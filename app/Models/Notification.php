<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'priority',
        'metadata',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Notification types
    const TYPE_CASE_ASSIGNMENT = 'case_assignment';
    const TYPE_TASK_ASSIGNMENT = 'task_assignment';
    const TYPE_TASK_DEADLINE = 'task_deadline_approaching';
    const TYPE_TASK_OVERDUE = 'task_overdue';
    const TYPE_DOCUMENT_ANALYSIS = 'document_analysis';
    const TYPE_CASE_CONVERSATION = 'case_conversation';
    const TYPE_FIRM_ANNOUNCEMENT = 'firm_announcement';
    const TYPE_KNOWLEDGE_BASE = 'knowledge_base_update';
    const TYPE_EMERGENCY = 'emergency';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Accessors
     */
    public function getIsReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

    public function getIsHighPriorityAttribute(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    public function getFormattedCreatedAtAttribute(): string
    {
        $now = now();
        $diff = $this->created_at->diffInMinutes($now);

        if ($diff < 1) return 'Just now';
        if ($diff < 60) return $diff . 'm ago';
        if ($diff < 1440) return $this->created_at->diffInHours($now) . 'h ago';
        if ($diff < 10080) return $this->created_at->diffInDays($now) . 'd ago';

        return $this->created_at->format('M j, Y');
    }

    public function getTypeDisplayNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CASE_ASSIGNMENT => 'Case Assignment',
            self::TYPE_TASK_ASSIGNMENT => 'Task Assignment',
            self::TYPE_TASK_DEADLINE => 'Task Deadline',
            self::TYPE_TASK_OVERDUE => 'Overdue Task',
            self::TYPE_DOCUMENT_ANALYSIS => 'Document Analysis',
            self::TYPE_CASE_CONVERSATION => 'Case Conversation',
            self::TYPE_FIRM_ANNOUNCEMENT => 'Firm Announcement',
            self::TYPE_KNOWLEDGE_BASE => 'Knowledge Base',
            self::TYPE_EMERGENCY => 'Emergency',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_CRITICAL => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_LOW => 'gray',
            default => 'blue',
        };
    }

    public function getIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CASE_ASSIGNMENT => '📁',
            self::TYPE_TASK_ASSIGNMENT => '✅',
            self::TYPE_TASK_DEADLINE => '⏰',
            self::TYPE_TASK_OVERDUE => '🚨',
            self::TYPE_DOCUMENT_ANALYSIS => '📄',
            self::TYPE_CASE_CONVERSATION => '💬',
            self::TYPE_FIRM_ANNOUNCEMENT => '📢',
            self::TYPE_KNOWLEDGE_BASE => '📚',
            self::TYPE_EMERGENCY => '🆘',
            default => '🔔',
        };
    }

    /**
     * Methods
     */
    public function markAsRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    public function isPriority(string $priority): bool
    {
        return $this->priority === $priority;
    }

    /**
     * Static methods
     */
    public static function createForUser(int $userId, array $data): self
    {
        return self::create(array_merge($data, ['user_id' => $userId]));
    }

    public static function getUnreadCountForUser(int $userId): int
    {
        return self::forUser($userId)->unread()->count();
    }

    public static function getHighPriorityCountForUser(int $userId): int
    {
        return self::forUser($userId)->unread()->highPriority()->count();
    }

    public static function getTodayCountForUser(int $userId): int
    {
        return self::forUser($userId)->today()->count();
    }

    public static function markAllAsReadForUser(int $userId): int
    {
        return self::forUser($userId)->unread()->update(['read_at' => now()]);
    }
}
