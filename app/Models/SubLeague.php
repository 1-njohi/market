<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubLeague extends Model
{
    use HasFactory;

    protected $fillable = [
        'season',
        'league_codes',
        'name',
        'owner_id',
        'invite_code',
        'max_members',
    ];
    
    protected $casts = [
        'max_members'  => 'integer',
        'league_codes' => 'array',
    ];
    
    /**
     * True when a matchday contest in this league is one this sub-league
     * plays. A null/empty league_codes means "all supported leagues" —
     * backward compat for pre-existing rows.
     */
    public function coversLeague(string $leagueCode): bool
    {
        if (empty($this->league_codes)) {
            return true;
        }
    
        return in_array($leagueCode, $this->league_codes, true);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(SubLeagueMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sub_league_members')
            ->withPivot('joined_at')
            ->withTimestamps();
    }
}