<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['user_id', 'name', 'slug', 'description', 'logo', 'banner'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function artworks()
    {
        return $this->hasMany(Artwork::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** URL of the logo, or null if the seller didn't upload one. */
    public function logoUrl(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function bannerUrl(): ?string
    {
        return $this->banner ? asset('storage/' . $this->banner) : null;
    }
}
