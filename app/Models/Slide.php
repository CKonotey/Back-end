<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use HasFactory;

    protected $primaryKey = 'slide_id'; // Setting slide_id as the primary key
    public $incrementing = false;        // Disable auto-incrementing if slide_id is not auto-generated
    protected $fillable = [
        'slide_id',       // Unique identifier for each slide
        'content',        // The text or content extracted from the slide
        'metadata',       // Any additional information (e.g., date, title)
    ];
}

