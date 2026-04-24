<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar campos de seguimiento a documents
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('responsible', ['cliente', 'abogado', 'firma', 'contraparte', 'juzgado', 'otro'])->default('cliente')->after('description');
            $table->string('entity')->nullable()->after('responsible');
            $table->decimal('estimated_cost', 12, 2)->nullable()->after('entity');
            $table->enum('status', ['pendiente', 'solicitado', 'en_tramite', 'recibido', 'no_aplica'])->default('recibido')->after('estimated_cost');
            $table->enum('priority', ['baja', 'media', 'alta', 'urgente'])->default('media')->after('status');
            $table->date('due_date')->nullable()->after('priority');
            $table->date('received_at')->nullable()->after('due_date');
            $table->string('external_url')->nullable()->after('file_size');
            $table->text('notes')->nullable()->after('external_url');
            $table->foreignId('assigned_to')->nullable()->after('uploaded_by')->constrained('users')->nullOnDelete();

            $table->index(['legal_case_id', 'status']);
        });

        // Hacer file_path nullable (antes era required, ahora opcional)
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->foreignId('uploaded_by')->nullable()->change();
        });

        // Migrar datos existentes de document_requirements a documents
        if (Schema::hasTable('document_requirements')) {
            $requirements = DB::table('document_requirements')->get();

            foreach ($requirements as $req) {
                DB::table('documents')->insert([
                    'legal_case_id' => $req->legal_case_id,
                    'name' => $req->name,
                    'description' => $req->description,
                    'responsible' => $req->responsible,
                    'entity' => $req->entity,
                    'estimated_cost' => $req->estimated_cost,
                    'status' => $req->status,
                    'priority' => $req->priority,
                    'due_date' => $req->due_date,
                    'received_at' => $req->received_at,
                    'external_url' => $req->external_url,
                    'notes' => $req->notes,
                    'assigned_to' => $req->assigned_to,
                    'created_at' => $req->created_at,
                    'updated_at' => $req->updated_at,
                ]);
            }

            Schema::dropIfExists('document_requirements');
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn([
                'responsible', 'entity', 'estimated_cost', 'status',
                'priority', 'due_date', 'received_at', 'external_url',
                'notes', 'assigned_to',
            ]);
        });
    }
};
