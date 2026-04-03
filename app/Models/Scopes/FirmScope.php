<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FirmScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user || ! $user->firm_id) {
            return;
        }

        if ($user->role === 'superadmin') {
            return;
        }

        if ($model->getTable() === 'clients' || $model->getTable() === 'legal_cases') {
            $builder->where($model->getTable().'.firm_id', $user->firm_id);
        }
    }
}
