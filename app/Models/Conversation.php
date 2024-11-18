<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['chat_id', 'query', 'response', 'group_id', 'user_id', 'user_name'];

    // Relationship with the Chat model
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    // Relationship with the Group model
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // Relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class); // assuming you have a User model
    }
}
