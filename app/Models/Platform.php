<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = [
        'name',
    ];

    public function games()
    {
        return $this->belongsToMany(Game::class, 'game_platforms');
    }
    
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
