<?php

namespace App\Console\Commands;

use App\Services\AIModelHealth;
use App\Services\AIModelRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:verify-ai-models {--budget=0 : Segundos maximos por ejecucion (0 = hasta terminar). En la web la busqueda sigue en tramos con el cron de cada minuto.}')]
#[Description('Consulta el catalogo de Gemini, OpenRouter y NVIDIA, prueba los modelos y guarda los que responden')]
class VerifyAiModels extends Command
{
    public function handle(AIModelRegistry $registry, AIModelHealth $health): int
    {
        $budget = (int) $this->option('budget');

        if ($budget > 0) {
            $step = $registry->refreshStep($budget);

            if ($step['busy']) {
                $this->info("Ya hay una busqueda de modelos en curso ({$step['probed']} de {$step['total']} candidatos probados).");

                return self::SUCCESS;
            }

            if (! $step['done']) {
                $this->info("Busqueda en curso: {$step['probed']} de {$step['total']} candidatos probados, ".count($step['verified']).' modelo(s) responden hasta ahora. Continua sola con el cron de cada minuto.');

                return self::SUCCESS;
            }

            $verified = $step['verified'];
        } else {
            $verified = $registry->refresh();
        }

        // Correo de novedades si la verificacion cambio el modelo principal o la lista.
        $health->notifyChanges();

        if (! $verified) {
            $this->error('Ningun modelo respondio. Se conserva la lista anterior.');

            return self::FAILURE;
        }

        $this->info(count($verified).' modelo(s) disponibles:');

        foreach ($verified as $model) {
            $this->line("  - {$model['label']} [{$model['key']}]");
        }

        return self::SUCCESS;
    }
}
