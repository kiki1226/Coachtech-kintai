<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['date' => '2025-01-01', 'name' => '元日'],
            ['date' => '2025-01-13', 'name' => '成人の日'],
            ['date' => '2025-02-11', 'name' => '建国記念の日'],
            // ...必要に応じて追加
        ];
        foreach ($rows as $row) {
           \App\Models\Holiday::updateOrCreate(
                ['date' => $row['date']],
                ['name' => $row['name']]
            );
        }
    }
}
