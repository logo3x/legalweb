<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('firm_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role', 20)->default('abogado')->after('email');
            $table->string('google_id')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('firm_id');
            $table->dropColumn(['role', 'google_id', 'avatar']);
        });
    }
};
