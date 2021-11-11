<?php

namespace App\Http\Controllers\Traits;

use DateTimeInterface;
use Carbon\Carbon;

trait ListQueryTrait
{
    public function inputLimit(int $default = 100, int $max = 1000): int
    {
        $limit = intval(request()->input('limit', $default));
        return max(min($limit, $max), 0);
    }

    public function inputOffset(
        int $default = 0,
        int $min = 0,
        ?int $max = null
    ): int {
        $offset = intval(request()->input('offset', $default));
        $offset = max($offset, $min);
        return $max === null ? $offset : min($offset, $max);
    }

    public function inputDateTime(string $key): ?DateTimeInterface
    {
        $timestamp = request()->input($key);
        return $timestamp ? Carbon::createFromTimestampMs($timestamp) : null;
    }

    public function paginationResponse($data, $filtered, $total)
    {
        return [
            'data' => $data,
            'pagination' => [
                'filtered' => $filtered,
                'total' => $total,
                'limit' => $this->inputLimit(),
                'offset' => $this->inputOffset(),
            ],
        ];
    }
}
