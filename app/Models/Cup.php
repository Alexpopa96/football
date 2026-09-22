<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'wheel_pool_team_ids',
        'winner_entry_id',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'wheel_pool_team_ids' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CupEntry::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(CupMatch::class);
    }

    public function winnerEntry(): BelongsTo
    {
        return $this->belongsTo(CupEntry::class, 'winner_entry_id');
    }
}
