<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Game extends Model
{
    use HasFactory;
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
        return $this->belongsToMany(Cryptocurrency::class, 'game_cryptocurrencies');
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'game_genres');
    }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'game_platforms');
    }
    
}
