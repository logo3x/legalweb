<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\FirmSettings;
use App\Filament\Pages\TeamMembers;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\LegalCases\LegalCaseResource;
use App\Models\AiUsageLog;
use App\Models\Client;
use App\Models\FirmInvitation;
use App\Models\LegalCase;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

/**
 * Lista de primeros pasos para firmas nuevas: lleva al abogado hasta el momento en que
 * LegalWeb le resulta util (importar un proceso, compartir el portal, etc.).
 * Se oculta cuando completa todo o cuando el usuario la cierra.
 */
class GettingStarted extends Widget
{
    protected string $view = 'filament.widgets.getting-started';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    /**
     * Se muestra apenas abre el escritorio, sin esperar a que cargue el resto.
     */
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user?->firm_id || ! $user->isAdmin() || Cache::get(self::dismissKey($user->id))) {
            return false;
        }

        return collect((new static)->getSteps())->contains(fn (array $step): bool => ! $step['done']);
    }

    public function dismiss(): void
    {
        Cache::forever(self::dismissKey((int) auth()->id()), true);
        $this->redirect(url('/admin'));
    }

    /**
     * @return list<array{title: string, description: string, done: bool, url: string, cta: string}>
     */
    public function getSteps(): array
    {
        $user = auth()->user();
        $firm = $user->firm;
        $firmId = $firm->getKey();
        $limits = $firm->planLimits();

        $realCases = LegalCase::withoutGlobalScopes()->where('firm_id', $firmId)->whereNull('deleted_at')->where('is_demo', false);
        $realClients = Client::withoutGlobalScopes()->where('firm_id', $firmId)->whereNull('deleted_at')->where('is_demo', false);

        $steps = [
            [
                'title' => 'Complete los datos de su firma',
                'description' => 'NIT, teléfono y logo aparecen en los reportes y en el portal del cliente.',
                'done' => filled($firm->nit) && filled($firm->phone),
                'url' => FirmSettings::getUrl(),
                'cta' => 'Ir a Mi Firma',
            ],
            [
                'title' => 'Importe su primer proceso',
                'description' => 'Pegue el radicado y traemos el expediente desde la Rama Judicial o SAMAI. Desde esta noche lo vigilamos.',
                'done' => (clone $realCases)->whereNotNull('external_case_number')->where('external_case_number', '!=', '')->exists(),
                'url' => LegalCaseResource::getUrl('index'),
                'cta' => 'Importar proceso',
            ],
            [
                'title' => 'Registre a un cliente',
                'description' => 'Con su cédula o NIT podemos avisarle si aparece en procesos nuevos.',
                'done' => (clone $realClients)->exists(),
                'url' => ClientResource::getUrl('create'),
                'cta' => 'Nuevo cliente',
            ],
            [
                'title' => 'Comparta el portal con un cliente',
                'description' => 'Su cliente consulta el estado del proceso con un enlace seguro y deja de llamarle para preguntar.',
                'done' => (clone $realCases)->where('portal_enabled', true)->exists(),
                'url' => LegalCaseResource::getUrl('index'),
                'cta' => 'Abrir un caso',
            ],
            [
                'title' => 'Pruebe el Asistente IA',
                'description' => 'Pida el resumen de un caso o un borrador de memorial. Los datos personales se ocultan antes de enviarse.',
                'done' => AiUsageLog::where('firm_id', $firmId)->where('success', true)->exists(),
                'url' => LegalCaseResource::getUrl('index'),
                'cta' => 'Abrir un caso',
            ],
        ];

        if ($limits->hasClientMonitoring()) {
            $steps[] = [
                'title' => 'Active la vigilancia de un cliente',
                'description' => 'Cada noche lo buscamos por cédula o NIT y le avisamos si lo demandan.',
                'done' => (clone $realClients)->where('process_monitoring_enabled', true)->exists(),
                'url' => ClientResource::getUrl('index'),
                'cta' => 'Ver clientes',
            ];
        }

        if (($limits->maxUsers() ?? 2) > 1) {
            $steps[] = [
                'title' => 'Invite a su equipo',
                'description' => 'Cada colaborador ve solo los casos que usted le asigne.',
                'done' => User::where('firm_id', $firmId)->count() > 1 || FirmInvitation::where('firm_id', $firmId)->exists(),
                'url' => TeamMembers::getUrl(),
                'cta' => 'Invitar',
            ];
        }

        return $steps;
    }

    private static function dismissKey(int $userId): string
    {
        return "getting-started.dismissed.{$userId}";
    }
}
