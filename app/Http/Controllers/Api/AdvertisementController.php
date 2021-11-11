<?php

namespace App\Http\Controllers\Api;

use Dec\Dec;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\{
    ListQueryTrait,
    SecurityCodeTrait,
};
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
    NotFoundHttpException,
};
use App\Http\Requests\{
    CreateAdRequest,
    EditAdRequest,
    GetAdRequest,
    PreviewAdRequest,
};
use App\Exceptions\{
    Core\BadRequestError,
    UnavailableStatusError,
};
use App\Models\{
    Advertisement,
    Order,
};
use App\Http\Resources\{
    AdvertisementResource,
};
use App\Repos\Interfaces\{
    UserRepo,
    AdvertisementRepo,
};
use App\Services\{
    AdvertisementServiceInterface,
    ExchangeServiceInterface,
};

class AdvertisementController extends AuthenticatedController
{
    use ListQueryTrait, SecurityCodeTrait;

    public function __construct(
        UserRepo $UserRepo,
        AdvertisementRepo $AdvertisementRepo,
        AdvertisementServiceInterface $AdvertisementService,
        ExchangeServiceInterface $ExchangeService
    ) {
        parent::__construct();
        $this->UserRepo = $UserRepo;
        $this->AdvertisementRepo = $AdvertisementRepo;
        $this->AdvertisementService = $AdvertisementService;
        $this->ExchangeService = $ExchangeService;

        parent::__construct();
        $this->middleware(
            'real_name.check',
            ['only' => ['create', 'buy', 'sell']]
        );
    }

    public function preview(PreviewAdRequest $request)
    {
        $user = auth()->user();
        $values = $request->validated();
        $values['amount'] = trim_redundant_decimal($values['amount'], $values['coin']);
        $preview = $this->AdvertisementService->preview(
            $user,
            $values['type'],
            $values['coin'],
            $values['currency'],
            $values['unit_price'],
            $values['amount']
        );
        $spread_percentage = $this->AdvertisementService
            ->getPriceSpreadPercentage(
                $user,
                $values['type'],
                $values['coin'],
                $values['currency'],
                $preview['unit_price']
            );
        if (Dec::create($spread_percentage)->comp(Advertisement::MAX_SPREAD_PERCENTAGE) > 0) {
            $preview['price_spread_percentage'] = $spread_percentage;
        }
        return $preview;
    }

    public function create(CreateAdRequest $request)
    {
        $user = auth()->user();
        $values = $request->validated();
        $values['amount'] = trim_redundant_decimal($values['amount'], $values['coin']);
        $this->checkSecurityCode($user, $values['security_code']);
        $advertisement = $this->AdvertisementService->make(
                $user,
                $values,
                data_get($values, 'payables', [])
            );

        return response()->json(
            new AdvertisementResource($advertisement),
            201
        );
    }

    public function edit(EditAdRequest $request, string $id)
    {
        $user = auth()->user();
        $values = $request->validated();
        $this->checkSecurityCode($user, $values['security_code']);

        $origin_ad = $this->AdvertisementRepo->findOrFail($id);
        $this->checkAuthorization($origin_ad);

        $values['amount'] = trim_redundant_decimal($values['amount'], $origin_ad->coin);

        if ($origin_ad->status === Advertisement::STATUS_UNAVAILABLE) {
            $basics = [
                'coin' => $origin_ad->coin,
                'currency' => $origin_ad->currency,
                'type' => $origin_ad->type,
            ];
            $new_ad = $this->AdvertisementService->make(
                    $user,
                    array_merge($basics, $values),
                    data_get($values, 'payables', []),
                    $origin_ad
                );
            return response()->json(
                    new AdvertisementResource($new_ad),
                    201
                );
        } elseif ($origin_ad->status === Advertisement::STATUS_DELETED) {
            throw new NotFoundHttpException('Advertisment not found.');
        } else {
            throw new UnavailableStatusError("Edit is available only when advertisement is deactivated.");
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        $user = auth()->user();
        $mod = $request->input('status');

        $ad = $this->AdvertisementRepo->findOrFail($id);
        $this->checkAuthorization($ad);

        if (($ad->status === Advertisement::STATUS_AVAILABLE) and ($mod === Advertisement::STATUS_UNAVAILABLE)) {
            $this->AdvertisementService->deactivate($user, $ad);
        } else {
            throw new BadRequestError;
        }
        return response(null, 201);
    }

    public function delete(string $id)
    {
        $user = auth()->user();
        $ad = $this->AdvertisementRepo->findOrFail($id);
        $this->checkAuthorization($ad);
        if ($ad->status === Advertisement::STATUS_UNAVAILABLE) {
            $this->AdvertisementService->delete($user, $ad);
        } elseif ($ad->status === Advertisement::STATUS_DELETED) {
            throw new NotFoundHttpException('Advertisment not found.');
        } else {
            throw new UnavailableStatusError("Delete is available only when advertisement is deactivated.");
        }
        return response(null, 204);
    }

    public function getAd(string $id)
    {
        $user = auth()->user();
        $advertisement = $this->AdvertisementRepo->findOrFail($id);
        if ($advertisement->coin === config('core.coin.control')) {
            $ads = $this->checkAdsRecommended(collect([$advertisement]));
            $advertisement = $ads->first();
        }
        return new AdvertisementResource($advertisement);
    }

    public function getAds(GetAdRequest $request)
    {
        $values = $request->validated();

        # get user ads
        if (data_get($values, 'user_id')) {
            $user = $this->UserRepo->findOrFail($values['user_id']);
            if (\Auth::id() === $user->id) {
                $result = $this->AdvertisementRepo->getUserAdList(
                    $user,
                    data_get($values, 'action', 'sell'),
                    [Advertisement::STATUS_AVAILABLE, Advertisement::STATUS_UNAVAILABLE],
                    data_get($values, 'currency'),
                    data_get($values, 'nationality'),
                    $this->inputLimit(),
                    $this->inputOffset()
                );
            } else {
                $result = $this->AdvertisementRepo->getUserAdList(
                    $user,
                    data_get($values, 'action', 'sell'),
                    [Advertisement::STATUS_AVAILABLE],
                    data_get($values, 'currency'),
                    data_get($values, 'nationality'),
                    $this->inputLimit(),
                    $this->inputOffset()
                );
                /* agent feature is not activated now
                $result['data'] = $this->checkAdsRecommended($result['data']); */
            }
        } else {
            $result = $this->AdvertisementRepo->getAdList(
                $values['action'],
                $values['coin'],
                data_get($values, 'currency'),
                data_get($values, 'nationality'),
                $this->inputLimit(),
                $this->inputOffset()
            );
            /* agent feature is not activated now
            if ($values['coin'] === config('core.coin.control')) {
                $result['data'] = $this->checkAdsRecommended($result['data']);
            } */
        }
        return $this->paginationResponse(
            AdvertisementResource::collection($result['data']),
            $result['filtered'],
            $result['total']
        );
    }

    protected function checkAdsRecommended($ads)
    {
        /* We currently do not need this.
        $user = auth()->user();
        if ($user->is_agent) {
            $price_map = $this->ExchangeService->getCoinPriceMap(null);
            $ads = $ads->map(function ($ad, $key) use ($user, $price_map) {
                if (!$ad->owner->is($user) and !$ad->owner->is_agent and ($ad->coin === config('core.coin.control')) and ($ad->status === Advertisement::STATUS_AVAILABLE)) {
                    if ($ad->type === Advertisement::TYPE_BUY) {
                        $ad->is_recommended = ($ad->unit_price >= $price_map[$ad->coin][$ad->currency]['bid']);
                    } else {
                        $ad->is_recommended = ($ad->unit_price <= $price_map[$ad->coin][$ad->currency]['ask']);
                    }
                }
                return $ad;
            });
        } */
        return $ads;
    }

    protected function checkAuthorization(Advertisement $ad)
    {
        if (data_get($ad->owner, 'id') !== \Auth::id()) {
            throw new AccessDeniedHttpException;
        }
        return true;
    }
}
