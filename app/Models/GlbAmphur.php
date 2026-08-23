<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlbAmphur extends Model
{
    protected $connection = 'oracle';
    protected $table      = 'GLB_AMPHUR';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'amphur_code', 'amphur_name', 'province_id'];

    public function province(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GlbProvince::class, 'province_id', 'id');
    }

    public function tambons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GlbTambon::class, 'amphur_id', 'id');
    }
}
