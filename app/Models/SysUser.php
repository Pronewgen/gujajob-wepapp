<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class SysUser extends Authenticatable
{
    protected $connection = 'oracle';
    protected $table      = 'SYS_USER';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    protected $keyType    = 'int';
    public    $timestamps  = false;

    protected $fillable = ['login_name', 'passwd', 'org_id', 'user_name'];

    /** Never expose passwd to serialisation / JSON */
    protected $hidden = ['passwd'];

    // ──────────────────────────────────────────────────────────
    //  Authenticatable contract
    // ──────────────────────────────────────────────────────────

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getAttribute('id');
    }

    public function getAuthPassword(): string
    {
        return (string) ($this->getAttribute('passwd') ?? '');
    }

    public function getAuthPasswordName(): string
    {
        return 'passwd';
    }

    // ──────────────────────────────────────────────────────────
    //  Relationships
    // ──────────────────────────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(GlbOrganization::class, 'org_id', 'org_id');
    }
}
