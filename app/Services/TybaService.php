<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TybaService
{
    private string $apiBase = 'https://consultaprocesos.ramajudicial.gov.co:448/api/v2';

    /**
     * Extraer informacion completa de un proceso desde la API publica de la Rama Judicial.
     *
     * @return array<string, mixed>|null
     */
    public function extractProcessInfo(string $radicado): ?array
    {
        $radicado = preg_replace('/[^0-9]/', '', $radicado);

        if (strlen($radicado) < 20) {
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
            Log::error('Tyba API: no se pudo obtener detalle', ['radicado' => $radicado]);

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
            Log::error('Tyba API: error en busqueda despues de 3 intentos', [
                'status' => $response?->status(),
                'radicado' => $radicado,
            ]);

            return null;
        }

        $data = $response->json();
        $procesos = $data['procesos'] ?? [];

        if (empty($procesos)) {
            Log::info('Tyba API: proceso no encontrado', ['radicado' => $radicado]);

            return null;
        }

        $proceso = $procesos[0];

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
        $response = Http::timeout(15)
            ->get("{$this->apiBase}/Proceso/Sujetos/{$idProceso}", [
                'idConexion' => $idConexion,
                'pagina' => 1,
            ]);

        if (! $response->successful()) {
            return [];
        }

        return $response->json()['sujetos'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActuaciones(int $idProceso, int $idConexion): array
    {
        $all = [];
        $page = 1;
        $maxPages = 5;

        do {
            $response = Http::timeout(15)
                ->get("{$this->apiBase}/Proceso/Actuaciones/{$idProceso}", [
                    'idConexion' => $idConexion,
                    'pagina' => $page,
                ]);

            if (! $response->successful()) {
                break;
            }

            $data = $response->json();
            $all = array_merge($all, $data['actuaciones'] ?? []);
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
     * @return array<int, array{ciclo: string, tipo: string, fecha: string, fecha_registro: string, fecha_inicial: string, fecha_final: string, anotacion: string, cod_regla: string, con_documentos: bool, cant_documentos: int}>
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

            $actuaciones[] = [
                'ciclo' => '',
                'tipo' => $tipo,
                'fecha' => $fecha,
                'fecha_registro' => $fechaRegistro,
                'fecha_inicial' => $fechaInicial,
                'fecha_final' => $fechaFinal,
                'anotacion' => trim($a['anotacion'] ?? ''),
                'cod_regla' => trim((string) ($a['codRegla'] ?? '')),
                'con_documentos' => (bool) ($a['conDocumentos'] ?? false),
                'cant_documentos' => (int) ($a['cant'] ?? 0),
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
