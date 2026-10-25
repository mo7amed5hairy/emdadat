<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'created_by'])]
class FinancialLink extends Model
{
    use HasFactory;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    public function types(): HasMany
    {
        return $this->hasMany(FinancialLinkType::class);
    }

    /**
     * كل الارتباطات المالية تظهر لكل المستخدمين، تبقى بدون تقييد.
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query;
    }
}