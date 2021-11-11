<?php

namespace App\Http\Requests;

use App\Models\Order;

class OrderListRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $status = [
            Order::STATUS_PROCESSING,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELED,
        ];

        return [
            'status' => 'nullable|in:'.implode(",", $status),
            'start' => 'required|integer',
            'end' => 'required|integer',
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer',
        ];
    }
}
