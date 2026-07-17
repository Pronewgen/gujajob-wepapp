<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MATERIALS';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'mat_code',
        'mat_name',
        'mat_desc',
        'unit',
        'min_amt',
        'max_amt',
        'created_by',
        'updated_by',
    ];
}
