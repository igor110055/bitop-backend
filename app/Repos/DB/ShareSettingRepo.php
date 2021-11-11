<?php

namespace App\Repos\DB;

use Carbon\Carbon;
use Dec\Dec;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use App\Exceptions\{
    Core\BadRequestError,
    Core\InternalServerError,
};
use App\Models\{
    ShareSetting,
    Group,
    User,
};

class ShareSettingRepo implements \App\Repos\Interfaces\ShareSettingRepo
{
    public function __construct(ShareSetting $share_settings) {
        $this->share_settings = $share_settings;
    }

    /*
     *  Get ShareSettings
     *
     */
    public function get($group = null, $is_prior = false, $with_user = false)
    {
        if ($is_prior) {
            $query = $this->share_settings
                ->whereNull('group_id')
                ->where('is_prior', true);
        } else {
            if (is_null($group)) {
                $query = $this->share_settings
                    ->whereNull('group_id')
                    ->where('is_prior', false);
            } else {
                $query = $group->share_settings();
            }
        }

        return $query->where('is_active', true)
            ->when($with_user, function($query) {
                return $query->with('user');
            })
            ->orderBy('percentage', 'desc')
            ->get();
    }

    /*
     *  Get ShareSettings and total_percentage
     *
     */
    public function getComposition($group = null, $is_prior = false, $with_user = false)
    {
        $share_settings = $this->get($group, $is_prior, $with_user);
        $total_percentage = dec_array_sum(Arr::pluck($share_settings, 'percentage'));
        if (Dec::gt($total_percentage, 100)) {
            throw new InternalServerError('ShareSetting sum is greater than 100%');
        }
        return [
            'share_settings' => $share_settings,
            'total_percentage' => (string)$total_percentage,
        ];
    }

    public function create($values)
    {
        return $this->share_settings->create($values);
    }

    public function deactivate($share_setting)
    {
        $this->share_settings
            ->find(data_get($share_setting, 'id', $share_setting))
            ->update([
                'is_active' => false,
            ]);
    }
}
