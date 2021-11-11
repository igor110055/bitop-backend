<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminAction;

class OrderUpdateRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $actions = [
            AdminAction::TYPE_CANCEL_ORDER,
            AdminAction::TYPE_COMPLETE_ORDER,
        ];
        return [
            'action' => 'required|in:'.implode(",", $actions),
            'description' => 'required|string',
        ];
    }
}
