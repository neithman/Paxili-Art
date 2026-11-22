<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['user_id', 'artwork_id', 'order_item_id', 'rating', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function artwork()
    {
        return $this->belongsTo(Artwork::class)->withTrashed();
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
