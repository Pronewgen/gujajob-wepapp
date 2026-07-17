<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialProcurementList extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MATERIAL_PROCUREMENT_LIST';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'mat_pro_id',
        'mat_id',
        'mat_amt',
        'mat_price',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mat_amt' => 'float',
        'mat_price' => 'float',
    ];

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(MaterialProcurement::class, 'mat_pro_id', 'id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'mat_id', 'id');
    }
}
