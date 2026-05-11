<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mass_email_campaigns')) {
            Schema::create('mass_email_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('subject');
                $table->text('body');
                $table->string('audience_type', 30)->default('all'); // all, by_plan, by_status, specific
                $table->json('audience_filters')->nullable();
                $table->json('audience_user_ids')->nullable();
                $table->string('status', 20)->default('borrador'); // borrador, programado, enviando, enviado, fallido
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->unsignedInteger('recipients_count')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->timestamps();

                $table->index(['status', 'scheduled_at']);
            });
        }

        if (! Schema::hasTable('mass_email_recipients')) {
            Schema::create('mass_email_recipients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('mass_email_campaigns')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('email');
                $table->string('status', 20)->default('pendiente'); // pendiente, enviado, fallido
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(['campaign_id', 'status']);
                $table->unique(['campaign_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mass_email_recipients');
        Schema::dropIfExists('mass_email_campaigns');
    }
};
