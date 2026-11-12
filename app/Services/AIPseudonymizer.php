<?php

namespace App\Services;

use App\Models\LegalCase;

/**
 * Oculta los datos personales antes de enviar un texto a un proveedor de IA y los
 * vuelve a poner en la respuesta.
 *
 * El nombre del cliente, su documento, telefono, correo y direccion, los nombres de
 * las partes del proceso y los datos del abogado se reemplazan por marcadores como
 * [CLIENTE] o [PARTE_1]. El proveedor nunca ve a quien se refiere el caso; el abogado
 * recibe el texto completo. Los datos publicos del proceso (radicado, despacho, juez)
 * no se ocultan porque la IA los necesita para orientar la respuesta.
 */
class AIPseudonymizer
{
    /**
     * Valor original => marcador.
     *
     * @var array<string, string>
     */
    private array $replacements = [];

    /**
     * Marcador => valor que se pone de vuelta en la respuesta.
     *
     * @var array<string, string>
     */
    private array $restorations = [];

    private int $partyCount = 0;

    private int $genericCount = 0;

    public static function forCase(LegalCase $case): self
    {
        $instance = new self;
        $client = $case->client;

        if ($client) {
            $fullName = trim("{$client->first_name} {$client->last_name}");
            $instance->add($fullName, '[CLIENTE]', $fullName);
            // La Rama Judicial escribe los nombres como "APELLIDOS NOMBRES".
            $instance->add(trim("{$client->last_name} {$client->first_name}"), '[CLIENTE]', $fullName);
            $instance->add((string) $client->document_number, '[CLIENTE_DOCUMENTO]');
            $instance->add((string) $client->phone, '[CLIENTE_TELEFONO]');
            $instance->add((string) $client->email, '[CLIENTE_CORREO]');
            $instance->add((string) $client->address, '[CLIENTE_DIRECCION]');
        }

        if ($case->user) {
            $instance->add((string) $case->user->name, '[ABOGADO]');
            $instance->add((string) $case->user->email, '[ABOGADO_CORREO]');
        }

        foreach (self::partyNames($case) as $party) {
            $instance->addParty($party);
        }

        return $instance;
    }

    /**
     * Reemplaza los datos personales por marcadores.
     */
    public function mask(string $text): string
    {
        // Primero los valores mas largos: "Perez Garcia Juan" antes que "Juan".
        $values = array_keys($this->replacements);
        usort($values, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($values as $value) {
            $text = preg_replace('/(?<![\p{L}\p{N}])'.preg_quote($value, '/').'(?![\p{L}\p{N}])/iu', $this->replacements[$value], $text) ?? $text;
        }

        // Datos que no estan en la ficha pero aparecen en textos libres (descripcion, actuaciones).
        $text = preg_replace_callback('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', fn (array $m): string => $this->generic('CORREO', $m[0]), $text) ?? $text;
        $text = preg_replace_callback('/(?<!\d)(?:\+?57[\s\-]?)?3\d{2}[\s\-]?\d{3}[\s\-]?\d{4}(?!\d)/', fn (array $m): string => $this->generic('TELEFONO', $m[0]), $text) ?? $text;
        $text = preg_replace_callback(
            '/\b(C\.?\s?C\.?|c[ée]dula(?:\s+de\s+ciudadan[ií]a)?|NIT|documento(?:\s+de\s+identidad)?)(\s*(?:No\.?|n[uú]mero|:)?\s*)([\d][\d\.\-]{4,14}\d)/iu',
            fn (array $m): string => $m[1].$m[2].$this->generic('DOCUMENTO', $m[3]),
            $text,
        ) ?? $text;

        return $text;
    }

    /**
     * Pone de vuelta los datos originales en la respuesta de la IA.
     */
    public function restore(string $text): string
    {
        foreach ($this->restorations as $token => $value) {
            $text = str_ireplace($token, $value, $text);
        }

        return $text;
    }

    /**
     * Instruccion para el proveedor sobre como tratar los marcadores.
     */
    public static function instructions(): string
    {
        return "\n\nPRIVACIDAD: los datos personales del texto se reemplazaron por marcadores entre corchetes, "
            .'por ejemplo [CLIENTE], [CLIENTE_DOCUMENTO], [ABOGADO] o [PARTE_1]. Escribe esos marcadores exactamente '
            .'igual donde corresponda el dato y nunca inventes nombres, documentos ni datos de contacto.';
    }

    /**
     * @return array<string, string>
     */
    public function replacements(): array
    {
        return $this->replacements;
    }

    private function add(string $value, string $token, ?string $restoreAs = null): void
    {
        $value = trim($value);

        // Valores muy cortos ("Ana", "1") reemplazarian palabras comunes.
        if (mb_strlen($value) < 5 || isset($this->replacements[mb_strtolower($value)])) {
            return;
        }

        $this->replacements[mb_strtolower($value)] = $token;
        $this->restorations[$token] ??= $restoreAs ?? $value;
    }

    private function addParty(string $name): void
    {
        $name = trim($name, " \t\n\r\0\x0B,.");

        if (mb_strlen($name) < 5 || isset($this->replacements[mb_strtolower($name)])) {
            return;
        }

        $token = '[PARTE_'.(++$this->partyCount).']';
        $this->add($name, $token);

        // Los titulos de los casos importados recortan los nombres a 40 caracteres.
        if (mb_strlen($name) > 40) {
            $this->add(mb_substr($name, 0, 40), $token, $name);
        }

        // Listas "A, B": cada persona por separado.
        if (str_contains($name, ',')) {
            foreach (explode(',', $name) as $part) {
                $this->addParty($part);
            }
        }
    }

    private function generic(string $type, string $value): string
    {
        $existing = array_search($value, $this->restorations, true);

        if ($existing !== false && str_starts_with($existing, "[{$type}_")) {
            return $existing;
        }

        $token = "[{$type}_".(++$this->genericCount).']';
        $this->restorations[$token] = $value;

        return $token;
    }

    /**
     * Nombres de las partes: contraparte y sujetos procesales de la descripcion importada.
     *
     * @return list<string>
     */
    private static function partyNames(LegalCase $case): array
    {
        $names = [];

        if (filled($case->opposing_party)) {
            $names[] = (string) $case->opposing_party;
        }

        $byRole = [];

        foreach (preg_split('/\R/', (string) $case->description) ?: [] as $line) {
            // "- Demandante: NOMBRE" (Rama Judicial) o "Demandante: NOMBRE" (SAMAI).
            if (preg_match('/^\s*-?\s*([^:\n]{3,60}):\s*(.+)$/u', $line, $m)
                && preg_match('/demandant|demandad|accionant|accionad|ejecutant|ejecutad|apoderad|tercer|denunciant|indiciad|v[ií]ctima|causante|interviniente|convocant|convocad|solicitante|deudor|acreedor|litisconsorte|\bpartes?\b/iu', $m[1])) {
                $names[] = trim($m[2]);
                $role = str_contains(mb_strtolower($m[1]), 'demandante') ? 'demandantes' : (str_contains(mb_strtolower($m[1]), 'demandado') ? 'demandados' : null);

                if ($role) {
                    $byRole[$role][] = trim($m[2]);
                }
            }
        }

        // Los titulos importados unen las partes por rol: "A, B vs C".
        foreach ($byRole as $list) {
            $names[] = implode(', ', array_unique($list));
        }

        return array_values(array_unique(array_filter($names)));
    }
}
