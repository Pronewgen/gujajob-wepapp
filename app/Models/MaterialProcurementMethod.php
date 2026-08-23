<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialProcurementMethod extends Model
{
    protected $connection = 'oracle';

    protected $table = 'MAT_PRO_METHOD';

    protected $primaryKey = 'method_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = ['method_id', 'method_name', 'is_active', 'sort_order'];
}
