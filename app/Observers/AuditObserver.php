<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function updated(Model $model): void
    {
        $new = collect($model->getChanges())->except('updated_at');
        $old = collect($model->getPrevious())->only($new->keys());

        $changes = $new->mapWithKeys(fn ($value, $field) => [
            $field => ['old' => $old->get($field), 'new' => $value],
        ])->all();

        if ($changes === []) {
            return;
        }

        AuditLog::create([
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'user_id' => auth()->user()?->id,
            'changes' => $changes,
        ]);
    }
}
