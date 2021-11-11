<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BanksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ini_set("auto_detect_line_endings", true);
        $prime_nationalities = array_keys(config('core.nationality'));
        $file = fopen(storage_path('db/banks.csv'), "r");

        # Fetch first row to get locales
        $data = fgetcsv($file, 500);
        $locale = [];
        $locale_start_col = 5;
        $col_num = 2;
        for ($i = $locale_start_col; $i < ($locale_start_col + $col_num); $i++) {
            if (!empty($data[$i])) {
                $locale[$i] = $data[$i];
            }
        }

        while(($data = fgetcsv($file, 500)) !== false) {
            if (in_array($data[0], $prime_nationalities)) {
                $foreign_name = [];
                for ($i = $locale_start_col; $i < ($locale_start_col + $col_num); $i++) {
                    if (!empty($data[$i])) {
                        $foreign_name[$locale[$i]] = $data[$i];
                    }
                }
                $foreign_name = empty($foreign_name) ? null : $foreign_name;
                $phonetic_name = empty($data[3]) ? null : $data[3];
                $local_code = empty($data[4]) ? null : $data[4];
                try {
                    \App\Models\Bank::create([
                        'nationality' => $data[0],
                        'swift_id' => $data[1],
                        'name' => $data[2],
                        'phonetic_name' => $phonetic_name,
                        'local_code' => $local_code,
                        'foreign_name' => $foreign_name,
                    ]);
                } catch (\Throwable $e) {
                    throw $e;
                }
            }
        }
        fclose($file);
    }
}
