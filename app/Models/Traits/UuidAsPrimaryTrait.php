<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait UuidAsPrimaryTrait
{
    public static function bootUuidAsPrimaryTrait()
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();
            if (!$model->$key) {
                $model->$key = (String) Str::orderedUuid();
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
}
