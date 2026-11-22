<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    const PENDING = 'pending';
    const PAID = 'paid';
    const CANCELLED = 'cancelled';

    protected $fillable = ['user_id', 'order_number', 'total', 'status', 'paid_at'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    /**
     * Payment verification: the order is marked paid AND a successful
     * payment record exists in the payments table.
     */
    public function isPaymentVerified(): bool
    {
        return $this->isPaid() && $this->payments()->where('status', Payment::SUCCESS)->exists();
    }
}
