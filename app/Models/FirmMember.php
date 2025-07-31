<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FirmMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'law_firm_id',
        'user_id',
        'role',
        'title',
        'department',
        'status',
        'invitation_token',
        'invitation_accepted_at',
    ];

    protected $casts = [
        'invitation_accepted_at' => 'datetime',
    ];

    const ROLE_ADMIN = 'admin';
    const ROLE_ATTORNEY = 'attorney';
    const ROLE_STAFF = 'staff';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_INVITED = 'invited';

    public function lawFirm(): BelongsTo
    {
        return $this->belongsTo(LawFirm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAttorney(): bool
    {
        return $this->role === self::ROLE_ATTORNEY;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
