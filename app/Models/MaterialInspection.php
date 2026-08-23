<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialInspection extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MATERIAL_INSPECTION';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'mat_insp_code',
        'mat_wd_id',
        'mat_insp_date',
        'mat_insp_person',
        'mat_insp_remark',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mat_insp_date' => 'date',
    ];

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(MaterialWithdrawn::class, 'mat_wd_id', 'id');
    }

    public function details(): HasMany
    {
        // Note: Oracle schema uses mat_isp_id (missing 'n') as the FK column name
        return $this->hasMany(MaterialInspectionList::class, 'mat_isp_id', 'id');
    }
}
