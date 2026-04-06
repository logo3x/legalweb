<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TybaService
{
    private string $processPageUrl;

    public function __construct()
    {
        $baseUrl = dirname(config('services.tyba.url'));
        $this->processPageUrl = $baseUrl.'/frmConsultaProceso.aspx';
    }

    /**
     * Extraer información completa de un proceso desde Tyba via URL directa.
     * No requiere captcha.
     *
     * @return array{
     *     codigo_proceso: string,
     *     tipo_proceso: string,
     *     clase_proceso: string,
     *     subclase: string,
     *     departamento: string,
     *     ciudad: string,
     *     corporacion: string,
     *     especialidad: string,
     *     despacho: string,
     *     direccion: string,
     *     telefono: string,
     *     celular: string,
     *     email: string,
     *     fecha_publicacion: string,
     *     distrito_circuito: string,
     *     numero_despacho: string,
     *     sujetos: array<int, array{rol: string, nombre: string, documento: string}>,
     * }|null
     */
    public function extractProcessInfo(string $radicado): ?array
    {
        $radicado = preg_replace('/[^0-9]/', '', $radicado);

        if (strlen($radicado) < 20) {
            return null;
        }

        $html = $this->fetchProcessPage($radicado);

        if (! $html) {
            return null;
        }

        return $this->parseProcessInfo($html);
    }

    /**
     * Consultar actuaciones de un proceso (acceso directo, sin captcha).
     * Las actuaciones pueden no estar disponibles via URL directa.
     *
     * @return array|null Lista de actuaciones o null si falla
     */
    public function consultarProceso(string $radicado): ?array
    {
        $radicado = preg_replace('/[^0-9]/', '', $radicado);

        if (strlen($radicado) < 20) {
            return null;
        }

        $html = $this->fetchProcessPage($radicado);

        if (! $html) {
            return null;
        }

        if (str_contains($html, 'MainContent_grdActuaciones')) {
            return $this->parseActuaciones($html, $radicado);
        }

        Log::warning('Tyba: actuaciones no disponibles via URL directa', ['radicado' => $radicado]);

        return [];
    }

    private function fetchProcessPage(string $radicado): ?string
    {
        $url = $this->processPageUrl.'?IdProceso='.$radicado;

        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])
            ->get($url);

        if (! $response->successful()) {
            Log::error('Tyba: error accediendo proceso', ['url' => $url, 'status' => $response->status()]);

            return null;
        }

        $html = $response->body();

        if (! str_contains($html, 'del Proceso')) {
            Log::error('Tyba: pagina no contiene info del proceso', ['radicado' => $radicado]);

            return null;
        }

        return $html;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseProcessInfo(string $html): array
    {
        // Campos del proceso (inputs readonly con value)
        $fieldMap = [
            'codigo_proceso' => 'MainContent_txtCodigoProceso',
            'tipo_proceso' => 'MainContent_txtTipoProceso',
            'clase_proceso' => 'MainContent_txtClaseProceso',
            'subclase' => 'MainContent_txtSubClaseProceso',
            'departamento' => 'MainContent_txtDepartamento',
            'ciudad' => 'MainContent_txtCiudad',
            'corporacion' => 'MainContent_txtCorporacion',
            'especialidad' => 'MainContent_txtEspecialidad',
            'distrito_circuito' => 'MainContent_txtDistritoCircuito',
            'numero_despacho' => 'MainContent_txtNumDespacho',
            'despacho' => 'MainContent_txtNomDespacho',
            'direccion' => 'MainContent_txtDireccion',
            'telefono' => 'MainContent_txtTelefono',
            'celular' => 'MainContent_txtCelular',
            'email' => 'MainContent_txtCorreoExterno',
            'fecha_publicacion' => 'MainContent_txtFechaPublicacion',
        ];

        $info = [];
        foreach ($fieldMap as $key => $id) {
            $info[$key] = $this->extractInputValue($html, $id);
        }

        $info['sujetos'] = $this->parseSujetos($html);

        return $info;
    }

    private function extractInputValue(string $html, string $id): string
    {
        // Buscar input por id con value
        if (preg_match('/id="'.preg_quote($id, '/').'"[^>]*value="([^"]*)"/si', $html, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        // Buscar value antes de id
        if (preg_match('/value="([^"]*)"[^>]*id="'.preg_quote($id, '/').'"[^>]*/si', $html, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }

        return '';
    }

    /**
     * @return array<int, array{rol: string, nombre: string, documento: string}>
     */
    private function parseSujetos(string $html): array
    {
        $sujetos = [];

        if (! preg_match('/<table[^>]*id="MainContent_grdCiudadanos"[^>]*>(.*?)<\/table>/si', $html, $tableMatch)) {
            return [];
        }

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableMatch[1], $rows);

        foreach (array_slice($rows[1] ?? [], 1) as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cells);

            if (empty($cells[1]) || count($cells[1]) < 6) {
                continue;
            }

            $rol = strip_tags(trim($cells[1][0] ?? ''));
            $nombre = strip_tags(trim($cells[1][6] ?? ''));
            $documento = strip_tags(trim($cells[1][5] ?? ''));

            if (! $rol || ! $nombre) {
                continue;
            }

            $sujetos[] = [
                'rol' => html_entity_decode($rol, ENT_QUOTES, 'UTF-8'),
                'nombre' => html_entity_decode($nombre, ENT_QUOTES, 'UTF-8'),
                'documento' => html_entity_decode($documento, ENT_QUOTES, 'UTF-8'),
            ];
        }

        return $sujetos;
    }

    /**
     * @return array<int, array{date: string, description: string, attachments: int}>
     */
    private function parseActuaciones(string $html, string $radicado): array
    {
        $actuaciones = [];

        if (! preg_match('/<table[^>]*id="MainContent_grdActuaciones"[^>]*>(.*?)<\/table>/si', $html, $tableMatch)) {
            return [];
        }

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $tableMatch[1], $rows);

        // Columnas: (icono), Ciclo, Tipo Actuación, Fecha Actuación, Fecha Registro
        foreach (array_slice($rows[1] ?? [], 1) as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cells);

            if (empty($cells[1]) || count($cells[1]) < 4) {
                continue;
            }

            $ciclo = strip_tags(trim($cells[1][1] ?? ''));
            $tipoActuacion = strip_tags(trim($cells[1][2] ?? ''));
            $fechaActuacion = strip_tags(trim($cells[1][3] ?? ''));

            if (! $fechaActuacion || ! $tipoActuacion) {
                continue;
            }

            $actuaciones[] = [
                'date' => $fechaActuacion,
                'description' => $tipoActuacion.($ciclo ? " ({$ciclo})" : ''),
                'attachments' => 0,
            ];
        }

        return $actuaciones;
    }
}
