<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetAssignment extends Model
{
    protected $connection   = 'oracle';
    protected $table        = 'ASSET_ASSIGNMENT';
    protected $primaryKey   = 'id';
    public    $incrementing = false;
    protected $keyType      = 'int';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const STATUS_ACTIVE    = '1';
    public const STATUS_CANCELLED = '0';

    protected $casts = [
        'assign_date' => 'date',
    ];

    protected $fillable = [
        'id',
        'org_id',
        'target_org_id',
        'target_sub_org_id',
        'assigner_id',
        'assign_date',
        'status',
        'remark',
        'created_by',
        'updated_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AssetAssignmentList::class, 'ass_assign_id');
    }

    public function org(): BelongsTo
    {
        return $this->belongsTo(GlbOrganization::class, 'org_id', 'org_id');
    }

    public function targetOrg(): BelongsTo
    {
        return $this->belongsTo(GlbOrganization::class, 'target_org_id', 'org_id');
    }

    public function targetSubOrg(): BelongsTo
    {
        return $this->belongsTo(GlbOrganization::class, 'target_sub_org_id', 'org_id');
    }
}
