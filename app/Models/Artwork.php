<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Artwork extends Model
{
    use SoftDeletes;

    // Status constants – use these instead of typing the strings everywhere.
    const PENDING = 'pending';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const REMOVED = 'removed';

    protected $fillable = [
        'store_id', 'category_id', 'title', 'slug', 'description', 'tags',
        'price', 'preview_path', 'file_path', 'file_name', 'status', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    // ---------- Relationships ----------

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function moderationLogs()
    {
        return $this->hasMany(ModerationLog::class)->latest();
    }

    // ---------- Query scopes ----------

    /** Usage: Artwork::approved()->get()  -> only artwork visible in the marketplace */
    public function scopeApproved($query)
    {
        return $query->where('status', self::APPROVED);
    }

    // ---------- Helpers ----------

    public function previewUrl(): string
    {
        return asset('storage/' . $this->preview_path);
    }

    public function tagList(): array
    {
        return array_filter(array_map('trim', explode(',', (string) $this->tags)));
    }

    public function averageRating(): float
    {
        return round((float) $this->reviews()->avg('rating'), 1);
    }

    /** Number of times this artwork was sold (paid orders only). */
    public function salesCount(): int
    {
        return $this->orderItems()->whereHas('order', fn ($q) => $q->where('status', 'paid'))->count();
    }
}
