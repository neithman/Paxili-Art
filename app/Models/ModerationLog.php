<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationLog extends Model
{
    protected $fillable = ['artwork_id', 'admin_id', 'artwork_title', 'action', 'reason'];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class)->withTrashed();
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
