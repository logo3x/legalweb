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

    public function canCreateCase(): bool
    {
        $subscription = $this->activeSubscription;

        if (! $subscription) {
            return $this->legalCases()->count() < 5;
        }

        $plan = $subscription->plan;

        if ($plan->max_cases === 0) {
            return true;
        }

        return $this->legalCases()->count() < $plan->max_cases;
    }

    public function casesRemaining(): int|string
    {
        $subscription = $this->activeSubscription;

        if (! $subscription) {
            return max(0, 5 - $this->legalCases()->count());
        }

        $plan = $subscription->plan;

        if ($plan->max_cases === 0) {
            return 'Ilimitados';
        }

        return max(0, $plan->max_cases - $this->legalCases()->count());
    }
}
