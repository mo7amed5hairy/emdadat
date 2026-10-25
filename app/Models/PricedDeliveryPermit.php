<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delivery_amount',
    'supply_request_number',
    'delivery_permit_number',
    'issue_date',
    'company_id',
    'form_date',
    'status',
    'fully_delivered',
    'created_by',
    'assigned_to',
])]
class PricedDeliveryPermit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'delivery_amount' => 'decimal:2',
            'issue_date' => 'date:Y-m-d',
            'form_date' => 'date:Y-m-d',
            'fully_delivered' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplyRequest(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_number', 'supply_request_number');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    /**
     * المستخدم الذي يتبع له الإذن (المالك).
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault();
    }

    /**
     * تقييد النتائج بالمستخدمين المسموح للمستخدم المحدد بالاطلاع على بياناتهم.
     */
    public function scopeVisibleTo($query, User $user)
    {
        $ids = $user->visibleUserIds();

        if ($ids === null) {
            return $query;
        }

        return $query->where(function ($q) use ($ids) {
            $q->whereIn('created_by', $ids)
                ->orWhereIn('assigned_to', $ids);
        });
    }
}