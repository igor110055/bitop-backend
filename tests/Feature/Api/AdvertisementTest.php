<?php

namespace Tests\Feature\Api;

use Dec\Dec;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Repos\Interfaces\{
    AdvertisementRepo,
    AccountRepo,
    BankRepo,
    BankAccountRepo,
};
use App\Services\{
    AdvertisementServiceInterface,
};
use App\Models\{
    User,
    Advertisement,
    Transaction,
};

class AdvertisementTest extends TestCase
{
    use ApiTestTrait;
    protected static $setUpHasRunOnce = false;
    protected static $userA;
    protected static $userA_bank;
    protected static $buy_ad;
    protected static $sell_ad;

    # called once for each tests
    public function setUp() : void
    {
        parent::setUp();
        if (!static::$setUpHasRunOnce) {
            static::$userA = User::factory()->create();

            $this->AdvertisementService = app()->make(AdvertisementServiceInterface::class);
            $this->AdvertisementRepo = app()->make(AdvertisementRepo::class);
            $this->AccountRepo = app()->make(AccountRepo::class);
            $this->AccountRepo->allByUserOrCreate(static::$userA);

            # init
            $this->AccountRepo->deposit(static::$userA, 'USDT-ERC20', '100');
            $bank = app()->make(BankRepo::class)->getBankListByNationality('TW')
                ->filter(function ($b) {
                    return $b->swift_id === 'BKTWTWTP';
                });
            static::$userA_bank = app()->make(BankAccountRepo::class)->create(static::$userA, [
                'name' => 'userA',
                'phonetic_name' => 'userA',
                'currency' => ['TWD'],
                'account' => '123456789',
                'bank_branch_name' => 'userA_test_branch_name',
                'bank_branch_phonetic_name' => 'userA_test_branch_phonetic_name',
                'bank_id' => $bank[0]->id,
            ]);

            static::$setUpHasRunOnce = true;
        } else {
            $this->AdvertisementService = app()->make(AdvertisementServiceInterface::class);
            $this->AdvertisementRepo = app()->make(AdvertisementRepo::class);
            $this->AccountRepo = app()->make(AccountRepo::class);
        }
    }

    public function testBuyPreview()
    {
        $response = $this->apiAs(
            'GET',
            $this->link('api/ads/preview'),
            [
                'type' => Advertisement::TYPE_BUY,
                'coin' => 'USDT-ERC20',
                'amount' => 5,
                'currency' => 'TWD',
                'unit_price' => 28.9,
            ],
            [],
            static::$userA
        )->assertStatus(200)->json();

        # assert response
        $this->assertEquals($response['type'], Advertisement::TYPE_BUY);
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertEquals($response['currency'], 'TWD');
        $this->assertTrue(Dec::eq($response['amount'], '5'));
        $this->assertTrue(Dec::eq($response['unit_price'], '28.9'));
        $this->assertTrue(!is_null($response['price']));
        $this->assertTrue(Dec::eq($response['fee'], '0'));
    }

    public function testSellPreview()
    {
        $response = $this->apiAs(
            'GET',
            $this->link('api/ads/preview'),
            [
                'type' => Advertisement::TYPE_SELL,
                'coin' => 'USDT-ERC20',
                'amount' => 5,
                'currency' => 'TWD',
                'unit_price' => 28.9,
            ],
            [],
            static::$userA
        )->assertStatus(200)->json();

        # assert response
        $this->assertEquals($response['type'], Advertisement::TYPE_SELL);
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertEquals($response['currency'], 'TWD');
        $this->assertTrue(Dec::eq($response['amount'], '5'));
        $this->assertTrue(Dec::eq($response['unit_price'], '28.9'));
        $this->assertTrue(!Dec::eq($response['price'], '0'));
        $this->assertTrue(!Dec::eq($response['fee'], '0'));
    }

    public function testCreateBuyAd()
    {
        $response = $this->apiAs(
            'POST',
            $this->link('api/ads'),
            [
                'type' => Advertisement::TYPE_BUY,
                'coin' => 'USDT-ERC20',
                'amount' => 40,
                'currency' => 'TWD',
                'unit_price' => 28.9,
                'min_trades' => 0,
                'payables' => [
                    'bank_account' => [static::$userA_bank->id],
                ],
                'security_code' => '123456',
                'min_limit' => 500,
                'max_limit' => 1156,
                'payment_window' => 10,
            ],
            [],
            static::$userA
        )->assertStatus(201)->json();

        # assert response
        $this->assertEquals($response['type'], Advertisement::TYPE_BUY);
        $this->assertEquals($response['user_id'], static::$userA->id);
        $this->assertEquals($response['username'], static::$userA->username);
        $this->assertEquals($response['status'], Advertisement::STATUS_AVAILABLE);
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertTrue(Dec::eq($response['amount'], '40'));
        $this->assertEquals($response['currency'], 'TWD');
        $this->assertTrue(Dec::eq($response['unit_price'], '28.9'));
        $this->assertTrue(Dec::eq($response['fee'], '0'));
        $this->assertTrue(Dec::eq($response['min_trades'], 0));
        $this->assertTrue(Dec::eq($response['min_limit'], 500));
        $this->assertTrue(Dec::eq($response['max_limit'], 1156));
        $this->assertTrue(Dec::eq($response['payment_window'], 10));
        $this->assertTrue($response['self']);
        $this->assertTrue(!is_null($response['payables']));
        $this->assertTrue(!is_null($response['owner']));
        $this->assertTrue(is_null($response['reference_id']));
        $this->assertTrue(is_null($response['terms']));
        $this->assertTrue(is_null($response['message']));

        # assert advertisement
        $ad = $this->AdvertisementRepo->find($response['id']);
        $this->assertEquals($ad->user_id, static::$userA->id);
        $this->assertEquals($ad->type, Advertisement::TYPE_BUY);
        $this->assertEquals($ad->status, Advertisement::STATUS_AVAILABLE);
        $this->assertEquals($ad->coin, 'USDT-ERC20');
        $this->assertTrue(Dec::eq($ad->amount, 40));
        $this->assertTrue(Dec::eq($ad->remaining_amount, 40));
        $this->assertTrue(Dec::eq($ad->fee, 0));
        $this->assertTrue(Dec::eq($ad->remaining_fee, 0));
        $this->assertEquals($ad->currency, 'TWD');
        $this->assertTrue(Dec::eq($ad->unit_price, 28.9));
        $this->assertTrue(Dec::eq($ad->min_limit, 500));
        $this->assertTrue(Dec::eq($ad->max_limit, 1156));
        $this->assertTrue(is_null($ad->terms));
        $this->assertTrue(is_null($ad->message));
        $this->assertTrue(Dec::eq($ad->min_trades, 0));
        $this->assertTrue(Dec::eq($ad->payment_window, 10));
        $this->assertTrue(is_null($ad->fee_setting_id));
        $this->assertTrue(is_null($ad->deleted_at));

        static::$buy_ad = $ad;
    }

    public function testCreateSellAd()
    {
        $response = $this->apiAs(
            'POST',
            $this->link('api/ads'),
            [
                'type' => Advertisement::TYPE_SELL,
                'coin' => 'USDT-ERC20',
                'amount' => 50,
                'currency' => 'TWD',
                'unit_price' => 28.9,
                'min_trades' => 0,
                'payables' => [
                    'bank_account' => [static::$userA_bank->id],
                ],
                'security_code' => '123456',
                'min_limit' => 500,
                'max_limit' => 1445,
                'payment_window' => 10,
            ],
            [],
            static::$userA
        )->assertStatus(201)->json();

        # assert response
        $this->assertEquals($response['type'], Advertisement::TYPE_SELL);
        $this->assertEquals($response['user_id'], static::$userA->id);
        $this->assertEquals($response['username'], static::$userA->username);
        $this->assertEquals($response['status'], Advertisement::STATUS_AVAILABLE);
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertTrue(Dec::eq($response['amount'], '50'));
        $this->assertEquals($response['currency'], 'TWD');
        $this->assertTrue(Dec::eq($response['unit_price'], '28.9'));
        $this->assertTrue(!Dec::eq($response['fee'], '0'));
        $this->assertTrue(Dec::eq($response['min_trades'], 0));
        $this->assertTrue(Dec::eq($response['min_limit'], 500));
        $this->assertTrue(Dec::eq($response['max_limit'], 1445));
        $this->assertTrue(Dec::eq($response['payment_window'], 10));
        $this->assertTrue($response['self']);
        $this->assertTrue(!is_null($response['payables']));
        $this->assertTrue(!is_null($response['owner']));
        $this->assertTrue(is_null($response['reference_id']));
        $this->assertTrue(is_null($response['terms']));
        $this->assertTrue(is_null($response['message']));

        # assert advertisement
        $ad = $this->AdvertisementRepo->find($response['id']);
        $this->assertEquals($ad->user_id, static::$userA->id);
        $this->assertEquals($ad->type, Advertisement::TYPE_SELL);
        $this->assertEquals($ad->status, Advertisement::STATUS_AVAILABLE);
        $this->assertEquals($ad->coin, 'USDT-ERC20');
        $this->assertTrue(Dec::eq($ad->amount, 50));
        $this->assertTrue(Dec::eq($ad->remaining_amount, 50));
        $this->assertTrue(!Dec::eq($ad->fee, 0));
        $this->assertTrue(!Dec::eq($ad->remaining_fee, 0));
        $this->assertEquals($ad->currency, 'TWD');
        $this->assertTrue(Dec::eq($ad->unit_price, 28.9));
        $this->assertTrue(Dec::eq($ad->min_limit, 500));
        $this->assertTrue(Dec::eq($ad->max_limit, 1445));
        $this->assertTrue(is_null($ad->terms));
        $this->assertTrue(is_null($ad->message));
        $this->assertTrue(Dec::eq($ad->min_trades, 0));
        $this->assertTrue(Dec::eq($ad->payment_window, 10));
        $this->assertTrue(!is_null($ad->fee_setting_id));
        $this->assertTrue(is_null($ad->deleted_at));

        static::$sell_ad = $ad;

        $account = $this->AccountRepo->findByUserCoin(static::$userA, 'USDT-ERC20');
        # assert account
        $this->assertTrue(!is_null($account));
        $this->assertEquals($account->user_id, static::$userA->id);
        $this->assertEquals($account->coin, 'USDT-ERC20');
        $this->assertTrue(Dec::eq($account->balance, 100));
        $this->assertTrue(Dec::eq($account->locked_balance, Dec::add($ad->amount, $ad->fee)));

        # assert transaction
        $transaction = $this->AdvertisementRepo->getTransaction($ad, Transaction::TYPE_ACTIVATE_ADVERTISEMENT);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_ACTIVATE_ADVERTISEMENT);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::add($ad->amount, $ad->fee)));
        $this->assertTrue(Dec::eq($transaction->balance, $account->locked_balance));
        $this->assertTrue($transaction->is_locked);
        $this->assertEquals($transaction->transactable_type, 'Advertisement');
        $this->assertEquals($transaction->transactable_id, $ad->id);
    }

    public function testUpdateStatusError()
    {
        # available needs security code
        $id = static::$buy_ad->id;
        $response = $this->apiAs(
            'PUT',
            $this->link("api/ads/{$id}/status"),
            [
                'status' => Advertisement::STATUS_AVAILABLE,
            ],
            [],
            static::$userA
        )->assertStatus(422)->json();
        $this->assertEquals($response['class'], 'ValidationException');

    }

    public function testUpdateBuyAdStatus()
    {
        # deactivate
        $id = static::$buy_ad->id;
        $response = $this->apiAs(
            'PUT',
            $this->link("api/ads/{$id}/status"),
            [
                'status' => Advertisement::STATUS_UNAVAILABLE,
            ],
            [],
            static::$userA
        )->assertStatus(201);
        static::$buy_ad->refresh();
        $this->assertEquals(static::$buy_ad->status, Advertisement::STATUS_UNAVAILABLE);

        # activate
        $response = $this->apiAs(
            'PUT',
            $this->link("api/ads/{$id}/status"),
            [
                'status' => Advertisement::STATUS_AVAILABLE,
                'security_code' => '123456',
            ],
            [],
            static::$userA
        )->assertStatus(201);
        static::$buy_ad->refresh();
        $this->assertEquals(static::$buy_ad->status, Advertisement::STATUS_AVAILABLE);
    }

    public function testUpdateSellAdStatus()
    {
        # deactivate
        $id = static::$sell_ad->id;
        $response = $this->apiAs(
            'PUT',
            $this->link("api/ads/{$id}/status"),
            [
                'status' => Advertisement::STATUS_UNAVAILABLE,
            ],
            [],
            static::$userA
        )->assertStatus(201);
        static::$sell_ad->refresh();
        $this->assertEquals(static::$sell_ad->status, Advertisement::STATUS_UNAVAILABLE);

        $account = $this->AccountRepo->findByUserCoin(static::$userA, 'USDT-ERC20');
        # assert transaction
        $transaction = $this->AdvertisementRepo->getTransaction(static::$sell_ad, Transaction::TYPE_DEACTIVATE_ADVERTISEMENT);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_DEACTIVATE_ADVERTISEMENT);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::add(static::$sell_ad->amount, static::$sell_ad->fee)->additiveInverse()));
        $this->assertTrue(Dec::eq($transaction->balance, $account->locked_balance));
        $this->assertTrue($transaction->is_locked);
        $this->assertEquals($transaction->transactable_type, 'Advertisement');
        $this->assertEquals($transaction->transactable_id, static::$sell_ad->id);

        # activate
        $response = $this->apiAs(
            'PUT',
            $this->link("api/ads/{$id}/status"),
            [
                'status' => Advertisement::STATUS_AVAILABLE,
                'security_code' => '123456',
            ],
            [],
            static::$userA
        )->assertStatus(201);
        static::$sell_ad->refresh();
        $this->assertEquals(static::$sell_ad->status, Advertisement::STATUS_AVAILABLE);

        $account->refresh();
        # assert transaction
        $transaction = $this->AdvertisementRepo->getTransaction(static::$sell_ad, Transaction::TYPE_ACTIVATE_ADVERTISEMENT);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_ACTIVATE_ADVERTISEMENT);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::add(static::$sell_ad->amount, static::$sell_ad->fee)));
        $this->assertTrue(Dec::eq($transaction->balance, $account->locked_balance));
        $this->assertTrue($transaction->is_locked);
        $this->assertEquals($transaction->transactable_type, 'Advertisement');
        $this->assertEquals($transaction->transactable_id, static::$sell_ad->id);
    }

    public function testEditAd()
    {
        $this->AdvertisementService->deactivate(static::$userA, static::$sell_ad);
        $id = static::$sell_ad->id;
        $response = $this->apiAs(
            'PUT',
            $this->link("api/ads/{$id}"),
            [
                'amount' => 50,
                'unit_price' => 28.9,
                'min_trades' => 0,
                'payables' => [
                    'bank_account' => [static::$userA_bank->id],
                ],
                'security_code' => '123456',
                'min_limit' => 500,
                'max_limit' => 1445,
                'payment_window' => 10,
            ],
            [],
            static::$userA
        )->assertStatus(201)->json();

        $this->assertEquals($response['type'], Advertisement::TYPE_SELL);
        $this->assertEquals($response['user_id'], static::$userA->id);
        $this->assertEquals($response['username'], static::$userA->username);
        $this->assertEquals($response['status'], Advertisement::STATUS_AVAILABLE);
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertTrue(Dec::eq($response['amount'], '50'));
        $this->assertEquals($response['currency'], 'TWD');
        $this->assertTrue(Dec::eq($response['unit_price'], '28.9'));
        $this->assertTrue(!Dec::eq($response['fee'], '0'));
        $this->assertTrue(Dec::eq($response['min_trades'], 0));
        $this->assertTrue(Dec::eq($response['min_limit'], 500));
        $this->assertTrue(Dec::eq($response['max_limit'], 1445));
        $this->assertTrue(Dec::eq($response['payment_window'], 10));
        $this->assertTrue($response['self']);
        $this->assertTrue(!is_null($response['payables']));
        $this->assertTrue(!is_null($response['owner']));
        $this->assertEquals($response['reference_id'], $id);
        $this->assertTrue(is_null($response['terms']));
        $this->assertTrue(is_null($response['message']));

        # assert advertisement
        $ad = $this->AdvertisementRepo->find($response['id']);
        $this->assertEquals($ad->user_id, static::$userA->id);
        $this->assertEquals($ad->reference_id, static::$sell_ad->id);
        $this->assertEquals($ad->type, Advertisement::TYPE_SELL);
        $this->assertEquals($ad->status, Advertisement::STATUS_AVAILABLE);
        $this->assertEquals($ad->coin, 'USDT-ERC20');
        $this->assertTrue(Dec::eq($ad->amount, 50));
        $this->assertTrue(Dec::eq($ad->remaining_amount, 50));
        $this->assertTrue(!Dec::eq($ad->fee, 0));
        $this->assertTrue(!Dec::eq($ad->remaining_fee, 0));
        $this->assertEquals($ad->currency, 'TWD');
        $this->assertTrue(Dec::eq($ad->unit_price, 28.9));
        $this->assertTrue(Dec::eq($ad->min_limit, 500));
        $this->assertTrue(Dec::eq($ad->max_limit, 1445));
        $this->assertTrue(is_null($ad->terms));
        $this->assertTrue(is_null($ad->message));
        $this->assertTrue(Dec::eq($ad->min_trades, 0));
        $this->assertTrue(Dec::eq($ad->payment_window, 10));
        $this->assertTrue(!is_null($ad->fee_setting_id));
        $this->assertTrue(is_null($ad->deleted_at));

        static::$sell_ad->refresh();
        $this->assertEquals(static::$sell_ad->status, Advertisement::STATUS_DELETED);
        $this->assertTrue(!is_null(static::$sell_ad->deleted_at));

        static::$sell_ad = $ad;
    }

    public function testDeleteAdError()
    {
        # delete when ad is available
        $id = static::$buy_ad->id;
        $response = $this->apiAs(
            'DELETE',
            $this->link("api/ads/{$id}"),
            [],
            [],
            static::$userA
        )->assertStatus(410)->json();
        $this->assertEquals($response['class'], 'UnavailableStatusError');
    }

    public function testDeleteBuyAd()
    {
        $this->AdvertisementService->deactivate(static::$userA, static::$buy_ad);
        $id = static::$buy_ad->id;
        $response = $this->apiAs(
            'DELETE',
            $this->link("api/ads/{$id}"),
            [],
            [],
            static::$userA
        )->assertStatus(204);
        static::$buy_ad->refresh();
        $this->assertEquals(static::$buy_ad->status, Advertisement::STATUS_DELETED);
        $this->assertTrue(!is_null(static::$buy_ad->deleted_at));
    }

    public function testDeleteSellAd()
    {
        $this->AdvertisementService->deactivate(static::$userA, static::$sell_ad);
        $id = static::$sell_ad->id;
        $response = $this->apiAs(
            'DELETE',
            $this->link("api/ads/{$id}"),
            [],
            [],
            static::$userA
        )->assertStatus(204);
        static::$sell_ad->refresh();
        $this->assertEquals(static::$sell_ad->status, Advertisement::STATUS_DELETED);
        $this->assertTrue(!is_null(static::$sell_ad->deleted_at));
    }
}
