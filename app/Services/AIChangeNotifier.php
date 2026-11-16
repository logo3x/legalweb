<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Compara el estado de la IA con el ultimo que se informo y envia un correo de novedades al
 * superadmin cuando cambia el modelo principal, aparecen o salen modelos, o la IA se cae o
 * se recupera. Como maximo un correo por hora: los cambios se acumulan hasta el siguiente.
 */
class AIChangeNotifier
{
    public const SNAPSHOT_KEY = 'ai.change_notifier.snapshot';

    public const MAIL_LOCK_KEY = 'ai.change_notifier.mail_lock';

    public function __construct(
        private AIModelRegistry $registry,
        private AIModelHealth $health,
    ) {}

    /**
     * Estado actual: modelo principal (el primero de la lista que no esta fallando),
     * modelos disponibles y si hay caida total.
     *
     * @return array{primary: ?string, primary_label: ?string, models: array<string, string>, outage: bool}
     */
    public function snapshot(): array
    {
        $options = $this->registry->options();
        $health = $this->health->all();
        $primary = null;

        foreach ($this->registry->attemptOrder() as $key) {
            if (($health[$key]['ok'] ?? null) !== false) {
                $primary = $key;
                break;
            }
        }

        $allChecked = $options !== [] && collect(array_keys($options))->every(fn (string $key): bool => isset($health[$key]));

        return [
            'primary' => $primary,
            'primary_label' => $primary ? ($options[$primary] ?? $primary) : null,
            'models' => $options,
            'outage' => $options === [] || ($allChecked && $primary === null),
        ];
    }

    /**
     * Revisa si hubo novedades y, si las hay, avisa. Devuelve las lineas enviadas.
     *
     * @return list<string>
     */
    public function check(): array
    {
        $current = $this->snapshot();
        $previous = Cache::get(self::SNAPSHOT_KEY);

        // Primera revision: se toma como punto de partida sin enviar nada.
        if (! is_array($previous)) {
            Cache::forever(self::SNAPSHOT_KEY, $current);

            return [];
        }

        $lines = $this->describeChanges($previous, $current);

        if ($lines === []) {
            return [];
        }

        // Un correo por hora como maximo; si no se envia ahora, el resumen sale en la siguiente revision.
        if (! Cache::add(self::MAIL_LOCK_KEY, true, now()->addHour())) {
            return [];
        }

        $title = match (true) {
            $current['outage'] && ! $previous['outage'] => 'la IA no está respondiendo',
            ! $current['outage'] && $previous['outage'] => 'la IA volvió a responder',
            $current['primary'] !== $previous['primary'] => 'cambió el modelo de IA principal',
            default => 'cambios en los modelos de IA',
        };

        SystemNews::notifySuperadmins(ucfirst($title), $lines, 'Abrir Uso de IA', url('/admin/uso-ia'));
        Cache::forever(self::SNAPSHOT_KEY, $current);

        return $lines;
    }

    /**
     * @param  array{primary: ?string, primary_label: ?string, models: array<string, string>, outage: bool}  $previous
     * @param  array{primary: ?string, primary_label: ?string, models: array<string, string>, outage: bool}  $current
     * @return list<string>
     */
    private function describeChanges(array $previous, array $current): array
    {
        $lines = [];
        $health = $this->health->all();

        if ($current['outage'] && ! $previous['outage']) {
            $lines[] = 'Ningún modelo de IA está respondiendo. LegalWeb sigue buscando modelos nuevos de forma automática; mientras tanto, los resúmenes y borradores con IA no estarán disponibles.';
        } elseif (! $current['outage'] && $previous['outage']) {
            $lines[] = 'La IA volvió a responder. Modelo principal: '.$current['primary_label'].'.';
        }

        if (! $current['outage'] && ! $previous['outage'] && $current['primary'] !== $previous['primary'] && $current['primary'] !== null) {
            $before = $previous['primary_label'] ?? 'ninguno';
            $reason = $this->reasonFor($previous['primary'], $current['models'], $health);
            $lines[] = "El modelo principal cambió de {$before} a {$current['primary_label']}{$reason}.";
        }

        $added = array_diff_key($current['models'], $previous['models']);
        $removed = array_diff_key($previous['models'], $current['models']);

        if ($added !== []) {
            $lines[] = 'Modelos nuevos disponibles: '.implode(', ', $added).'.';
        }

        if ($removed !== []) {
            $lines[] = 'Modelos que dejaron de estar disponibles: '.implode(', ', $removed).'.';
        }

        return $lines;
    }

    /**
     * @param  array<string, string>  $models
     * @param  array<string, array<string, mixed>>  $health
     */
    private function reasonFor(?string $previousPrimary, array $models, array $health): string
    {
        if ($previousPrimary === null) {
            return '';
        }

        if (! isset($models[$previousPrimary])) {
            return ' porque el anterior fue retirado por el proveedor';
        }

        $entry = $health[$previousPrimary] ?? null;

        if (($entry['ok'] ?? null) === false) {
            $status = $entry['status'] ? " (error {$entry['status']})" : '';

            return " porque el anterior dejó de responder{$status}";
        }

        return '';
    }
}
