<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserQuery extends Model
{
    protected $fillable = [
        'user_id',        // ID of the user making the query
        'query',          // The user's query
        'response',       // The response given by the API
        'created_at',     // Timestamp for logging queries
    ];
}
