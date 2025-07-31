<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_case_id',
        'firm_member_id',
        'role',
    ];

    /**
     * assignment roles.
     */
    const ROLE_LEAD = 'lead';
    const ROLE_ASSOCIATE = 'associate';
    const ROLE_PARALEGAL = 'paralegal';
    const ROLE_SUPPORT = 'support';

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function firmMember(): BelongsTo
    {
        return $this->belongsTo(FirmMember::class);
    }

    public function isLead(): bool
    {
        return $this->role === self::ROLE_LEAD;
    }

    public function isAssociate(): bool
    {
        return $this->role === self::ROLE_ASSOCIATE;
    }

    public function isParalegal(): bool
    {
        return $this->role === self::ROLE_PARALEGAL;
    }

    public function isSupport(): bool
    {
        return $this->role === self::ROLE_SUPPORT;
    }
}
