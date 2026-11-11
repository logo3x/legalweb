<?php

namespace App\Notifications\Messages;

class WhatsAppMessage
{
    /**
     * @param  array<int, string>  $variables  Variables posicionales de la plantilla (1..n).
     */
    public function __construct(
        public string $template,
        public array $variables,
        public string $fallbackText,
    ) {}
}
