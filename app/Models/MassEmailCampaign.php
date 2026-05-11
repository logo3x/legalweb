<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'subject',
    'body',
    'audience_type',
    'audience_filters',
    'audience_user_ids',
    'status',
    'scheduled_at',
    'sent_at',
    'recipients_count',
    'sent_count',
    'failed_count',
])]
class MassEmailCampaign extends Model
{
    public const AUDIENCE_TYPES = [
        'all' => 'Todos los usuarios',
        'by_plan' => 'Por plan',
        'by_status' => 'Por estado de firma',
        'specific' => 'Usuarios especificos',
    ];

    public const STATUSES = [
        'borrador' => 'Borrador',
        'programado' => 'Programado',
        'enviando' => 'Enviando',
        'enviado' => 'Enviado',
        'fallido' => 'Fallido',
    ];

    protected function casts(): array
    {
        return [
            'audience_filters' => 'array',
            'audience_user_ids' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MassEmailRecipient::class, 'campaign_id');
    }

    public function resolveRecipients(): Collection
    {
        $query = User::query()->whereNotNull('email');

        switch ($this->audience_type) {
            case 'by_plan':
                $planSlugs = $this->audience_filters['plans'] ?? [];
                if (! empty($planSlugs)) {
                    $query->whereHas('firm', function ($q) use ($planSlugs) {
                        $q->whereHas('activeSubscription.plan', function ($p) use ($planSlugs) {
                            $p->whereIn('slug', $planSlugs);
                        });
                    });
                }
                break;

            case 'by_status':
                $statuses = $this->audience_filters['statuses'] ?? [];
                if (! empty($statuses)) {
                    $query->whereHas('firm', function ($q) use ($statuses) {
                        $q->whereIn('tracking_status', $statuses);
                    });
                }
                break;

            case 'specific':
                $userIds = $this->audience_user_ids ?? [];
                $query->whereIn('id', $userIds);
                break;

            case 'all':
            default:
                // sin filtros, todos los usuarios
                break;
        }

        return $query->get();
    }
}
