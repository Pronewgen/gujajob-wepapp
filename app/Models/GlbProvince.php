<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlbProvince extends Model
{
    protected $connection = 'oracle';
    protected $table      = 'GLB_PROVINCE';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'province_code', 'province_name'];

    public function amphurs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GlbAmphur::class, 'province_id', 'id');
    }
}
