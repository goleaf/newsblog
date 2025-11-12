<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'status',
        'verified_at',
        'token',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function scopeSubscribed($query)
    {
        return $query->where('status', 'subscribed');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function verify()
    {
        $this->update([
            'status' => 'subscribed',
            'verified_at' => now(),
        ]);
    }

    public function unsubscribe()
    {
        $this->update(['status' => 'unsubscribed']);
    }
}
