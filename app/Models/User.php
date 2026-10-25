<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'password', 'is_admin', 'category', 'hospital_id', 'department_id', 'input_permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** فئات المستخدمين المطابقة لأنواع الطلبات في طلبات الإمداد. */
    public const CATEGORIES = ['مستلزمات', 'كيماويات', 'أدوية'];

    /**
     * مفاتيح الحقول القابلة للإدخال في شاشة طلبات الإمداد.
     *
     * @return array<int, string>
     */
    public static function supplyRequestFields(): array
    {
        return [
            'supply_request_number',
            'serial',
            'hospital_id',
            'department_id',
            'financial_link',
            'financial_category',
            'amount',
            'request_date',
            'fiscal_year',
        ];
    }

    /**
     * مفاتيح الحقول القابلة للإدخال في شاشة أذون التسليم المسعّرة.
     *
     * @return array<int, string>
     */
    public static function deliveryPermitFields(): array
    {
        return [
            'delivery_amount',
            'supply_request_number',
            'delivery_permit_number',
            'issue_date',
            'company_id',
            'form_date',
            'status',
            'fully_delivered',
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'input_permissions' => 'array',
        ];
    }

    /**
     * الحقول المسموح للمستخدم بإدخالها على الشاشة المحددة.
     * تُرجع null إذا كان المستخدم غير مقيد على هذه الشاشة (كل الحقول مسموحة)،
     * أو مصفوفة بالمفاتيح المسموحة (قد تكون فارغة إذا لا يوجد أي حقل).
     *
     * @return array<int, string>|null
     */
    public function permittedInputFields(string $screen): ?array
    {
        if ($this->is_admin) {
            return null;
        }

        $perms = is_array($this->input_permissions) ? $this->input_permissions : null;
        if (! $perms || ! array_key_exists($screen, $perms)) {
            return null;
        }

        return array_values(array_filter((array) ($perms[$screen]['fields'] ?? [])));
    }

    /**
     * هل المستخدم مقيد على الشاشة المحددة (أي هل توجد إعدادات إدخال له)؟
     */
    public function isInputRestricted(string $screen): bool
    {
        if ($this->is_admin) {
            return false;
        }

        $perms = is_array($this->input_permissions) ? $this->input_permissions : null;

        return (bool) ($perms && array_key_exists($screen, $perms));
    }

    /**
     * هل يُسمح للمستخدم بإدخال بيانات على الشاشة المحددة أصلًا؟
     */
    public function canInputOn(string $screen): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $perms = is_array($this->input_permissions) ? $this->input_permissions : null;
        if (! $perms || ! array_key_exists($screen, $perms)) {
            return true;
        }

        return (bool) ($perms[$screen]['permitted'] ?? false);
    }

    /**
     * الشاشات التي يملك هذا المستخدم صلاحية إدخال فيها (permitted = true).
     *
     * تُرجع null إذا كان المستخدم غير مقيد (مدير أو بدون input_permissions) — أي يدخل كل الصفحات.
     * تُرجع مصفوفة (قد تكون فارغة) إذا كان مقيدًا — يُسمح له فقط بشاشاتها.
     *
     * @return array<int, string>|null
     */
    public function permittedScreens(): ?array
    {
        if ($this->is_admin) {
            return null;
        }

        $perms = is_array($this->input_permissions) ? $this->input_permissions : null;
        if (! $perms) {
            return null;
        }

        $screens = [];
        foreach ($perms as $screen => $config) {
            if (is_array($config) && ($config['permitted'] ?? false)) {
                $screens[] = $screen;
            }
        }

        return $screens;
    }

    /**
     * المستخدمون الذين يملك هذا المستخدم صلاحية الاطلاع على بياناتهم.
     */
    public function viewableUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'user_viewables',
            'user_id',
            'viewable_user_id'
        )->withTimestamps();
    }

    /**
     * المستخدمون الذين يملكون صلاحية الاطلاع على بيانات هذا المستخدم.
     */
    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'user_viewables',
            'viewable_user_id',
            'user_id'
        )->withTimestamps();
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * المعرّفات التي يُسمح لهذا المستخدم برؤية بياناتها.
     * يشمل نفسه وأي مستخدمين تم تفويضه لرؤيتهم.
     * يُرجع null إذا كان مديراً يرى كل البيانات.
     *
     * @return array<int>|null
     */
    public function visibleUserIds(): ?array
    {
        if ($this->is_admin) {
            return null;
        }

        return collect([$this->id])
            ->concat($this->viewableUsers->pluck('id'))
            ->unique()
            ->values()
            ->all();
    }
}