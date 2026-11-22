<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One GCash payment attempt for an order.
 *   mode = "test" -> paid with the built-in GCash simulator (no real money)
 *   mode = "live" -> paid with real GCash through PayMongo
 */
class Payment extends Model
{
    const PENDING = 'pending';
    const SUCCESS = 'success';
    const FAILED = 'failed';

    protected $fillable = [
        'order_id', 'user_id', 'method', 'mode', 'reference', 'provider_reference',
        'amount', 'status', 'account_number', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** "09171234567" -> "0917****567" (never show the full number) */
    public static function maskNumber(string $number): string
    {
        return substr($number, 0, 4) . '****' . substr($number, -3);
    }

    /** Text shown in tables, e.g. "GCash 0917****567 · TEST" */
    public function label(): string
    {
        return 'GCash' . ($this->account_number ? ' ' . $this->account_number : '') . ' · ' . strtoupper($this->mode);
    }
}
