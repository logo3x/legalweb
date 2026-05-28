<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            if (! Schema::hasColumn('firms', 'tracking_status')) {
                $table->string('tracking_status', 20)->default('activo')->after('onboarding_completed');
            }
            if (! Schema::hasColumn('firms', 'tracking_tags')) {
                $table->json('tracking_tags')->nullable()->after('tracking_status');
            }
            if (! Schema::hasColumn('firms', 'tracking_notes')) {
                $table->text('tracking_notes')->nullable()->after('tracking_tags');
            }
            if (! Schema::hasColumn('firms', 'last_admin_review_at')) {
                $table->timestamp('last_admin_review_at')->nullable()->after('tracking_notes');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('tour_completed_at');
            }
            if (! Schema::hasColumn('users', 'login_count')) {
                $table->unsignedInteger('login_count')->default(0)->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('firms', function (Blueprint $table) {
            foreach (['tracking_status', 'tracking_tags', 'tracking_notes', 'last_admin_review_at'] as $col) {
                if (Schema::hasColumn('firms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['last_login_at', 'login_count'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
