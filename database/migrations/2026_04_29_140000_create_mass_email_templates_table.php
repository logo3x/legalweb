<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mass_email_templates')) {
            Schema::create('mass_email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category', 50)->default('general');
                $table->string('subject');
                $table->text('body');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'category']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mass_email_templates');
    }
};
