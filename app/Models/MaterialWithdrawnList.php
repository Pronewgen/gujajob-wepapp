<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialWithdrawnList extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MATERIAL_WITHDRAWN_LIST';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'mat_wd_id',
        'mat_id',
        'wd_amount',
        'created_by',
        'updated_by',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(MaterialWithdrawn::class, 'mat_wd_id', 'id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'mat_id', 'id');
    }
}
