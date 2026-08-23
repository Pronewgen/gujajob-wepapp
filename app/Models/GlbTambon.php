<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlbTambon extends Model
{
    protected $connection = 'oracle';
    protected $table      = 'GLB_TAMBON';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'tambon_code', 'tambon_name', 'amphur_id', 'zipcode'];

    public function amphur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GlbAmphur::class, 'amphur_id', 'id');
    }
}
