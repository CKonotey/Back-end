<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Slide;  // Import the Slide model

class SlideSeeder extends Seeder
{
    public function run()
    {
        // Dummy data for slides
        Slide::create([
            'slide_id' => 'slide-001',   // Unique identifier for each slide
            'content' => 'Introduction to API development',
            'metadata' => json_encode([
                'author' => 'John Doe',
                'date' => '2024-09-17',
                'title' => 'API Basics'
            ])
        ]);

        Slide::create([
            'slide_id' => 'slide-002',   // Unique identifier for each slide
            'content' => 'How to build RESTful APIs in Laravel',
            'metadata' => json_encode([
                'author' => 'Jane Smith',
                'date' => '2024-09-18',
                'title' => 'RESTful APIs'
            ])
        ]);

        Slide::create([
            'slide_id' => 'slide-003',   // Unique identifier for each slide
            'content' => 'Understanding OAuth and JWT for secure APIs',
            'metadata' => json_encode([
                'author' => 'John Doe',
                'date' => '2024-09-19',
                'title' => 'API Security'
            ])
        ]);
    }
}
