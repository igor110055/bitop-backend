<?php

namespace App\Repos\DB;

use App\Exceptions\{
    Core\BadRequestError,
    Core\UnknownError,
};
use App\Models\{
    FeeSetting,
};

class FeeSettingRepo implements \App\Repos\Interfaces\FeeSettingRepo
{
    public function __construct(FeeSetting $fs)
    {
        $this->fee_setting = $fs;
    }

    public function create(array $values, $applicable = null)
    {
        if ($applicable) {
            return $applicable->fee_settings()->create($values);
        }
        return $this->fee_setting->create($values);
    }

    public function inactivate(string $coin, string $type, $applicable = null)
    {
        if ($applicable) {
            $applicable->fee_settings()
                ->where('coin', $coin)
                ->where('type', $type)
                ->update(['is_active' => false]);
        } else {
            $this->fee_setting
                ->where('coin', $coin)
                ->where('type', $type)
                ->whereNull('applicable_id')
                ->whereNull('applicable_type')
                ->where('coin', $coin)
                ->update(['is_active' => false]);
        }
    }

    public function set(string $coin, string $type, array $ranges, $applicable = null)
    {
        if (!in_array($type, FeeSetting::RANGE_TYPES)) {
            throw new BadRequestError;
        }
        # add new settings
        $rangeStart = 0;
        $response = [];
        foreach ($ranges as $range) {
            $data = [
                'coin' => $coin,
                'type' => $type,
                'range_start' => $rangeStart,
                'range_end' => $range['range_end'],
                'value' => $range['value'],
                /* 'unit' => ($range['unit'] === '%') ? '%' : $coin */  # order fee should only support percentage unit.
                'unit' => '%',
                'is_active' => true,
            ];
            $rangeStart = $range['range_end'];
            $response[] = $this->create($data, $applicable);
        }
        return $response;
    }

    public function setFixed(
        string $coin,
        string $type,
        string $discount_percent,
        $applicable = null
    ) {
        if (!in_array($type, FeeSetting::FIX_TYPES)) {
            throw new BadRequestError;
        }
        return $this->create([
            'coin' => $coin,
            'type' => $type,
            'range_start' => 0,
            'value' => $discount_percent,
            'unit' => '%',
            'is_active' => true,
        ], $applicable);
    }
    /*
     *  Get FeeSettings
     *
     */
    public function get(
        string $coin,
        string $type,
        $applicable = null
    ) {
        if (is_null($applicable)) {
            return $this->fee_setting
                ->where('coin', $coin)
                ->where('type', $type)
                ->where('applicable_type', null)
                ->where('applicable_id', null)
                ->where('is_active', true)
                ->orderBy('range_start')
                ->get();
        } else {
            return $applicable
                ->fee_settings()
                ->where('coin', $coin)
                ->where('type', $type)
                ->where('is_active', true)
                ->orderBy('range_start')
                ->get();
        }

        throw new UnknownError('invalid fee applicable');
    }

    public function getFixed(
        string $coin,
        string $type,
        $applicable = null
    ) {
        if (is_null($applicable)) {
            return $this->fee_setting
                ->where('coin', $coin)
                ->where('type', $type)
                ->where('applicable_type', null)
                ->where('applicable_id', null)
                ->where('is_active', true)
                ->latest()
                ->first();
        } else {
            return $applicable
                ->fee_settings()
                ->where('coin', $coin)
                ->where('type', $type)
                ->where('is_active', true)
                ->latest()
                ->first();
        }
        throw new UnknownError('invalid fee applicable');
    }
}
