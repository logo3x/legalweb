<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('case_events')) {
            return;
        }

        // 1. Agregar columna event_day (DATE) si no existe
        if (! Schema::hasColumn('case_events', 'event_day')) {
            Schema::table('case_events', function (Blueprint $table) {
                $table->date('event_day')->nullable()->after('event_date');
            });
        }

        // 2. Backfill: poblar event_day desde event_date
        DB::statement('UPDATE case_events SET event_day = DATE(event_date) WHERE event_day IS NULL');

        // 3. Colapsar duplicados existentes por (legal_case_id, title, event_day)
        //    Para cada grupo con > 1: conservamos el id mas bajo y borramos el resto.
        //    Hacemos esto en PHP para evitar locks largos en MySQL viejo.
        $duplicates = DB::table('case_events')
            ->selectRaw('legal_case_id, title, event_day, MIN(id) as keep_id, COUNT(*) as cnt')
            ->whereNotNull('legal_case_id')
            ->whereNotNull('title')
            ->whereNotNull('event_day')
            ->groupBy('legal_case_id', 'title', 'event_day')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $g) {
            DB::table('case_events')
                ->where('legal_case_id', $g->legal_case_id)
                ->where('title', $g->title)
                ->where('event_day', $g->event_day)
                ->where('id', '!=', $g->keep_id)
                ->delete();
        }

        // 4. Indice UNICO a nivel BD para que sea imposible insertar duplicados
        //    de (caso, titulo, dia). Usa nombre corto por limite MySQL.
        $indexName = 'ce_case_title_day_uniq';
        $exists = collect(DB::select('SHOW INDEX FROM case_events WHERE Key_name = ?', [$indexName]))->isNotEmpty();
        if (! $exists) {
            Schema::table('case_events', function (Blueprint $table) use ($indexName) {
                $table->unique(['legal_case_id', 'title', 'event_day'], $indexName);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('case_events')) {
            return;
        }

        $indexName = 'ce_case_title_day_uniq';
        $exists = collect(DB::select('SHOW INDEX FROM case_events WHERE Key_name = ?', [$indexName]))->isNotEmpty();
        if ($exists) {
            Schema::table('case_events', function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }

        if (Schema::hasColumn('case_events', 'event_day')) {
            Schema::table('case_events', function (Blueprint $table) {
                $table->dropColumn('event_day');
            });
        }
    }
};
