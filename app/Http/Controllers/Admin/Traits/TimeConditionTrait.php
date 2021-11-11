<?php

namespace App\Http\Controllers\Admin\Traits;

trait TimeConditionTrait
{
    public function searchConditionWithTimeInterval(
        array $condition = [[]],
        $column,
        $from,
        $to
    ) : array {
        $TI = $this->timeIntervalCondition($column, $from, $to);
        return array_merge($condition, $TI);
    }

    public function timeIntervalCondition($column, $from, $to) : array
    {
        return [
            [$column, '>=', $from],
            [$column, '<', $to],
        ];
    }
}
