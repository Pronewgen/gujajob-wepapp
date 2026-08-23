<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetImage extends Model
{
    protected $connection   = 'oracle';
    protected $table        = 'ASSET_IMAGE';
    protected $primaryKey   = 'id';
    public    $incrementing = false;
    protected $keyType      = 'int';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = ['id', 'ass_id', 'ass_image', 'created_by', 'updated_by'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'ass_id');
    }
}
