<?php

namespace App\Models;

use App\Notifications\DocumentRequestedNotification;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'legal_case_id',
    'case_event_id',
    'name',
    'description',
    'file_path',
    'file_type',
    'file_size',
    'uploaded_by',
    'responsible',
    'entity',
    'estimated_cost',
    'status',
    'priority',
    'due_date',
    'received_at',
    'external_url',
    'notes',
    'assigned_to',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'received_at' => 'date',
            'client_notified_at' => 'datetime',
            'estimated_cost' => 'decimal:2',
        ];
    }

    /**
     * Estados en los que el documento esta a la espera de que el cliente lo aporte.
     */
    public const CLIENT_REQUEST_STATUSES = ['pendiente', 'solicitado'];

    protected static function booted(): void
    {
        static::saved(function (Document $document): void {
            $document->notifyClientIfRequested();
        });
    }

    /**
     * Avisa al cliente (una sola vez por documento) que debe aportar este documento.
     */
    public function notifyClientIfRequested(): void
    {
        if ($this->client_notified_at
            || $this->responsible !== 'cliente'
            || ! in_array($this->status, self::CLIENT_REQUEST_STATUSES, true)) {
            return;
        }

        $case = LegalCase::withoutGlobalScopes()->with('user.firm')->find($this->legal_case_id);

        if (! $case || $case->is_demo) {
            return;
        }

        $client = Client::withoutGlobalScopes()->find($case->client_id);

        if (! $client) {
            return;
        }

        $notification = new DocumentRequestedNotification($case, $this);

        // Sin canal disponible (sin autorizacion, sin telefono o sin Twilio) no se marca,
        // para poder avisar si el cliente autoriza mas adelante.
        if ($notification->via($client) === []) {
            return;
        }

        $this->client_notified_at = now();
        $this->saveQuietly();

        $client->notify($notification);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function caseEvent(): BelongsTo
    {
        return $this->belongsTo(CaseEvent::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
