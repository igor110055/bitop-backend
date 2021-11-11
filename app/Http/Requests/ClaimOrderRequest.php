<?php

namespace App\Http\Requests;

use App\Models\Order;

class ClaimOrderRequest extends PublicRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $types = Order::PAYMENT_TYPES_MAP;
        return [
            'payment_src_type' => 'required|string|in:'.implode(",", $types),
            'payment_src_id' => 'required|string',
            'payment_dst_type' => 'required|string|in:'.implode(",", $types),
            'payment_dst_id' => 'required|string',
        ];
    }
}
