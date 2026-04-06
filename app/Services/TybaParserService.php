<?php

namespace App\Services;

class TybaParserService
{
    /**
     * Parsear texto copiado de la pagina de Tyba (Ctrl+A, Ctrl+C).
     *
     * @return array<string, mixed>|null
     */
    public function parseText(string $text): ?array
    {
        $text = trim($text);

        if (strlen($text) < 50) {
            return null;
        }

        $info = [
            'codigo_proceso' => '',
            'tipo_proceso' => '',
            'clase_proceso' => '',
            'subclase' => '',
            'departamento' => '',
            'ciudad' => '',
            'corporacion' => '',
            'especialidad' => '',
            'despacho' => '',
            'direccion' => '',
            'telefono' => '',
            'email' => '',
            'fecha_publicacion' => '',
            'demandantes' => '',
            'demandados' => '',
            'contraparte' => '',
            'sujetos_text' => '',
            'actuaciones_text' => '',
        ];

        // Extraer campos label: valor
        $fieldMap = [
            'codigo_proceso' => ['Código Proceso', 'Codigo Proceso'],
            'tipo_proceso' => ['Tipo Proceso'],
            'clase_proceso' => ['Clase Proceso'],
            'subclase' => ['Subclase Proceso'],
            'departamento' => ['Departamento Proceso'],
            'ciudad' => ['Ciudad Proceso'],
            'corporacion' => ['Corporación', 'Corporacion'],
            'especialidad' => ['Especialidad'],
            'despacho' => ['Despacho'],
            'direccion' => ['Dirección', 'Direccion'],
            'telefono' => ['Teléfono', 'Telefono'],
            'email' => ['Correo Electrónico Externo', 'Correo Electronico'],
            'fecha_publicacion' => ['Fecha Publicación', 'Fecha Publicacion'],
        ];

        $lines = preg_split('/[\r\n]+/', $text);
        $cleanLines = array_map('trim', $lines);
        $cleanLines = array_values(array_filter($cleanLines, fn ($l) => $l !== ''));

        foreach ($fieldMap as $key => $labels) {
            foreach ($labels as $label) {
                $value = $this->extractFieldValue($cleanLines, $label);
                if ($value) {
                    $info[$key] = $value;

                    break;
                }
            }
        }

        // Extraer sujetos procesales
        $sujetosSection = $this->extractSection($text, 'Sujetos', ['Predios', 'Archivos', 'Actuaciones']);
        if ($sujetosSection) {
            $info['sujetos_text'] = $sujetosSection;

            // Extraer demandantes y demandados
            $demandantes = [];
            $demandados = [];
            foreach (preg_split('/[\r\n]+/', $sujetosSection) as $line) {
                $line = trim($line);
                if (preg_match('/Demandante.*?(?:CC|NIT|C\.?C\.?)?\s*[\d.,]+\s+(.+?)(?:\s+\d{2}-\d{2}-\d{4})?$/i', $line, $m)) {
                    $demandantes[] = trim($m[1]);
                } elseif (preg_match('/Demandado.*?(?:CC|NIT|C\.?C\.?)?\s*[\d.,]+\s+(.+?)(?:\s+\d{2}-\d{2}-\d{4})?$/i', $line, $m)) {
                    $demandados[] = trim($m[1]);
                }
            }
            $info['demandantes'] = implode(', ', array_unique($demandantes));
            $info['demandados'] = implode(', ', array_unique($demandados));
            $info['contraparte'] = $info['demandados'] ?: $info['demandantes'];
        }

        // Extraer actuaciones
        $actuacionesSection = $this->extractSection($text, 'Actuaciones', ['©', 'RED INTEGRADA']);
        if ($actuacionesSection) {
            $info['actuaciones_text'] = $actuacionesSection;
        }

        // Validar que se extrajo algo util
        if (empty($info['codigo_proceso']) && empty($info['despacho']) && empty($info['clase_proceso'])) {
            return null;
        }

        return $info;
    }

    /**
     * Extraer el valor de un campo buscando el label en las lineas.
     */
    private function extractFieldValue(array $lines, string $label): string
    {
        for ($i = 0; $i < count($lines); $i++) {
            // Caso 1: "Label\nValor" (label en una linea, valor en la siguiente)
            if (strcasecmp($lines[$i], $label) === 0 && isset($lines[$i + 1])) {
                $next = $lines[$i + 1];
                // Verificar que no sea otro label
                if (! str_contains($next, 'Proceso') || strlen($next) > 50) {
                    return $next;
                }
            }

            // Caso 2: "Label Valor" (en la misma linea con tab/espacios)
            if (preg_match('/^'.preg_quote($label, '/').'\s{2,}(.+)$/i', $lines[$i], $m)) {
                return trim($m[1]);
            }

            // Caso 3: "Label: Valor"
            if (preg_match('/^'.preg_quote($label, '/').'\s*:\s*(.+)$/i', $lines[$i], $m)) {
                return trim($m[1]);
            }
        }

        return '';
    }

    /**
     * Extraer una seccion de texto entre un titulo y los siguientes titulos.
     */
    private function extractSection(string $text, string $startTitle, array $endTitles): string
    {
        $pattern = '/\b'.preg_quote($startTitle, '/').'\b\s*\n(.*?)(?=\b(?:'.implode('|', array_map(fn ($t) => preg_quote($t, '/'), $endTitles)).')\b|\z)/si';
        if (preg_match($pattern, $text, $m)) {
            return trim($m[1]);
        }

        return '';
    }
}
