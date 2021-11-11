<?php

namespace App\Models\Traits;

/**
 * Generate ID according to IBAN standard
 * IBAN: https://en.wikipedia.org/wiki/International_Bank_Account_Number*
 */

trait RandomIDTrait
{
    public static function bootRandomIDTrait()
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();
            if (!$model->$key) {
                while (true) {
                    $id = static::generate();
                    if (!$model::find($id)) {
                        break;
                    }
                }
                $model->$key = $id;
            }
        });

        static::saving(function ($model) {
            # modifying primary key is not allowed!
            $key = $model->getKeyName();
            $orig = $model->getOriginal($key);
            if ($orig !== null and $model->$key !== $orig) {
                $model->$key = $orig;
            }
        });
    }

    protected static function generate()
    {
        $rand_size = static::ID_SIZE - 8;
        $rand_str = '';

        # because pow(10, x) may return float when x > 10, we need to generate rand_str iteratively
        while ($rand_size > 0) {
            $seg_size = ($rand_size >= 10) ? 10 : $rand_size;
            $seg_rand = random_int(0, (pow(10, $seg_size) - 1)); # seg_size-digit int
            $rand_str.= sprintf("%0{$seg_size}d", $seg_rand);    # concact seg_size-digit string to $rand_str
            $rand_size = $rand_size - $seg_size;
        }

        $time = gettimeofday()['usec']; # 6-digit int
        $base = sprintf('%s%06d', $rand_str, $time);             # concact strings
        $checksum = 98 - (intval($base.'00') % 97);              # 2-digit int
        return sprintf('%s%02d', $base, $checksum);
    }
}
