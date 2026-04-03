<?php

namespace App\Models;

use Database\Factories\FirmFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name',
    'nit',
    'legal_name',
    'email',
    'phone',
    'address',
    'city',
    'department',
    'logo_path',
    'website',
    'description',
    'onboarding_completed',
])]
class Firm extends Model
{
    /** @use HasFactory<FirmFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'onboarding_completed' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return Storage::url($this->logo_path);
        }

        return '/images/default-firm-logo.svg';
    }

    public function realCasesCount(): int
    {
        return $this->legalCases()->where('is_demo', false)->count();
    }

    public function realClientsCount(): int
    {
        return $this->clients()->where('is_demo', false)->count();
    }

    public function canCreateCase(): bool
    {
        $subscription = $this->activeSubscription;
        $limit = $subscription ? $subscription->plan->max_cases : 5;

        if ($limit === 0) {
            return true;
        }

        return $this->realCasesCount() < $limit;
    }

    public function canCreateClient(): bool
    {
        $subscription = $this->activeSubscription;
        $limit = $subscription ? $subscription->plan->max_cases : 5;

        if ($limit === 0) {
            return true;
        }

        return $this->realClientsCount() < $limit;
    }

    public function casesRemaining(): int|string
    {
        $subscription = $this->activeSubscription;
        $limit = $subscription ? $subscription->plan->max_cases : 5;

        if ($limit === 0) {
            return 'Ilimitados';
        }

        return max(0, $limit - $this->realCasesCount());
    }

    public function clientsRemaining(): int|string
    {
        $subscription = $this->activeSubscription;
        $limit = $subscription ? $subscription->plan->max_cases : 5;

        if ($limit === 0) {
            return 'Ilimitados';
        }

        return max(0, $limit - $this->realClientsCount());
    }
}
