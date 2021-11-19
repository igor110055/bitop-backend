<?php

namespace App\Repos\DB;

use Carbon\Carbon;
use Dec\Dec;
use DB;
use Illuminate\Database\Eloquent\Builder;
use App\Exceptions\{
    Core\BadRequestError,
    UnavailableStatusError,
};

use App\Models\{
    User,
    Advertisement,
    Order,
};

class AdvertisementRepo implements \App\Repos\Interfaces\AdvertisementRepo
{
    protected $ad;

    public function __construct(Advertisement $ad) {
        $this->ad = $ad;
    }

    public function find($id)
    {
        return $this->ad->find($id);
    }

    public function findOrFail($id)
    {
        return $this->ad->findOrFail($id);
    }

    public function findForUpdate($id)
    {
        return $this->ad
            ->lockForUpdate()
            ->findOrFail($id);
    }

    protected function preCreate(array $values)
    {
        return array_merge($values, [
            'remaining_amount' => $values['amount'],
            'remaining_fee' => $values['fee'],
            'status' => Advertisement::STATUS_AVAILABLE,
        ]);
    }

    public function create(array $values)
    {
        return $this->ad->create($this->preCreate($values));
    }

    public function createByRef(Advertisement $ref, array $values)
    {
        return $ref->references()->create($this->preCreate($values));
    }

    public function setStatus($advertisement, string $status, string $origin_status)
    {
        if ($this->ad
            ->where('id', $advertisement->id)
            ->where('status', $origin_status)
            ->update(['status' => $status]) !== 1) {
            throw new UnavailableStatusError;
        }
    }

    public function setAttribute(Advertisement $advertisement, array $array)
    {
        if ($this->ad
            ->where('id', $advertisement->id)
            ->where('status', $advertisement->status)
            ->where('remaining_amount', DB::raw($advertisement->remaining_amount))
            ->update($array) !== 1) {
            throw new UnavailableStatusError;
        }
    }

    public function getAdList(
        string $type,
        string $coin,
        $currency = null,
        int $limit,
        int $offset
    ) {
        $query = $this->ad
            ->where('is_express', false)
            ->where('status', Advertisement::STATUS_AVAILABLE)
            ->where('type', $type)
            ->where('coin', $coin)
            ->when($currency, function ($query, $currency) {
                return $query->where('currency', $currency);
            })
            ->when($type, function ($query, $type) {
                if ($type === Advertisement::TYPE_BUY) {
                    return $query->orderBy('unit_price', 'desc');
                } elseif ($type === Advertisement::TYPE_SELL) {
                    return $query->orderBy('unit_price', 'asc');
                }
            });

        $total = $query->count();
        $data = $query
            ->with(['owner', 'bank_accounts', 'bank_accounts.bank'])
            ->orderBy('remaining_amount', 'desc')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'filtered' => $data->count(),
            'data' => $data,
        ];
    }

    public function getUserAdList(
        User $user,
        string $type,
        array $status,
        $is_express = false,
        $currency = null,
        int $limit,
        int $offset
    ) {
        $query = $this->ad
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereIn('status', $status)
            ->when($currency, function ($query, $currency) {
                return $query->where('currency', $currency);
            });
        if (!is_null($is_express)) {
            $query->where('is_express', $is_express);
        }

        $total = $query->count();
        $data = $query
            ->with(['owner', 'bank_accounts', 'bank_accounts.bank'])
            ->latest('updated_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'filtered' => $data->count(),
            'data' => $data,
        ];
    }

    public function delete(Advertisement $advertisement)
    {
        return DB::transaction(function () use ($advertisement) {
            if ($this->ad
                ->where('id', $advertisement->id)
                ->whereIn('status', [Advertisement::STATUS_AVAILABLE, Advertisement::STATUS_UNAVAILABLE])
                ->update([
                    'status' => Advertisement::STATUS_DELETED,
                    'deleted_at' => Carbon::now(),
                ]
            ) !== 1) {
                throw new UnavailableStatusError;
            }
        });
    }

    public function calculateProportionFee(Advertisement $advertisement, $amount)
    {
        return (string) Dec::mul($amount, $advertisement->fee)->div($advertisement->amount, config('coin')[$advertisement->coin]['decimal']);
    }

    public function checkValuesUnchanged(Advertisement $advertisement1, Advertisement $advertisement2) {
        return $advertisement1->toArray() == $advertisement2->toArray();
    }

    public function queryAdvertisement($where = [], $keyword = null, $user = null)
    {
        $query = $this->ad->with(['owner']);
        if ($user) {
            $query->where('user_id', $user->id);
        }
        return $query
            ->when($where, function($query, $where){
                return $query->where($where);
            })
            ->when(($keyword and is_string($keyword)), function($query) use ($keyword) {
                return $query->where(function ($query) use ($keyword) {
                    $like = "%{$keyword}%";
                    return $query
                        ->orWhere('id', 'like', $like)
                        ->orWhere('coin', 'like', $like)
                        ->orWhere('currency', 'like', $like)
                        ->orWhereHas('owner', function (Builder $query) use ($like) {
                            $query->where('username', 'like', $like);
                        });
                });
            })
            ->orderBy('created_at', 'desc');
    }

    public function countAll()
    {
        return $this->ad->count();
    }

    public function getUserAdsCount(User $user)
    {
        return $user->advertisements()->count();
    }

    public function getTransaction(Advertisement $advertisement, string $type)
    {
        return $advertisement->transactions()
            ->where('type', $type)
            ->latest()
            ->first();
    }

    public function getExpressAds(
        User $user,
        string $type,
        string $coin,
        string $currency
    ) {
        $data = $this->ad
            ->where('user_id', '!=', $user->id)
            ->where('is_express', true)
            ->where('status', Advertisement::STATUS_AVAILABLE)
            ->where('type', $type)
            ->where('coin', $coin)
            ->where('currency', $currency)
            ->when($type, function ($query, $type) {
                if ($type === Advertisement::TYPE_BUY) {
                    return $query->orderBy('unit_price', 'desc');
                } elseif ($type === Advertisement::TYPE_SELL) {
                    return $query->orderBy('unit_price', 'asc');
                }
            })->orderBy('created_at', 'desc')
            ->get();
        return $data;
    }
}
