<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;
use App\Models\Iso3166;

class Iso3166sTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $key = 'iso_3611-1';
        if (!\Cache::has($key)) {
            # fetch the iso_3611-1.json from the debian package iso-codes
            $uri = 'https://salsa.debian.org/iso-codes-team/iso-codes/raw/main/data/iso_3166-1.json';
            \Cache::forever($key, file_get_contents($uri));
        }

        $calling_codes_source_url = 'https://raw.githubusercontent.com/AccessGateLabs/useful-jsons/master/country-codes/country-list-ALPHAcode-with-capital-currency-unicode.json';
        $calling_codes_data = file_get_contents($calling_codes_source_url);
        $calling_codes_data = json_decode($calling_codes_data, true);
        $calling_codes_data = collect($calling_codes_data)->keyBy('Iso2');

        $iso3166s = Iso3166::all()->keyBy('id');
        $values = [];
        foreach (json_decode(\Cache::get($key), true)['3166-1'] as $item) {
            $id = $item['numeric'];
            if (isset($item['common_name'])) {
                $item['name'] = $item['common_name'];
            }
            if (isset($iso3166s[$id])) {
                $iso3166 = $iso3166s[$id];
                foreach (['alpha_2', 'alpha_3', 'name'] as $field) {
                    $iso3166->$field = $item[$field];
                }
                if (isset($calling_codes_data[$item['alpha_2']])) {
                    $calling_code = data_get($calling_codes_data[$item['alpha_2']], 'Dial');
                    $iso3166->calling_code = str_replace(['+', '-'], '', $calling_code);
                    $iso3166->flag_unicode = data_get($calling_codes_data[$item['alpha_2']], 'Unicode');
                }
                $iso3166->timestamps = false;
                $iso3166->save();
                unset($iso3166s[$id]);
            } else {
                $value = [
                    'id' => $item['numeric'],
                    'alpha_2' => $item['alpha_2'],
                    'alpha_3' => $item['alpha_3'],
                    'name' => data_get($item, 'common_name', $item['name']),
                ];
                if (isset($calling_codes_data[$item['alpha_2']])) {
                    $calling_code = data_get($calling_codes_data[$item['alpha_2']], 'Dial');
                    $value['calling_code'] = str_replace(['+', '-'], '', $calling_code);
                    $value['flag_unicode'] = data_get($calling_codes_data[$item['alpha_2']], 'Unicode');
                }

                $values[] = $value;
            }
        }
        if (count($values) > 0) {
            DB::table('iso3166s')->insert($values);
        }
        if (count($iso3166s) > 0) {
            DB::table('iso3166s')->where_in('id', $iso3166s->keys())->delete();
        }
    }
}
