<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'workplace', 'message', 'rating'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'rating' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved->value)->whereNotNull('approved_at');
    }
}
