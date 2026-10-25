<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['financial_link_id', 'name', 'created_by'])]
class FinancialLinkType extends Model
{
    use HasFactory;

    public function financialLink(): BelongsTo
    {
        return $this->belongsTo(FinancialLink::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }
}