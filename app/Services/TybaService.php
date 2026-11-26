<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TybaService
{
    /**
     * Todas las busquedas envian SoloActivos=false: por defecto la Rama solo devuelve procesos
     * con actuaciones en los ultimos 30 dias, y un proceso quieto parecia "no encontrado".
     */
    private string $apiBase = 'https://consultaprocesos.ramajudicial.gov.co:448/api/v2';

    /**
     * La consulta publica por cedula/NIT fue retirada por la Rama Judicial (desde nov-2026 responde
     * "no existe" o "no esta autorizado"). Se marca para usar la busqueda por nombre como alternativa.
     */
    private bool $documentSearchUnavailable = false;

    /**
     * Motivo legible del ultimo fallo de extractProcessInfo() (null si la consulta salio bien).
     */
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function documentSearchUnavailable(): bool
    {
        return $this->documentSearchUnavailable;
    }

    /**
     * Procesos de un cliente: por documento si la Rama lo permite; si no, por su nombre,
     * dejando solo los procesos donde todas las palabras del nombre aparecen entre las partes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchClientProcesses(string $document, int $tipoDocumento, string $fullName): array
    {
        $found = $this->searchByDocument($document, $tipoDocumento);

        if (! $this->documentSearchUnavailable) {
            return $found;
        }

        $tokens = array_values(array_filter(
            explode(' ', self::normalizeName($fullName)),
            fn (string $token): bool => mb_strlen($token) >= 2,
        ));

        // Con una sola palabra habria demasiados homonimos.
        if (count($tokens) < 2) {
            return [];
        }

        try {
            $byName = $this->searchByName(implode(' ', $tokens));
        } catch (\Throwable $e) {
            Log::warning('Tyba API: error busqueda por nombre del cliente', ['error' => $e->getMessage()]);

            return [];
        }

        return array_values(array_filter($byName, function (array $proceso) use ($tokens): bool {
            $sujetos = self::normalizeName((string) ($proceso['sujetos'] ?? ''));

            foreach ($tokens as $token) {
                if (! preg_match('/\b'.preg_quote($token, '/').'\b/u', $sujetos)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private static function normalizeName(string $value): string
    {
        $value = mb_strtoupper(Str::ascii($value));

        return trim((string) preg_replace('/[^A-Z0-9]+/', ' ', $value));
    }

    /**
     * Extraer informacion completa de un proceso desde la API publica de la Rama Judicial.
     *
     * @return array<string, mixed>|null
     */
    public function extractProcessInfo(string $radicado): ?array
    {
        $this->lastError = null;

        // Ningun error de la Rama (respuesta rara, campo faltante, timeout) debe romper el lote
        // nocturno: se registra como fallo de este caso y se sigue con el siguiente.
        try {
            return $this->fetchProcessInfo($radicado);
        } catch (\Throwable $e) {
            $this->lastError = 'Error inesperado al leer la respuesta de la Rama Judicial';
            Log::error('Tyba API: error inesperado', ['radicado' => $radicado, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchProcessInfo(string $radicado): ?array
    {
        $radicado = preg_replace('/[^0-9]/', '', $radicado);

        if (strlen($radicado) < 20) {
            $this->lastError = 'El radicado no tiene 23 digitos';

            return null;
        }

        // Paso 1: Buscar proceso por numero de radicacion
        $search = $this->searchByRadicado($radicado);

        if (! $search) {
            return null;
        }

        $idProceso = $search['idProceso'];
        $idConexion = $search['idConexion'];

        // Paso 2: Obtener detalles, sujetos y actuaciones en paralelo
        $detail = $this->getDetail($idProceso, $idConexion);
        $sujetos = $this->getSujetos($idProceso, $idConexion);
        $actuaciones = $this->getActuaciones($idProceso, $idConexion);

        if (! $detail) {
            $this->lastError = 'La Rama Judicial no entrego el detalle del proceso';
            Log::error('Tyba API: no se pudo obtener detalle', ['radicado' => $radicado]);

            return null;
        }

        // Sin actuaciones legibles no se puede afirmar "sin novedades": es un fallo.
        if ($actuaciones === null) {
            Log::error('Tyba API: no se pudieron leer las actuaciones', ['radicado' => $radicado, 'motivo' => $this->lastError]);

            return null;
        }

        $despachoName = trim($detail['despacho'] ?? $search['despacho'] ?? '');

        $info = [
            'codigo_proceso' => $detail['llaveProceso'] ?? $radicado,
            'tipo_proceso' => $detail['tipoProceso'] ?? '',
            'clase_proceso' => $detail['claseProceso'] ?? '',
            'subclase' => $detail['subclaseProceso'] ?? '',
            'departamento' => $search['departamento'] ?? '',
            'ciudad' => '',
            'corporacion' => $this->extractCorporacion($despachoName),
            'especialidad' => $this->extractEspecialidad($despachoName),
            'distrito_circuito' => '',
            'numero_despacho' => $this->extractNumeroDespacho($despachoName),
            'despacho' => $despachoName,
            'cod_despacho' => $detail['codDespachoCompleto'] ?? '',
            'ponente' => trim($detail['ponente'] ?? ''),
            'direccion' => '',
            'telefono' => '',
            'celular' => '',
            'email' => '',
            'fecha_publicacion' => $this->formatDate($detail['fechaProceso'] ?? $search['fechaProceso'] ?? ''),
            'fecha_ultima_actuacion' => $this->formatDate($search['fechaUltimaActuacion'] ?? ''),
            'ubicacion' => $detail['ubicacion'] ?? '',
            'recurso' => $detail['recurso'] ?? '',
            'es_privado' => $search['esPrivado'] ?? false,
            'sujetos' => $this->formatSujetos($sujetos),
            'actuaciones' => $this->formatActuaciones($actuaciones),
        ];

        Log::info('Tyba API: proceso importado', [
            'radicado' => $radicado,
            'despacho' => $info['despacho'],
            'sujetos' => count($info['sujetos']),
            'actuaciones' => count($info['actuaciones']),
        ]);

        return $info;
    }

    /**
     * Buscar procesos por nombre o razon social en la Rama Judicial.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchByName(string $name): array
    {
        $allProcesos = [];
        $page = 1;
        $maxPages = 3;

        do {
            $response = Http::timeout(15)
                ->get("{$this->apiBase}/Procesos/Consulta/NombreRazonSocial", [
                    'nombre' => $name,
                    'SoloActivos' => 'false',
                    'pagina' => $page,
                ]);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $procesos = $data['procesos'] ?? [];

            foreach ($procesos as $p) {
                $radicado = $p['llaveProceso'] ?? '';
                // Evitar duplicados (la API a veces repite)
                if (isset($allProcesos[$radicado])) {
                    continue;
                }

                $sujetos = trim(str_replace(["\r\n", "\t"], ' ', $p['sujetosProcesales'] ?? ''));
                $sujetos = preg_replace('/\s+/', ' ', $sujetos);

                $allProcesos[$radicado] = [
                    'radicado' => $radicado,
                    'despacho' => trim($p['despacho'] ?? ''),
                    'departamento' => $p['departamento'] ?? '',
                    'fecha' => $this->formatDate($p['fechaProceso'] ?? ''),
                    'ultima_actuacion' => $this->formatDate($p['fechaUltimaActuacion'] ?? ''),
                    'sujetos' => $sujetos,
                    'es_privado' => $p['esPrivado'] ?? false,
                ];
            }

            $totalPages = $data['paginacion']['cantidadPaginas'] ?? 1;
            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        return array_values($allProcesos);
    }

    /**
     * Buscar procesos por numero de documento (cedula o NIT) en la Rama Judicial.
     *
     * tipoDocumento: 1 = Cedula ciudadania, 2 = Cedula extranjeria,
     *                3 = NIT, 4 = Pasaporte, 5 = Tarjeta identidad
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchByDocument(string $document, int $tipoDocumento = 1): array
    {
        $allProcesos = [];
        $page = 1;
        $maxPages = 3;
        $cleanDoc = preg_replace('/[^\d]/', '', $document);

        if ($cleanDoc === '') {
            return [];
        }

        do {
            try {
                $response = Http::timeout(15)
                    ->get("{$this->apiBase}/Procesos/Consulta/DocumentoIdentificacion", [
                        'tipoDocumento' => $tipoDocumento,
                        'numeroDocumento' => $cleanDoc,
                        'SoloActivos' => 'false',
                        'pagina' => $page,
                    ]);
            } catch (\Exception $e) {
                // Sin el documento completo (dato personal): solo los ultimos digitos.
                Log::warning('Tyba API: error busqueda documento', [
                    'document' => '***'.substr($cleanDoc, -4),
                    'error' => str_replace($cleanDoc, '***'.substr($cleanDoc, -4), $e->getMessage()),
                ]);
                break;
            }

            if (in_array($response->status(), [401, 404], true)) {
                // La Rama retiro esta consulta del acceso publico.
                $this->documentSearchUnavailable = true;
                SystemNews::notifySuperadmins(
                    'La Rama Judicial no permite consultar por cédula o NIT',
                    [
                        'La consulta pública de procesos de la Rama Judicial respondió que la búsqueda por documento no existe o no está autorizada (HTTP '.$response->status().').',
                        'LegalWeb busca automáticamente por el nombre del cliente y filtra por las partes del proceso, tanto en el buscador como en la vigilancia nocturna.',
                        'Si la Rama vuelve a habilitarla, LegalWeb la usará de nuevo sin que tenga que hacer nada.',
                    ],
                    'Abrir Buscar procesos',
                    url('/admin/buscar-procesos-tyba'),
                    onceKey: 'rama.document_search_unavailable',
                );
                break;
            }

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $procesos = $data['procesos'] ?? [];

            foreach ($procesos as $p) {
                $radicado = $p['llaveProceso'] ?? '';
                if (isset($allProcesos[$radicado])) {
                    continue;
                }

                $sujetos = trim(str_replace(["\r\n", "\t"], ' ', $p['sujetosProcesales'] ?? ''));
                $sujetos = preg_replace('/\s+/', ' ', $sujetos);

                $allProcesos[$radicado] = [
                    'radicado' => $radicado,
                    'despacho' => trim($p['despacho'] ?? ''),
                    'departamento' => $p['departamento'] ?? '',
                    'fecha' => $this->formatDate($p['fechaProceso'] ?? ''),
                    'ultima_actuacion' => $this->formatDate($p['fechaUltimaActuacion'] ?? ''),
                    'sujetos' => $sujetos,
                    'es_privado' => $p['esPrivado'] ?? false,
                ];
            }

            $totalPages = $data['paginacion']['cantidadPaginas'] ?? 1;
            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        return array_values($allProcesos);
    }

    /**
     * Buscar proceso por numero de radicacion.
     *
     * @return array{idProceso: int, idConexion: int, departamento: string, despacho: string, fechaProceso: string, fechaUltimaActuacion: string, esPrivado: bool}|null
     */
    private function searchByRadicado(string $radicado): ?array
    {
        $response = null;

        // Reintentar hasta 3 veces si la API falla (503, timeout, etc)
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = Http::timeout(20)
                    ->get("{$this->apiBase}/Procesos/Consulta/NumeroRadicacion", [
                        'numero' => $radicado,
                        // Sin esto la Rama solo devuelve procesos con actuaciones en los ultimos 30 dias.
                        'SoloActivos' => 'false',
                        'pagina' => 1,
                    ]);

                if ($response->successful() && $response->json('procesos') !== null) {
                    break;
                }

                Log::warning('Tyba API: reintento busqueda', [
                    'attempt' => $attempt,
                    'status' => $response?->status(),
                    'radicado' => $radicado,
                ]);
            } catch (\Exception $e) {
                Log::warning('Tyba API: error conexion', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'radicado' => $radicado,
                ]);
            }

            if ($attempt < 3) {
                sleep(2);
            }
        }

        if (! $response || ! $response->successful()) {
            $this->lastError = $response
                ? 'La Rama Judicial no respondio (HTTP '.$response->status().')'
                : 'No hubo conexion con la Rama Judicial';
            Log::error('Tyba API: error en busqueda despues de 3 intentos', [
                'status' => $response?->status(),
                'radicado' => $radicado,
            ]);

            return null;
        }

        $data = $response->json();
        $procesos = $data['procesos'] ?? [];

        if (empty($procesos)) {
            $this->lastError = 'El radicado no aparece en la consulta publica de la Rama Judicial';
            Log::info('Tyba API: proceso no encontrado', ['radicado' => $radicado]);

            return null;
        }

        $proceso = $procesos[0];

        if (! isset($proceso['idProceso'], $proceso['idConexion'])) {
            $this->lastError = 'La Rama Judicial respondio con un formato distinto al esperado';
            Log::error('Tyba API: respuesta de busqueda sin idProceso', ['radicado' => $radicado]);

            return null;
        }

        return [
            'idProceso' => $proceso['idProceso'],
            'idConexion' => $proceso['idConexion'],
            'departamento' => $proceso['departamento'] ?? '',
            'despacho' => trim($proceso['despacho'] ?? ''),
            'fechaProceso' => $proceso['fechaProceso'] ?? '',
            'fechaUltimaActuacion' => $proceso['fechaUltimaActuacion'] ?? '',
            'esPrivado' => $proceso['esPrivado'] ?? false,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getDetail(int $idProceso, int $idConexion): ?array
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = Http::timeout(20)
                    ->get("{$this->apiBase}/Proceso/Detalle/{$idProceso}", [
                        'idConexion' => $idConexion,
                    ]);

                if ($response->successful() && $response->json('despacho') !== null) {
                    return $response->json();
                }
            } catch (\Exception) {
            }

            if ($attempt < 3) {
                sleep(1);
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSujetos(int $idProceso, int $idConexion): array
    {
        try {
            $response = Http::timeout(15)
                ->get("{$this->apiBase}/Proceso/Sujetos/{$idProceso}", [
                    'idConexion' => $idConexion,
                    'pagina' => 1,
                ]);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $sujetos = $response->json('sujetos');

        return is_array($sujetos) ? $sujetos : [];
    }

    /**
     * Actuaciones del proceso, o null si no se pudieron leer (error HTTP, sin conexion o
     * formato distinto). Una lista vacia significa que la Rama no reporta actuaciones.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function getActuaciones(int $idProceso, int $idConexion): ?array
    {
        $all = [];
        $page = 1;
        $maxPages = 5;

        do {
            try {
                $response = Http::timeout(15)
                    ->get("{$this->apiBase}/Proceso/Actuaciones/{$idProceso}", [
                        'idConexion' => $idConexion,
                        'pagina' => $page,
                    ]);
            } catch (\Throwable) {
                $this->lastError = 'No hubo conexion con la Rama Judicial al leer las actuaciones';

                return null;
            }

            if (! $response->successful()) {
                $this->lastError = 'La Rama Judicial no entrego las actuaciones (HTTP '.$response->status().')';

                return null;
            }

            $data = $response->json();

            if (! is_array($data) || ! array_key_exists('actuaciones', $data) || ! is_array($data['actuaciones'])) {
                $this->lastError = 'Las actuaciones llegaron con un formato distinto al esperado';

                return null;
            }

            $all = array_merge($all, $data['actuaciones']);
            $totalPages = $data['paginacion']['cantidadPaginas'] ?? 1;
            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        return $all;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawSujetos
     * @return array<int, array{rol: string, nombre: string, documento: string}>
     */
    private function formatSujetos(array $rawSujetos): array
    {
        $sujetos = [];

        foreach ($rawSujetos as $s) {
            $nombre = trim(str_replace(["\r\n", "\t"], ' ', $s['nombreRazonSocial'] ?? ''));
            $nombre = preg_replace('/\s+/', ' ', $nombre);
            $rol = trim($s['tipoSujeto'] ?? '');

            if (! $nombre || ! $rol) {
                continue;
            }

            $sujetos[] = [
                'rol' => $rol,
                'nombre' => $nombre,
                'documento' => '',
            ];
        }

        return $sujetos;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawActuaciones
     * @return array<int, array{ciclo: string, tipo: string, fecha: string, fecha_registro: string, fecha_inicial: string, fecha_final: string, anotacion: string, cod_regla: string, con_documentos: bool}>
     */
    private function formatActuaciones(array $rawActuaciones): array
    {
        $actuaciones = [];

        foreach ($rawActuaciones as $a) {
            $tipo = trim($a['actuacion'] ?? '');
            $fecha = $this->formatDate($a['fechaActuacion'] ?? '');
            $fechaRegistro = $this->formatDate($a['fechaRegistro'] ?? '');
            $fechaInicial = $this->formatDate($a['fechaInicial'] ?? '');
            $fechaFinal = $this->formatDate($a['fechaFinal'] ?? '');

            if (! $tipo || ! $fecha) {
                continue;
            }

            // codRegla viene "00" cuando la Rama no asigno regla; lo descartamos
            // para no ensuciar la descripcion.
            $codRegla = trim((string) ($a['codRegla'] ?? ''));
            if ($codRegla === '00' || $codRegla === '0') {
                $codRegla = '';
            }

            // Identificador propio de la actuacion en la Rama: distingue dos actuaciones con el
            // mismo titulo el mismo dia. Si no viene, una huella de su contenido.
            $ref = $a['idRegActuacion'] ?? $a['consActuacion'] ?? null;
            $ref = filled($ref)
                ? 'r:'.$ref
                : 'h:'.md5($tipo.'|'.$fecha.'|'.trim($a['anotacion'] ?? '').'|'.$fechaRegistro);

            $actuaciones[] = [
                'ref' => mb_substr((string) $ref, 0, 64),
                'ciclo' => '',
                'tipo' => $tipo,
                'fecha' => $fecha,
                'fecha_registro' => $fechaRegistro,
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
                'anotacion' => trim($a['anotacion'] ?? ''),
                'cod_regla' => $codRegla,
                'con_documentos' => ! empty($a['conDocumentos']),
            ];
        }

        return $actuaciones;
    }

    /**
     * Convertir fecha ISO a d/m/Y.
     */
    private function formatDate(string $date): string
    {
        if (! $date) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * Extraer especialidad del nombre del despacho.
     */
    private function extractEspecialidad(string $despacho): string
    {
        $despacho = strtolower($despacho);

        if (str_contains($despacho, 'civil')) {
            return 'Civil';
        }
        if (str_contains($despacho, 'penal')) {
            return 'Penal';
        }
        if (str_contains($despacho, 'laboral')) {
            return 'Laboral';
        }
        if (str_contains($despacho, 'familia')) {
            return 'Familia';
        }
        if (str_contains($despacho, 'administrativo')) {
            return 'Administrativo';
        }

        return 'General';
    }

    /**
     * Extraer corporacion del nombre del despacho.
     */
    private function extractCorporacion(string $despacho): string
    {
        $d = strtolower($despacho);

        if (str_contains($d, 'tribunal')) {
            return 'Tribunal';
        }
        if (str_contains($d, 'corte suprema')) {
            return 'Corte Suprema de Justicia';
        }
        if (str_contains($d, 'circuito')) {
            return 'Juzgado de Circuito';
        }
        if (str_contains($d, 'municipal')) {
            return 'Juzgado Municipal';
        }

        return '';
    }

    /**
     * Extraer numero de despacho (ej: "001" de "JUZGADO 001 CIVIL").
     */
    private function extractNumeroDespacho(string $despacho): string
    {
        if (preg_match('/\b(\d{1,3})\b/', $despacho, $m)) {
            return $m[1];
        }

        return '';
    }
}
