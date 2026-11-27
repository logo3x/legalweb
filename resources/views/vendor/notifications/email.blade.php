{{--
    Plantilla de los correos armados con MailMessage (line, action, greeting...).
    Usa el mismo marco que los demas correos: resources/views/components/mail.
--}}
@php
    $tone = match ($level ?? 'info') {
        'error' => 'danger',
        'success' => 'success',
        default => null,
    };
    $firstLine = collect($introLines ?? [])->first();
    $preheader = is_string($firstLine) ? \Illuminate\Support\Str::limit(strip_tags(str_replace(['**', '__'], '', $firstLine)), 110) : null;
@endphp
<x-mail.layout
    :title="$greeting ?? config('app.name')"
    :preheader="$preheader"
    :firm-logo="$firmLogo ?? null"
    :firm-name="$firmName ?? null"
    :audience="$audience ?? 'account'"
>
    <h1 style="margin:0 0 18px;font-size:22px;line-height:1.3;font-weight:700;color:#0F172A;letter-spacing:-0.01em;">
        {{ filled($greeting ?? null) ? $greeting : (($level ?? '') === 'error' ? 'Atención' : 'Hola') }}
    </h1>

    @foreach($introLines as $line)
        @if($loop->first && $tone)
            <x-mail.notice :tone="$tone">{{ \App\Notifications\SafeMailMarkdown::render($line) }}</x-mail.notice>
        @else
            <div class="lw-md" style="margin:0 0 14px;">{{ \App\Notifications\SafeMailMarkdown::render($line) }}</div>
        @endif
    @endforeach

    @isset($actionText)
        <x-mail.button :href="$actionUrl">{{ $actionText }}</x-mail.button>
    @endisset

    @foreach($outroLines as $line)
        <div class="lw-md" style="margin:0 0 14px;">{{ \App\Notifications\SafeMailMarkdown::render($line) }}</div>
    @endforeach

    @if(filled($salutation ?? null))
        <div class="lw-md" style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#475569;">{{ \App\Notifications\SafeMailMarkdown::render($salutation) }}</div>
    @endif

    @isset($actionText)
        <p style="margin:22px 0 0;font-size:13px;line-height:1.55;color:#475569;">
            Si el botón no abre, copie este enlace en su navegador:<br>
            <a href="{{ $actionUrl }}" style="color:#1D4ED8;word-break:break-all;">{{ $displayableActionUrl ?? $actionUrl }}</a>
        </p>
    @endisset
</x-mail.layout>
