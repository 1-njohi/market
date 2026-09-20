<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'phone', 'code', 'country_code', 'bio', 'profile_picture_url', 'is_admin', 'is_verified', 'suspended_at', 'suspension_reason'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime'
        ];
    }

    protected $appends = ['joined_ago'];
    public function Wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function Transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function Deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function Withdraws()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function Betslips()
    {
        return $this->hasMany(Betslip::class);
    }
    public function purchases()
    {
        return $this->hasMany(BetslipUserPurchase::class, 'buyer_id');
    }

    public function purchasedBetslips()
    {
        return $this->belongsToMany(Betslip::class, 'betslip_user_purchases', 'buyer_id', 'betslip_id')
            ->withPivot([
                'id',
                'seller_id',
                'purchase_price',
                'total_odds',
                'status',
                'payment_method',
                'payment_reference',
                'purchased_at',
                'created_at',
                'updated_at'
            ])
            ->withTimestamps()
            ->orderBy('betslip_user_purchases.created_at', 'desc');
    }

    public function soldBetslips()
    {
        return $this->belongsToMany(Betslip::class, 'betslip_user_purchases', 'seller_id', 'betslip_id')
            ->withPivot([
                'id',
                'buyer_id',
                'purchase_price',
                'total_odds',
                'status',
                'payment_method',
                'payment_reference',
                'purchased_at',
                'created_at',
                'updated_at'
            ])
            ->withTimestamps()
            ->orderBy('betslip_user_purchases.created_at', 'desc');
    }

    // Purchase records as buyer
    public function purchasesAsBuyer()
    {
        return $this->hasMany(BetslipUserPurchase::class, 'buyer_id');
    }

    // Purchase records as seller
    public function purchasesAsSeller()
    {
        return $this->hasMany(BetslipUserPurchase::class, 'seller_id');
    }

    // Get active purchases (pending or completed)
    public function getActivePurchases()
    {
        return $this->purchasedBetslips()->wherePivotIn('status', ['pending', 'underway']);
    }

    // Get completed purchases
    public function getCompletedPurchases()
    {
        return $this->purchasedBetslips()->wherePivot('status', 'settled');
    }

    // Check if user has purchased a specific betslip
    public function hasPurchasedBetslip(Betslip $betslip): bool
    {
        return $this->purchasedBetslips()->where('betslip_id', $betslip->id)->exists();
    }

    // Get purchase record for a specific betslip
    public function getPurchaseForBetslip(Betslip $betslip): ?BetslipUserPurchase
    {
        return $this->purchasesAsBuyer()->where('betslip_id', $betslip->id)->first();
    }

    /**
     * Users that this user is following
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
            ->withPivot('is_notifications_enabled', 'followed_at')
            ->withTimestamps()
            ->orderBy('followers.followed_at', 'desc');
    }

    /**
     * Users that follow this user
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
            ->withPivot('is_notifications_enabled', 'followed_at')
            ->withTimestamps()
            ->orderBy('followers.followed_at', 'desc');
    }

    /**
     * Check if user is following another user
     */
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    /**
     * Check if user is followed by another user
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    /**
     * Follow a user
     */
    public function follow(User $user): bool
    {
        if ($this->id === $user->id) {
            return false; // Cannot follow yourself
        }

        if ($this->isFollowing($user)) {
            return true; // Already following
        }

        $this->following()->attach($user->id, [
            'followed_at' => now(),
            'is_notifications_enabled' => true,
        ]);

        return true;
    }

    /**
     * Unfollow a user
     */
    public function unfollow(User $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        $this->following()->detach($user->id);
        return true;
    }

    /**
     * Toggle follow status
     */
    public function toggleFollow(User $user): bool
    {
        if ($this->isFollowing($user)) {
            $this->unfollow($user);
            return false; // Unfollowed
        }

        $this->follow($user);
        return true; // Followed
    }

    /**
     * Update notification preferences for a followed user
     */
    public function updateFollowNotifications(User $user, bool $enabled): bool
    {
        if (!$this->isFollowing($user)) {
            return false;
        }

        $this->following()->updateExistingPivot($user->id, [
            'is_notifications_enabled' => $enabled
        ]);

        return true;
    }

    /**
     * Get followers count
     */
    public function getFollowersCount(): int
    {
        return $this->followers()->count();
    }

    /**
     * Get following count
     */
    public function getFollowingCount(): int
    {
        return $this->following()->count();
    }

    /**
     * Get recent followers (with pagination)
     */
    public function getRecentFollowers(int $limit = 10)
    {
        return $this->followers()->take($limit)->get();
    }

    /**
     * Get recent following (with pagination)
     */
    public function getRecentFollowing(int $limit = 10)
    {
        return $this->following()->take($limit)->get();
    }

    /**
     * Get betslips from followed sellers (for feed)
     */
    public function getFollowedSellersBetslips(int $limit = 20)
    {
        $followedIds = $this->following()->pluck('users.id');

        return Betslip::whereIn('user_id', $followedIds)
            ->where('status', 'pending')
            ->where('remaining', '>', 0)
            ->with(['seller', 'odds', 'odds.fixture'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
    public function sellerMetric()
    {
        return $this->hasOne(\App\Models\SellerMetric::class);
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function getJoinedAgoAttribute(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }

    protected function profilePictureUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (!$value) {
                    return null;
                }

                // Legacy: value is already an absolute URL (e.g. an external avatar)
                if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                    return $value;
                }

                return Storage::disk('public')->url($value);
            },
        );
    }

    /**
     * Get the raw storage path for deletion. Returns null for external URLs
     * so we never try to delete something we don't own.
     */
    public function getAvatarPathForDeletion(): ?string
    {
        $raw = $this->getRawOriginal('profile_picture_url');

        if (!$raw || str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return null;
        }

        return $raw;
    }
}
