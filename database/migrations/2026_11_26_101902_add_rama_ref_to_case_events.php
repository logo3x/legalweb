<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada actuacion de la Rama se identifica por su propio id (rama_ref) y no por titulo + dia:
 * dos actuaciones con el mismo titulo el mismo dia (por ejemplo dos "Fijacion estado") ya no
 * se funden en una. Los eventos escritos por el abogado no tienen rama_ref.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_events', function (Blueprint $table) {
            $table->string('rama_ref', 64)->nullable()->after('event_day');
        });

        // Primero los indices nuevos (MySQL exige un indice sobre la llave foranea legal_case_id).
        Schema::table('case_events', function (Blueprint $table) {
            $table->unique(['legal_case_id', 'rama_ref'], 'ce_case_rama_ref_uniq');
            $table->index(['legal_case_id', 'title', 'event_day'], 'ce_case_title_day_idx');
        });

        if (Schema::hasIndex('case_events', 'ce_case_title_day_uniq')) {
            Schema::table('case_events', function (Blueprint $table) {
                $table->dropUnique('ce_case_title_day_uniq');
            });
        }
    }

    public function down(): void
    {
        Schema::table('case_events', function (Blueprint $table) {
            $table->dropUnique('ce_case_rama_ref_uniq');
            $table->dropIndex('ce_case_title_day_idx');
            $table->dropColumn('rama_ref');
        });
    }
};
