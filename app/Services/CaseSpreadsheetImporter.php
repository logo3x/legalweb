<?php

namespace App\Services;

use App\Models\CaseType;
use App\Models\Client;
use App\Models\Firm;
use App\Models\LegalCase;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Throwable;

/**
 * Importa casos y clientes desde una hoja de Excel (.xlsx) o CSV.
 *
 * Pensado para el abogado que llega con su lista de procesos en Excel: crea los clientes
 * que no existan (por documento), crea los casos y deja los radicados listos para que la
 * sincronizacion nocturna traiga las actuaciones de la Rama Judicial o SAMAI.
 */
class CaseSpreadsheetImporter
{
    public const MAX_ROWS = 500;

    /**
     * Columnas de la plantilla: clave interna => [encabezado, ejemplo, alias aceptados].
     *
     * @var array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public const COLUMNS = [
        'radicado' => ['Radicado', '11001310300120240012300', ['radicado_judicial', 'numero_de_radicado', 'numero_de_proceso', 'codigo_de_proceso', 'codigo_proceso', 'proceso']],
        'titulo' => ['Titulo', 'Ejecutivo singular - Banco del Ejemplo', ['asunto', 'nombre_del_caso', 'caso']],
        'cliente_documento' => ['Documento del cliente', '79845123', ['documento', 'cedula', 'nit', 'identificacion', 'documento_cliente', 'cc']],
        'cliente_tipo_documento' => ['Tipo de documento', 'CC', ['tipo_documento', 'tipo_de_documento']],
        'cliente_nombres' => ['Nombres del cliente', 'Carlos Andres', ['nombres', 'nombre', 'cliente', 'nombre_cliente', 'razon_social']],
        'cliente_apellidos' => ['Apellidos del cliente', 'Perez Gomez', ['apellidos', 'apellido']],
        'cliente_email' => ['Correo del cliente', 'cliente@ejemplo.com', ['correo', 'email', 'correo_electronico', 'correo_cliente']],
        'cliente_telefono' => ['Telefono del cliente', '3001234567', ['telefono', 'celular', 'movil', 'telefono_cliente']],
        'despacho' => ['Despacho', 'Juzgado 3 Civil del Circuito de Medellin', ['juzgado', 'despacho_judicial', 'tribunal']],
        'juez' => ['Juez', '', ['magistrado', 'ponente']],
        'contraparte' => ['Contraparte', 'Banco del Ejemplo S.A.', ['demandado', 'demandante', 'parte_contraria']],
        'tipo_proceso' => ['Tipo de proceso', 'Civil', ['tipo', 'clase', 'clase_de_proceso', 'especialidad', 'area']],
        'estado' => ['Estado', 'Abierto', ['estado_del_caso']],
        'prioridad' => ['Prioridad', 'Media', ['urgencia']],
        'fecha_inicio' => ['Fecha de inicio', '15/03/2024', ['fecha', 'fecha_de_radicacion', 'inicio']],
        'abogado' => ['Abogado responsable (correo)', '', ['abogado_responsable', 'responsable', 'abogado_email', 'abogado_responsable_correo']],
        'notas' => ['Notas', '', ['descripcion', 'observaciones', 'comentarios']],
    ];

    private const STATUSES = [
        'abierto' => 'abierto', 'activo' => 'abierto', 'nuevo' => 'abierto',
        'en_progreso' => 'en_progreso', 'en_curso' => 'en_progreso', 'en_tramite' => 'en_progreso', 'tramite' => 'en_progreso',
        'en_espera' => 'en_espera', 'suspendido' => 'en_espera', 'pendiente' => 'en_espera',
        'cerrado' => 'cerrado', 'terminado' => 'cerrado', 'finalizado' => 'cerrado',
        'archivado' => 'archivado', 'archivo' => 'archivado',
    ];

    private const PRIORITIES = ['baja', 'media', 'alta', 'urgente'];

    private const DOCUMENT_TYPES = ['CC', 'CE', 'NIT', 'PP', 'TI'];

    private int $clientsCreated = 0;

    public function __construct(private User $user) {}

    /**
     * Genera la plantilla de Excel con encabezados y una fila de ejemplo.
     */
    public static function writeTemplate(string $path): void
    {
        $writer = new XlsxWriter;
        $writer->getOptions()->setColumnWidth(26, ...range(1, count(self::COLUMNS)));
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(array_column(self::COLUMNS, 0), (new Style)->setFontBold()->setBackgroundColor('DBEAFE')));
        $writer->addRow(Row::fromValues(array_column(self::COLUMNS, 1)));
        $writer->close();
    }

    /**
     * @return array{rows: int, created: int, clients_created: int, duplicates: int, errors: int, results: list<array{radicado: string, status: string, msg: string, case_number: ?string, case_id?: int}>}
     */
    public function import(string $path, string $extension): array
    {
        $rows = $this->readRows($path, $extension);
        $header = array_shift($rows) ?? [];
        $map = $this->mapHeader($header);

        if (! isset($map['cliente_documento']) || (! isset($map['titulo']) && ! isset($map['radicado']))) {
            throw new \InvalidArgumentException('El archivo debe tener al menos las columnas "Documento del cliente" y "Titulo" o "Radicado". Descargue la plantilla para ver el formato.');
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => collect($row)->contains(fn ($value): bool => filled($this->text($value)))));

        if (count($rows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('El archivo tiene '.count($rows).' filas. Importe como maximo '.self::MAX_ROWS.' a la vez.');
        }

        $results = [];

        foreach ($rows as $index => $row) {
            $values = [];
            foreach ($map as $key => $column) {
                $values[$key] = $row[$column] ?? null;
            }

            $results[] = $this->importRow($values, $index + 2);
        }

        return [
            'rows' => count($results),
            'created' => collect($results)->where('status', 'ok')->count(),
            'clients_created' => $this->clientsCreated,
            'duplicates' => collect($results)->where('status', 'duplicado')->count(),
            'errors' => collect($results)->whereIn('status', ['error', 'limite'])->count(),
            'results' => $results,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{radicado: string, status: string, msg: string, case_number: ?string, case_id?: int}
     */
    private function importRow(array $values, int $line): array
    {
        $firm = $this->user->firm;
        $radicado = preg_replace('/\D/', '', $this->text($values['radicado'] ?? null));
        $label = "Fila {$line}".($radicado !== '' ? " - {$radicado}" : '');
        $fail = fn (string $msg, string $status = 'error'): array => ['radicado' => $label, 'status' => $status, 'msg' => $msg, 'case_number' => null];

        if ($radicado !== '' && (strlen($radicado) < 20 || strlen($radicado) > 30)) {
            return $fail('El radicado debe tener 23 digitos.');
        }

        if ($radicado !== '' && LegalCase::withoutGlobalScopes()->where('firm_id', $firm->id)->where('external_case_number', $radicado)->exists()) {
            return $fail('Ya tiene un caso con este radicado.', 'duplicado');
        }

        $status = $this->status($values['estado'] ?? null);

        if (in_array($status, FirmPlan::ACTIVE_STATUSES, true) && ! $firm->planLimits()->canAddActiveCase()) {
            return $fail('Alcanzo el limite de casos activos de su plan.', 'limite');
        }

        $client = $this->resolveClient($values, $firm);

        if (is_string($client)) {
            return $fail($client);
        }

        $caseType = $this->caseType($this->text($values['tipo_proceso'] ?? null));
        $title = $this->text($values['titulo'] ?? null)
            ?: trim(($caseType->name === 'General' ? 'Proceso' : $caseType->name).' - '.$client->first_name.' '.$client->last_name);

        try {
            $case = LegalCase::create([
                'firm_id' => $firm->id,
                'case_number' => LegalCase::nextCaseNumber(),
                'external_case_number' => $radicado ?: null,
                'title' => Str::limit($title, 250, ''),
                'description' => $this->text($values['notas'] ?? null) ?: null,
                'case_type_id' => $caseType->id,
                'client_id' => $client->id,
                'user_id' => $this->responsibleLawyer($this->text($values['abogado'] ?? null)),
                'status' => $status,
                'priority' => $this->priority($values['prioridad'] ?? null),
                'court' => Str::limit($this->text($values['despacho'] ?? null), 250, '') ?: null,
                'judge' => Str::limit($this->text($values['juez'] ?? null), 250, '') ?: null,
                'opposing_party' => Str::limit($this->text($values['contraparte'] ?? null), 250, '') ?: null,
                'started_at' => $this->date($values['fecha_inicio'] ?? null),
                'closed_at' => in_array($status, ['cerrado', 'archivado'], true) ? now() : null,
                'is_demo' => false,
            ]);
        } catch (QueryException) {
            return $fail('No se pudo crear el caso. Revise los datos de la fila.');
        }

        return [
            'radicado' => $label,
            'status' => 'ok',
            'msg' => $radicado !== '' ? 'Creado. Las actuaciones llegan en la proxima sincronizacion.' : 'Creado sin radicado.',
            'case_number' => $case->case_number,
            'case_id' => $case->id,
        ];
    }

    /**
     * Busca el cliente por documento dentro de la firma o lo crea. Devuelve un mensaje si falta informacion.
     *
     * @param  array<string, mixed>  $values
     */
    private function resolveClient(array $values, Firm $firm): Client|string
    {
        $document = preg_replace('/[^0-9A-Za-z]/', '', $this->text($values['cliente_documento'] ?? null));

        if ($document === '') {
            return 'Falta el documento del cliente.';
        }

        $existing = Client::withoutGlobalScopes()->where('firm_id', $firm->id)->where('document_number', $document)->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        $firstName = $this->text($values['cliente_nombres'] ?? null);

        if ($firstName === '') {
            return "El cliente con documento {$document} no existe y falta su nombre.";
        }

        $type = strtoupper(preg_replace('/[^A-Za-z]/', '', $this->text($values['cliente_tipo_documento'] ?? null)));
        $email = $this->text($values['cliente_email'] ?? null);

        $this->clientsCreated++;

        return Client::create([
            'firm_id' => $firm->id,
            'user_id' => $this->user->id,
            'document_type' => in_array($type, self::DOCUMENT_TYPES, true) ? $type : (strlen($document) === 9 ? 'NIT' : 'CC'),
            'document_number' => Str::limit($document, 20, ''),
            'first_name' => Str::limit($firstName, 100, ''),
            'last_name' => Str::limit($this->text($values['cliente_apellidos'] ?? null), 100, ''),
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'phone' => Str::limit(preg_replace('/[^0-9+]/', '', $this->text($values['cliente_telefono'] ?? null)), 20, '') ?: null,
            'is_demo' => false,
        ]);
    }

    private function responsibleLawyer(string $value): int
    {
        if ($value === '' || ! $this->user->isAdmin()) {
            return $this->user->id;
        }

        $lawyer = User::where('firm_id', $this->user->firm_id)
            ->where(fn ($query) => $query->where('email', $value)->orWhere('name', $value))
            ->first();

        return $lawyer?->id ?? $this->user->id;
    }

    private function caseType(string $name): CaseType
    {
        if ($name !== '') {
            $match = CaseType::all()->first(fn (CaseType $type): bool => self::normalize($type->name) === self::normalize($name));

            if ($match) {
                return $match;
            }
        }

        return CaseType::firstOrCreate(['name' => 'General']);
    }

    private function status(mixed $value): string
    {
        return self::STATUSES[self::normalize($this->text($value))] ?? 'abierto';
    }

    private function priority(mixed $value): string
    {
        $priority = self::normalize($this->text($value));

        return in_array($priority, self::PRIORITIES, true) ? $priority : 'media';
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        $text = $this->text($value);

        if ($text === '') {
            return null;
        }

        // Fecha guardada como numero de serie de Excel.
        if (is_numeric($text) && (float) $text > 20000 && (float) $text < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $text);
        }

        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $text);

                if ($date && $date->year > 1950 && $date->year < 2100) {
                    return $date;
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    /**
     * Relaciona cada columna del archivo con un campo conocido.
     *
     * @param  list<mixed>  $header
     * @return array<string, int>
     */
    private function mapHeader(array $header): array
    {
        $lookup = [];
        foreach (self::COLUMNS as $key => [$label, , $aliases]) {
            foreach ([$key, $label, ...$aliases] as $name) {
                $lookup[self::normalize($name)] ??= $key;
            }
        }

        $map = [];
        foreach ($header as $index => $name) {
            $key = $lookup[self::normalize($this->text($name))] ?? null;

            if ($key && ! isset($map[$key])) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readRows(string $path, string $extension): array
    {
        if ($extension === 'xlsx') {
            $reader = new XlsxReader;
        } else {
            $options = new CsvOptions;
            $options->FIELD_DELIMITER = $this->detectDelimiter($path);
            $reader = new CsvReader($options);
        }

        $rows = [];
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();

                    if (count($rows) > self::MAX_ROWS + 50) {
                        break;
                    }
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    /**
     * Excel en espanol guarda los CSV con punto y coma.
     */
    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $firstLine = $handle ? (string) fgets($handle) : '';

        if ($handle) {
            fclose($handle);
        }

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    private function text(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        return trim((string) $value);
    }

    private static function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', Str::lower(Str::ascii($value))), '_');
    }
}
