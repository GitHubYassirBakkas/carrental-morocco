<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait ProtectsHistoricalRecords
{
    protected static function bootProtectsHistoricalRecords(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            throw new RuntimeException(static::historicalRecordDeleteMessage());
        });
    }

    protected static function historicalRecordDeleteMessage(): string
    {
        return class_basename(static::class) . ' records are historical business records and cannot be deleted.';
    }
}
