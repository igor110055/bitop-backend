<?php

namespace App\Repos\Interfaces;

use App\Models\{
    BankAccount,
    User,
};

interface BankAccountRepo
{
    public function find($id);
    public function findOrFail($id);
    public function delete(BankAccount $bank_account);
    public function approve(BankAccount $bank_account);
    public function reject(BankAccount $bank_account);
    public function getUserBankAccounts(User $user, $is_verified = null, $with_deleted = false);
    public function getUserBankAccountIds(User $user);
    public function create(User $user, array $values);
    public function filterWithIds(Array $ids, Array $rules);
    public function getSupportMap($user_nationality = null);
    public function getFilteringQuery($status = 'active', $keyword = null);
    public function queryIsVerified($query, $is_verified = null);
    public function queryIsDeleted($query, $is_deleted = null);
    public function querySearch($query, $keyword = null);
    public function getAllCount();
    public function getNextToReview();
}
