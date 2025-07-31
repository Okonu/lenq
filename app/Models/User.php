<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(LegalDocument::class);
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function firmMemberships(): HasMany
    {
        return $this->hasMany(FirmMember::class);
    }

    public function isMemberOf(LawFirm $lawFirm): bool
    {
        return $this->firmMemberships()
            ->where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->exists();
    }

    public function isAdminOf(LawFirm $lawFirm): bool
    {
        return $this->firmMemberships()
            ->where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->where('role', FirmMember::ROLE_ADMIN)
            ->exists();
    }

    public function getActiveLawFirm(): ?LawFirm
    {
        $firmMember = $this->firmMemberships()
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->with('lawFirm')
            ->first();

        return $firmMember ? $firmMember->lawFirm : null;
    }

    public function getActiveFirmMembership(): ?FirmMember
    {
        return $this->firmMemberships()
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->first();
    }
}
