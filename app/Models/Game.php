<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'name',
        'manager',
        'price',
        'description',
        'image',
        'youtube_url', 
    ];

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favorable');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    
    public function cryptos()
    {
        return $this->belongsToMany(Cryptocurrency::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class);
    }
    
}
