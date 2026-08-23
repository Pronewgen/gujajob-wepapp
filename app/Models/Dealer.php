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

    /** Code → label mapping (no master table exists in DB). */
    public const DEALER_TYPES = [
        '1' => 'บริษัท จำกัด',
        '2' => 'บริษัทมหาชนจำกัด',
        '3' => 'ห้างหุ้นส่วนจำกัด',
        '4' => 'ร้านค้า',
        '5' => 'บุคคลธรรมดา',
    ];

    public function getDealerTypeLabelAttribute(): string
    {
        return self::DEALER_TYPES[$this->dealer_type] ?? ($this->dealer_type ?? '-');
    }

    public function province(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GlbProvince::class, 'dealer_prov_id', 'id');
    }

    public function amphur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GlbAmphur::class, 'dealer_amp_id', 'id');
    }

    public function tambon(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GlbTambon::class, 'dealer_tam_id', 'id');
    }
}
