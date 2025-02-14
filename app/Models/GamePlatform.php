<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlatform extends Model
{
    protected $fillable = [
        'game_id',
        'platform_id',
    ];
    
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
    
}
