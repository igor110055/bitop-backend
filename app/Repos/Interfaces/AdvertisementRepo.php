<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Advertisement,
    User,
};

interface AdvertisementRepo
{
    public function find($id);
    public function findOrFail($id);
    public function findForUpdate($id);
    public function create(array $values);
    public function createByRef(Advertisement $ref, array $values);
    public function setStatus(Advertisement $advertisement, string $status, string $origin_status);
    public function setAttribute(Advertisement $advertisement, array $array);
    public function getAdList(
        string $type,
        string $coin,
        $currency = null,
        $nationality = null,
        int $limit,
        int $offset
    );
    public function getUserAdList(
        User $user,
        string $type,
        array $status,
        $currency = null,
        $nationality = null,
        int $limit,
        int $offset
    );
    public function delete(Advertisement $advertisement);
    public function calculateProportionFee(Advertisement $advertisement, $amount);
    public function checkValuesUnchanged(Advertisement $advertisement1, Advertisement $advertisement2);
    public function queryAdvertisement($where = [], $keyword = null, $user_id = null);
    public function countAll();
    public function getUserAdsCount(User $user);
    public function getTransaction(Advertisement $advertisement, string $type);
}
