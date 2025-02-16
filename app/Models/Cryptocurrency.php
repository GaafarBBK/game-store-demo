<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cryptocurrency extends Model
{
    protected $fillable = [
        'name',
        'symbol',
    ];

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_cryptocurrencies');
    }


    
}
