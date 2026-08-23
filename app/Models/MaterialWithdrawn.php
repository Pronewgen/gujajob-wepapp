<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialWithdrawn extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MATERIAL_WITHDRAWN';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'mat_wd_code',
        'mat_wd_date',
        'org_id',
        'mat_wd_person',
        'status',
        'withdraw_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mat_wd_date' => 'date',
    ];

    public const STATUS_DRAFT    = 0;
    public const STATUS_PENDING  = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    public function getStatusLabelAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_DRAFT    => 'รอการอนุมัติ',
            self::STATUS_APPROVED => 'อนุมัติแล้ว',
            self::STATUS_REJECTED => 'ไม่อนุมัติ',
            default               => 'รอการอนุมัติ',
        };
    }

    public function getStatusCssAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_DRAFT    => 'pending',
            self::STATUS_APPROVED => 'approved',
            self::STATUS_REJECTED => 'rejected',
            default               => 'pending',
        };
    }

    public function getWithdrawTypeLabelAttribute(): string
    {
        return match ($this->withdraw_type) {
            'LARGE_LOT'    => 'เบิกวัสดุจากหน่วยงานต้นสังกัด',
            'INTERNAL_USE' => 'เบิกเพื่อใช้ภายในหน่วยงาน',
            default        => '-',
        };
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(GlbOrganization::class, 'org_id', 'org_id');
    }

    // Approver = the user who changed status to APPROVED (stored in updated_by)
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(SysUser::class, 'updated_by', 'id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MaterialWithdrawnList::class, 'mat_wd_id', 'id');
    }

    public function getApproverOrgNameAttribute(): string
    {
        if ((int) $this->status !== self::STATUS_APPROVED) {
            return '-';
        }

        return $this->updatedByUser?->organization?->org_name ?? '-';
    }
}
