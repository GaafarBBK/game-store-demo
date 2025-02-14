<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameCryptocurrency extends Model
{
    protected $fillable = [
        'game_id',
        'cryptocurrency_id',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function cryptocurrency()
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

}
