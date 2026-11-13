<?php

use App\Models\User;
use App\Notifications\ProductUpdatesNotification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

/**
 * Anuncio unico en la campana del panel: novedades de noviembre de 2026 y
 * actualizacion de Terminos (v3.3) y Politica de Privacidad.
 * El correo con el detalle se envia aparte desde Correos masivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasTable('users')) {
            return;
        }

        $novedades = new ProductUpdatesNotification(
            title: 'Novedades en LegalWeb',
            body: 'Avisos por WhatsApp, vigilancia de clientes por cedula o NIT, procesos de SAMAI, IA que oculta los datos personales, busqueda rapida con Ctrl+K y analitica de despachos.',
            actionLabel: 'Ver mi plan y novedades',
            actionUrl: url('/admin/planes'),
        );

        $terminos = new ProductUpdatesNotification(
            title: 'Actualizamos los Terminos y la Politica de Privacidad',
            body: 'Version 3.3: WhatsApp, vigilancia por documento, SAMAI y ocultamiento de datos personales en la IA. Si tiene preguntas, escribanos.',
            actionLabel: 'Leer los terminos',
            actionUrl: url('/portal/terminos'),
        );

        User::query()->chunkById(200, function ($users) use ($novedades, $terminos): void {
            Notification::sendNow($users, $novedades);
            Notification::sendNow($users, $terminos);
        });
    }

    public function down(): void
    {
        // Las notificaciones enviadas no se retiran.
    }
};
