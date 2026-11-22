<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'artwork_id', 'store_id', 'title', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /** withTrashed(): buyers keep their purchase even if the seller deletes the product later. */
    public function artwork()
    {
        return $this->belongsTo(Artwork::class)->withTrashed();
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function download()
    {
        return $this->hasOne(Download::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function isDownloaded(): bool
    {
        return $this->download !== null;
    }
}
