<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (! Schema::hasColumn('reminders', 'notified_at')) {
                $table->timestamp('notified_at')->nullable()->after('completed_at');
                $table->index(['is_completed', 'remind_at', 'notified_at'], 'reminders_pending_notify_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (Schema::hasColumn('reminders', 'notified_at')) {
                $table->dropIndex('reminders_pending_notify_idx');
                $table->dropColumn('notified_at');
            }
        });
    }
};
