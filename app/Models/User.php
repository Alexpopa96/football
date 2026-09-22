<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;

    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'phone',
        'obs',
        'pin',
        'avatar_emoji',
        'avatar_color',
        'rating',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    public function permissionList()
    {
        return $this->roles
            ->map->permissions
            ->flatten()->pluck('name')->unique();
    }

    public function verifyPin(string $pin): bool
    {
        return $this->pin && Hash::check($pin, $this->pin);
    }

    /**
     * Starting bet balance a player is topped up to after going bankrupt.
     */
    private const BANKRUPTCY_RESET_BALANCE = 20;

    /**
     * Deduct a stake from the player's bet balance. If that empties it out,
     * top it back up to the starting balance and record the bankruptcy so
     * the leaderboard can tell a "fresh 20" apart from a "reset 20".
     */
    public function spendBetBalance(int $amount): void
    {
        $this->decrement('bet_balance', $amount);

        if ($this->bet_balance <= 0) {
            $this->increment('bankruptcies_count');
            $this->update(['bet_balance' => self::BANKRUPTCY_RESET_BALANCE]);
        }
    }

    public function userRole()
    {
        return $this->belongsTo('Spatie\Permission\Models\Role', 'id', 'id');
    }
}
