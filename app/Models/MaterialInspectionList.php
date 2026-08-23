<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialInspectionList extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MATERIAL_INSPECTION_LIST';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'mat_isp_id',   // FK → MATERIAL_INSPECTION.id (schema has 'isp' not 'insp')
        'mat_id',
        'isp_amount',
        'created_by',
        'updated_by',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(MaterialInspection::class, 'mat_isp_id', 'id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'mat_id', 'id');
    }
}
