<?php

namespace App\Http\Resources;

use Dec\Dec;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\ExchangeServiceInterface;

class LimitationResource extends JsonResource
{
    protected $daily_params;

    public function withUSDLimit($daily_params)
    {
        extract($daily_params);
        $this->USD = [
            'daily_max' => $daily_max,
            'daily_used' => $daily_used,
            'daily_remain' => $daily_remain,
        ];
        return $this;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (auth()->user()->two_factor_auth) {
            $this->max = (string) Dec::mul($this->max, config('core.two_factor_auth.withdrawal_limit'));
        }

        return [
            'type' => $this->type,
            'coin' => $this->coin,
            'min' => $this->min,
            'max' => $this->max,
            'remain_max' => $this->when($this->USD, function () {
                $service = app()->make(ExchangeServiceInterface::class);
                $compare = $service->USDTToCoin($this->USD['daily_remain'], $this->coin);
                return min($this->max, $compare);
            }),
            'USD' => $this->when($this->USD, $this->USD),
        ];
    }
}
