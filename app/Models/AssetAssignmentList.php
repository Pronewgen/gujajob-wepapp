<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignmentList extends Model
{
    protected $connection   = 'oracle';
    protected $table        = 'ASSET_ASSIGNMENT_LIST';
    protected $primaryKey   = 'id';
    public    $incrementing = false;
    protected $keyType      = 'int';

    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'ass_assign_id',
        'asset_id',
        'created_by',
        'updated_by',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AssetAssignment::class, 'ass_assign_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
