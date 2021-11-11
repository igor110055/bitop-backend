<?php

namespace App\Http\Controllers\Api;

use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
};
use App\Http\Controllers\Traits\ListQueryTrait;
use App\Models\{
    Transfer,
};
use App\Http\Requests\{
    TransactionRequest,
};
use App\Http\Resources\{
    TransactionResource,
};
use App\Repos\Interfaces\{
    TransactionRepo,
};

class TransactionController extends AuthenticatedController
{
    use ListQueryTrait;

    public function __construct(
        TransactionRepo $TransactionRepo
    ) {
        parent::__construct();
        $this->TransactionRepo = $TransactionRepo;
    }

    public function show(string $id)
    {
        $user = auth()->user();
        $transaction = $this->TransactionRepo
            ->findOrFail($id);
        if (!$transaction->account->user->is($user)) {
            throw new AccessDeniedHttpException;
        }

        return new TransactionResource($transaction);
    }


    public function getTransactionList(TransactionRequest $request)
    {
        $input = $request->validated();
        $result = $this->TransactionRepo
            ->getUserTransactions(
                auth()->user(),
                $input['coin'],
                $this->inputDateTime('start'),
                $this->inputDateTime('end'),
                $this->inputLimit(),
                $this->inputOffset()
            );

        return $this->paginationResponse(
            TransactionResource::collection(
                $result['data']->loadMorph('transactable', [
                    Transfer::class => ['src_user', 'dst_user'],
                ])
            ),
            $result['filtered'],
            $result['total']
        );
    }
}
