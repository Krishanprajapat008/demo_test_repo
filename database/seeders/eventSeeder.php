<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;

class eventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Event::create([
            'title' => 'Event Title',
            'desc' => 'Dummy Description',
            'status' => '1',
            'add_events' => date('Y-m-d H:i:s')
        ]);
    }
}
