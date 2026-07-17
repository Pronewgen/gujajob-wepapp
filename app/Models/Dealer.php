<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dealer extends Model
{
    protected $connection = 'oracle';

    protected $table = 'DEALER';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'dealer_type',
        'dealer_name',
        'dealer_tax_id',
        'dealer_addr_no',
        'dealer_alley',
        'dealer_street',
        'dealer_tam_id',
        'dealer_amp_id',
        'dealer_prov_id',
        'dealer_zipcode',
        'dealer_contact',
        'dealer_phone',
        'created_by',
        'updated_by',
    ];
}
