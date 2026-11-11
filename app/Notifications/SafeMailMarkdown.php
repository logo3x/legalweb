<?php

namespace App\Notifications;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Mail\Markdown;
use Illuminate\Support\HtmlString;

/**
 * Convierte las lineas de los correos de notificacion a HTML sin permitir HTML
 * ni enlaces que vengan en los datos (nombres de clientes, actuaciones, datos de
 * la Rama Judicial o SAMAI). Se conserva el formato basico (**negrita**, listas).
 *
 * Solo el contenido marcado a proposito como HTML (Htmlable) se muestra tal cual.
 */
class SafeMailMarkdown
{
    public static function render(mixed $line): HtmlString
    {
        if ($line instanceof Htmlable) {
            return new HtmlString($line->toHtml());
        }

        // "[" y "<" escapados: sin enlaces Markdown, autoenlaces ni etiquetas desde los datos.
        $text = str_replace(['[', '<'], ['\[', '\<'], (string) $line);

        return new HtmlString(
            Markdown::converter(['html_input' => 'escape', 'allow_unsafe_links' => false])
                ->convert($text)
                ->getContent()
        );
    }
}
