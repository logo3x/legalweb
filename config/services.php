<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'notifications' => [
        'new_firm_emails' => env('NEW_FIRM_EMAILS', ''),
    ],

    'wompi' => [
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'events_secret' => env('WOMPI_EVENTS_SECRET'),
        'integrity_secret' => env('WOMPI_INTEGRITY_SECRET'),
        'sandbox' => env('WOMPI_SANDBOX', true),
        'origin' => env('WOMPI_ORIGIN', 'legalweb'),
        'base_url' => env('WOMPI_SANDBOX', true)
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1',
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        // Alias que Google mantiene apuntando al modelo flash vigente.
        'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'max_candidates' => (int) env('GEMINI_MAX_CANDIDATES', 3),
    ],

    'ai' => [
        // Proveedor que se intenta primero: "openrouter" (privacidad) o "gemini" (calidad, plan gratuito puede entrenar).
        'preferred_provider' => env('AI_PREFERRED_PROVIDER', 'openrouter'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL'),
        'base_url' => 'https://openrouter.ai/api/v1',
        'max_candidates' => (int) env('OPENROUTER_MAX_CANDIDATES', 5),
        // "deny" solo enruta a proveedores que no almacenan ni entrenan con los datos enviados.
        'data_collection' => env('OPENROUTER_DATA_COLLECTION', 'deny'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),

        // Content SIDs (HX...) aprobados por Meta. Las claves llevan puntos,
        // asi que se leen con config('services.twilio.templates')[$key].
        'templates' => [
            'reminder.due.lawyer' => env('TWILIO_TPL_REMINDER_DUE_LAWYER'),
            'deadline.lawyer' => env('TWILIO_TPL_DEADLINE_LAWYER'),
            'tyba.new_event.lawyer' => env('TWILIO_TPL_TYBA_NEW_EVENT_LAWYER'),
            'document.received.lawyer' => env('TWILIO_TPL_DOCUMENT_RECEIVED_LAWYER'),
            'portal.access.lawyer' => env('TWILIO_TPL_PORTAL_ACCESS_LAWYER'),
        ],
    ],

];
