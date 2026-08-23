<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialInventory extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MATERIAL_INVENTORY';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'org_id',
        'mat_id',
        'inv_amt',
        'inv_price',
        'created_by',
        'updated_by',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'mat_id', 'id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(GlbOrganization::class, 'org_id', 'org_id');
    }
}
