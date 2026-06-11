<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'grace_until')) {
                $table->timestamp('grace_until')->nullable()->after('ends_at');
            }
            if (! Schema::hasColumn('subscriptions', 'last_warning_sent_at')) {
                $table->timestamp('last_warning_sent_at')->nullable()->after('grace_until');
            }
            if (! Schema::hasColumn('subscriptions', 'warning_stage')) {
                // null | upcoming | overdue | grace_ending | suspended
                $table->string('warning_stage', 30)->nullable()->after('last_warning_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            foreach (['grace_until', 'last_warning_sent_at', 'warning_stage'] as $col) {
                if (Schema::hasColumn('subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
