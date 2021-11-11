<?php

namespace App\Http\Controllers\Admin\Traits;

use Illuminate\Database\Eloquent\Collection;

trait DataTableTrait
{
    public function queryPagination($query, $total) : Collection
    {
        return $query
            ->skip(clamp_query(request()->input('start'), 0, $total))
            ->take(clamp_query(request()->input('length'), 10, 100))
            ->get();
    }

    public function result($total, $filtered, $data) : array
    {
        return [
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ];
    }

    public function draw(array $result)
    {
        $draw = request()->input('draw');
        if (is_numeric($draw)) {
            $result['draw'] = (int)$draw;
        }
        return $result;
    }
}
