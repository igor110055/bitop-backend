<?php

namespace App\Repos\DB;

use Carbon\Carbon;

use App\Models\{
    BankAccount,
    User,
};

class BankAccountRepo implements \App\Repos\Interfaces\BankAccountRepo
{
    protected $bank_account;

    public function __construct(BankAccount $bank_account) {
        $this->bank_account = $bank_account;
    }

    public function find($id)
    {
        return $this->bank_account->find($id);
    }

    public function findOrFail($id)
    {
        return $this->bank_account->findOrFail($id);
    }

    public function delete(BankAccount $bank_account)
    {
        return $bank_account->update([
            'deleted_at' => Carbon::now()->format('Uv'),
        ]);
    }

    public function approve(BankAccount $bank_account)
    {
        return $bank_account->update([
            'verified_at' => Carbon::now()->format('Uv'),
        ]);
    }

    public function reject(BankAccount $bank_account)
    {
        return $bank_account->update([
            'verified_at' => null,
            'deleted_at' => Carbon::now()->format('Uv'),
        ]);
    }

    public function getUserBankAccounts(User $user, $is_verified = null, $with_deleted = false)
    {
        $query = $user->bank_accounts()
            ->with(['bank']);
        if ($is_verified === true) {
            $query->whereNotNull('verified_at');
        } elseif ($is_verified === false) {
            $query->whereNull('verified_at');
        }
        if (!$with_deleted) {
            $query->whereNull('deleted_at');
        }
        return $query->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserBankAccountIds(User $user)
    {
        return $this->getUserBankAccounts($user, true)->pluck('id');
    }

    public function create(User $user, array $values)
    {
        $values['verified_at'] = $user->isAgent ? Carbon::now()->format('Uv') : null;
        return $user->bank_accounts()->create($values);
    }

    public function filterWithIds(Array $ids, Array $rules)
    {
        $bank_accounts = $this->bank_account
            ->with('bank')
            ->find($ids);

        $filtered = $bank_accounts->filter(function ($bank_account, $key) use ($rules) {
            $pass = true;
            if (isset($rules['currency'])) {
                $pass = (bool)($pass AND in_array($rules['currency'], $bank_account->currency));
            }
            if (isset($rules['user_id'])) {
                $pass = (bool)($pass & ($rules['user_id'] === $bank_account->user_id));
            }
            return $pass;
        });

        return $filtered;
    }

    public function getSupportMap($user_nationality = null)
    {
        $nationalities = config('core.nationality');
        $result = [];
        foreach ($nationalities as $nationality => $data) {
            if ((($user_nationality === "TW") and ($nationality === 'CN')) OR
                (($user_nationality !== "TW") and ($nationality === 'TW'))) {
                continue;
            }
            $result[$nationality] = $data['currency'];
        }
        return $result;
    }

    public function getFilteringQuery($status = BankAccount::STATUS_ACTIVE, $keyword = null)
    {
        $query = $this->bank_account
            ->with('owner')
            ->with('bank');
        if ($status === BankAccount::STATUS_ACTIVE) {
            $query = $this->queryIsVerified($query, true);
            $query = $this->queryIsDeleted($query, false);    
        } elseif ($status === BankAccount::STATUS_PENDING) {
            $query = $this->queryIsVerified($query, false);
            $query = $this->queryIsDeleted($query, false);
        } elseif ($status === BankAccount::STATUS_DELETED) {
            $query = $this->queryIsDeleted($query, true);
        }
        $query = $this->querySearch($query, $keyword);
        return $query;
    }

    public function queryIsVerified($query, $is_verified = null)
    {
        if (is_bool($is_verified)) {
            if ($is_verified) {
                $query = $query->whereNotNull('verified_at');
            } else {
                $query = $query->whereNull('verified_at');
            }
        }
        return $query;
    }

    public function queryIsDeleted($query, $is_deleted = null)
    {
        if (is_bool($is_deleted)) {
            if ($is_deleted) {
                $query = $query->whereNotNull('deleted_at');
            } else {
                $query = $query->whereNull('deleted_at');
            }
        }
        return $query;
    }

    public function querySearch($query, $keyword = null)
    {
        if ($keyword and is_string($keyword)) {
            $query = $query->where(function ($query) use ($keyword) {
                $like = "%{$keyword}%";
                return $query
                    ->orWhere('id', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('account', 'like', $like);
            });
        }
        return $query;
    }

    public function getAllCount()
    {
        return $this->bank_account->count();
    }

    public function getNextToReview()
    {
        return $this->bank_account->whereNull('deleted_at')->whereNull('verified_at')->oldest('updated_at')->first();
    }
}
