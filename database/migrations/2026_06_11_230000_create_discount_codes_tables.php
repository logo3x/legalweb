<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('discount_codes')) {
            Schema::create('discount_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('description')->nullable();
                $table->string('type', 20);              // 'percent' | 'fixed'
                $table->unsignedInteger('amount');        // % (1-100) o monto COP
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('current_uses')->default(0);
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_until')->nullable();
                $table->foreignId('applies_to_plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('discount_redemptions')) {
            Schema::create('discount_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('discount_code_id')->constrained()->cascadeOnDelete();
                $table->foreignId('firm_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->unsignedInteger('original_amount');   // precio antes del descuento
                $table->unsignedInteger('discount_amount');   // descuento aplicado en COP
                $table->unsignedInteger('final_amount');      // precio final cobrado
                $table->string('wompi_transaction_id')->nullable();
                $table->timestamp('redeemed_at')->useCurrent();
                $table->timestamps();

                $table->index(['firm_id', 'redeemed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_redemptions');
        Schema::dropIfExists('discount_codes');
    }
};
