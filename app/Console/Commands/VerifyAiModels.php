<?php

namespace App\Console\Commands;

use App\Services\AIModelRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:verify-ai-models')]
#[Description('Consulta el catalogo de Gemini y OpenRouter, prueba los modelos y guarda los que responden')]
class VerifyAiModels extends Command
{
    public function handle(AIModelRegistry $registry): int
    {
        $verified = $registry->refresh();

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
