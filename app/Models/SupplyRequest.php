<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'request_type',
    'supply_request_number',
    'serial',
    'hospital_id',
    'department_id',
    'financial_link',
    'financial_category',
    'amount',
    'request_date',
    'fiscal_year',
    'created_by',
    'assigned_to',
    'owner_processed_at',
])]
class SupplyRequest extends Model
{
    use HasFactory;

    /** أنواع الارتباط المالي الفرعية الخاصة بالارتباط من ميزانية. */
    public const BUDGET_CATEGORIES = ['مستلزمات طبية', 'خامات أدوية', 'خامات أخرى', 'دائنين', 'تبرعات'];

    /** أنواع الارتباط المالي الفرعية الخاصة بالارتباط من حسابات خاصة. */
    public const SPECIAL_ACCOUNTS = [
        'أشعة',
        'أشعة تحديث جهاز الرنين',
        'أطفال',
        'أطفال الشفة الأرنبية',
        'أكاديمية القلب',
        'الخدمات',
        'السكتة الدماغية',
        'السموم',
        'الطب النفسي',
        'العبور',
        'العيادات الخارجية',
        'الكوبلت',
        'الكوبلت صحة المرأة',
        'الكوبلت مبادرة الكبد',
        'إنزيمات وراثية',
        'دمرداش',
        'دمرداش ليزر',
        'رعاية استقبال طوارئ',
        'شؤون الأطباء',
        'صيدلية ثانى',
        'عين شمس',
        'عين شمس رعاية صدر',
        'عين شمس روماتيزم',
        'عين شمس كلى صناعي',
        'مسنين',
        'مكافحة العدوى',
        'مكافحة العدوى الوحدة المركزية',
        'مناظير الجهاز الهضمي',
        'نساء',
        'نساء إخصاب معملي',
        'نساء تشخيص مبكر أورام مناظير',
        'نساء مستلزمات',
        'نساء موجات ديناميكية رعاية جنين',
        'وراثة أطفال',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_date' => 'date:Y-m-d',
            'owner_processed_at' => 'datetime',
        ];
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    /**
     * المستخدم الذي يتبع له الطلب (صاحب الطلب).
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault();
    }

    /**
     * أذون التسليم المسعّرة المرتبطة بطلب الإمداد هذا (بنفس رقم الإمداد).
     */
    public function deliveryPermits(): HasMany
    {
        return $this->hasMany(PricedDeliveryPermit::class, 'supply_request_number', 'supply_request_number');
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