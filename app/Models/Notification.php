<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple in-app notification.
 *
 * Send one from anywhere in the app:
 *     Notification::send($user, 'new_order', 'New order', 'Someone bought your artwork', '/seller/sales');
 *
 * Send to every admin:
 *     Notification::toAdmins('new_user', 'New user', 'Jane just registered');
 */
class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'link', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public static function send(User $user, string $type, string $title, string $message, ?string $link = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }

    public static function toAdmins(string $type, string $title, string $message, ?string $link = null): void
    {
        foreach (User::where('is_admin', true)->get() as $admin) {
            self::send($admin, $type, $title, $message, $link);
        }
    }
}
