<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['chat_id', 'query', 'response', 'group_id'];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }


    public function group()
    {
        return $this->belongsTo(Group::class);
    }

}
