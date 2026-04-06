<?php

namespace App\Services;

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
     * Extraer información completa de un proceso desde Tyba via Browserless.
     * Usa un navegador real en la nube para obtener la pagina renderizada.
     *
     * @return array<string, mixed>|null
     */
    public function extractProcessInfo(string $radicado): ?array
    {
        $radicado = preg_replace('/[^0-9]/', '', $radicado);

        if (strlen($radicado) < 20) {
            return null;
        }

        $url = $this->processPageUrl.'?IdProceso='.$radicado;

        $browserless = app(BrowserlessService::class);
        $html = $browserless->getRenderedHtml($url);

        if (! $html || ! str_contains($html, 'del Proceso')) {
            Log::error('Tyba: no se pudo obtener pagina via Browserless', [
                'radicado' => $radicado,
                'html_size' => $html ? strlen($html) : 0,
            ]);

            return null;
        }

        $info = $this->parseProcessInfo($html);

        // Validar que tenga datos reales
        if (empty($info['codigo_proceso']) && empty($info['despacho']) && empty($info['clase_proceso'])) {
            Log::error('Tyba: proceso sin datos', ['radicado' => $radicado]);

            return null;
        }

        Log::info('Tyba: proceso importado', [
            'radicado' => $radicado,
            'despacho' => $info['despacho'],
            'sujetos' => count($info['sujetos']),
            'actuaciones' => count($info['actuaciones']),
        ]);

        return $info;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseProcessInfo(string $html): array
    {
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
        $info['actuaciones'] = $this->parseActuaciones($html);

        return $info;
    }

    private function extractInputValue(string $html, string $id): string
    {
        $escaped = preg_quote($id, '/');

        // Buscar el tag completo que contiene este id
        if (preg_match('/<input[^>]*id="'.$escaped.'"[^>]*>/si', $html, $tagMatch)) {
            if (preg_match('/value="([^"]*)"/', $tagMatch[0], $valMatch)) {
                return html_entity_decode(trim($valMatch[1]), ENT_QUOTES, 'UTF-8');
            }
        }

        // Fallback: buscar por name
        $name = str_replace('_', '$', str_replace('MainContent_', 'ctl00$MainContent$', $id));
        if (preg_match('/<input[^>]*name="'.preg_quote($name, '/').'"[^>]*>/si', $html, $tagMatch)) {
            if (preg_match('/value="([^"]*)"/', $tagMatch[0], $valMatch)) {
                return html_entity_decode(trim($valMatch[1]), ENT_QUOTES, 'UTF-8');
            }
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
     * @return array<int, array{ciclo: string, tipo: string, fecha: string, fecha_registro: string}>
     */
    private function parseActuaciones(string $html): array
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
            $tipo = strip_tags(trim($cells[1][2] ?? ''));
            $fecha = strip_tags(trim($cells[1][3] ?? ''));
            $fechaRegistro = strip_tags(trim($cells[1][4] ?? ''));

            if (! $fecha || ! $tipo) {
                continue;
            }

            $actuaciones[] = [
                'ciclo' => html_entity_decode($ciclo, ENT_QUOTES, 'UTF-8'),
                'tipo' => html_entity_decode($tipo, ENT_QUOTES, 'UTF-8'),
                'fecha' => $fecha,
                'fecha_registro' => $fechaRegistro,
            ];
        }

        return $actuaciones;
    }
}
