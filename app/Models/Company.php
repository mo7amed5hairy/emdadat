<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'created_by'])]
class Company extends Model
{
    use HasFactory;

    public function pricedDeliveryPermits(): HasMany
    {
        return $this->hasMany(PricedDeliveryPermit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    /**
     * كل الشركات تظهر لكل المستخدمين، تبقى بدون تقييد.
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query;
    }
}