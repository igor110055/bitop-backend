<?php

namespace App\Models\Traits;

use DateTimeInterface;

use Carbon\Carbon;

trait DateTimeTrait
{
    protected function serializeDate(DateTimeInterface $date)
    {
        # It is compatible with ISO-8601, see:
        # http://php.net/manual/en/class.datetime.php#datetime.constants.iso8601
        return $date->toAtomString();
    }

    protected function asDateTime($value)
    {
        if (is_numeric($value)) {
            switch ($this->getDateFormat()) {
            case 'Uv': # unix timestamp in millisecond
                return Carbon::createFromTimestampMs($value);

            case 'Uu': # unix timestamp in microsecond
                $s = "$value";
                $dot = strlen($s) - 6;
                $v = substr($s, 0, $dot).'.'.substr($s, $dot);
                return Carbon::createFromFormat('U.u', $v);

            default:
                return Carbon::createFromFormat($format, $value);
            }
        }
        return parent::asDateTime($value);
    }
}