<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlbOrganization extends Model
{
    protected $connection = 'oracle';

    protected $table = 'GLB_ORGANIZATION';

    protected $primaryKey = 'org_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public const CREATED_AT = 'create_date';

    public const UPDATED_AT = 'last_update_date';

    protected $fillable = [
        'org_id',
        'org_code',
        'org_name',
        'org_abbr',
        'created_by',
        'last_updated_by',
    ];
}
