<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $connection = 'oracle';
    protected $table      = 'ASSET_CATEGORY';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    protected $keyType    = 'int';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'asscat_code',
        'asscat_group',
        'asscat_type',
        'asscat_name',
        'asscat_unit',
        'depreciation_rate',
        'created_by',
        'updated_by',
    ];
}
