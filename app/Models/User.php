<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

/**
 * A User can be a BUYER and a SELLER at the same time.
 *  - Buyer  = anyone who is logged in.
 *  - Seller = a user who has created a store ($user->store is not null).
 *  - Admin  = is_admin column is true.
 *
 * Note: we use "RoutesNotifications" (instead of Laravel's "Notifiable") only so the
 * built-in "forgot password" e-mail still works. Our own in-app notifications are
 * stored in the "notifications" table through the notifications() relation below.
 */
class User extends Authenticatable
{
    use HasFactory, RoutesNotifications;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Default values for a brand-new user (before it is saved)
    protected $attributes = ['is_admin' => false];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // ---------- Relationships ----------

    public function store()
    {
        return $this->hasOne(Store::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function downloads()
    {
        return $this->hasMany(Download::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    // ---------- Helpers ----------

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isSeller(): bool
    {
        return $this->store()->exists();
    }

    /** Returns the user's cart, creating an empty one the first time. */
    public function getCart(): Cart
    {
        return $this->cart()->firstOrCreate([]);
    }

    /** Has this user already PAID for this artwork? */
    public function hasPurchased(Artwork $artwork): bool
    {
        return OrderItem::where('artwork_id', $artwork->id)
            ->whereHas('order', fn ($q) => $q->where('user_id', $this->id)->where('status', 'paid'))
            ->exists();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }
}
