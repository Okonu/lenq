<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneratedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'law_firm_id',
        'conversation_id',
        'legal_case_id',
        'title',
        'type',
        'format',
        'generation_prompt',
        'content',
        'downloaded_at',
        'download_count',
        'generation_metadata',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'generation_metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lawFirm()
    {
        return $this->belongsTo(LawFirm::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }
}
