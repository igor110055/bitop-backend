<?php

namespace Tests\Feature\Api;

use Dec\Dec;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Repos\Interfaces\{
    TransferRepo,
    AccountRepo,
    VerificationRepo,
};
use App\Services\{
    TransferServiceInterface,
};
use App\Models\{
    User,
    Transfer,
    Transaction,
    Verification,
};

class TransferTest extends TestCase
{
    use ApiTestTrait;

    protected static $setUpHasRunOnce = false;
    protected static $userA;
    protected static $userB;
    protected static $transfer;
    protected static $verification_id;
    protected static $verification_code;

    # called once for each tests
    public function setUp() : void
    {
        parent::setUp();
        if (!static::$setUpHasRunOnce) {
            static::$userA = User::factory()->create();
            static::$userB = User::factory()->create();

            $this->TransferRepo = app()->make(TransferRepo::class);
            $this->AccountRepo = app()->make(AccountRepo::class);
            $this->AccountRepo->allByUserOrCreate(static::$userA);
            $this->AccountRepo->allByUserOrCreate(static::$userB);

            # init
            $this->AccountRepo->deposit(static::$userA, 'USDT-ERC20', '10');

            static::$setUpHasRunOnce = true;
        } else {
            $this->TransferRepo = app()->make(TransferRepo::class);
            $this->AccountRepo = app()->make(AccountRepo::class);
        }
    }

    public function testCreateTransferErrors()
    {
        # wrong security code
        $response = $this->apiAs(
            'POST',
            $this->link('api/transfers'),
            [
                'dst_user_id' => static::$userB->id,
                'coin' => 'USDT-ERC20',
                'amount' => '5',
                'security_code' => 'wrong-security-code',
            ],
            [],
            static::$userA
        )->assertStatus(401)->json();
        $this->assertEquals($response['class'], 'Auth\WrongSecurityCodeError');

        # insufficient balance
        $response = $this->apiAs(
            'POST',
            $this->link('api/transfers'),
            [
                'dst_user_id' => static::$userB->id,
                'coin' => 'USDT-ERC20',
                'amount' => '11',
                'security_code' => '123456',
            ],
            [],
            static::$userA
        )->assertStatus(400)->json();
        $this->assertEquals($response['class'], 'Account\InsufficientBalanceError');

        # validation exception
        $response = $this->apiAs(
            'POST',
            $this->link('api/transfers'),
            [
                'dst_user_id' => static::$userB->id,
                'coin' => 'USDT-ERC20',
                'amount' => 'abc',
                'security_code' => '123456',
            ],
            [],
            static::$userA
        )->assertStatus(422)->json();
        $this->assertEquals($response['class'], 'ValidationException');
    }

    public function testCreateTransfer()
    {
        $response = $this->apiAs(
            'POST',
            $this->link('api/transfers'),
            [
                'dst_user_id' => static::$userB->id,
                'coin' => 'USDT-ERC20',
                'amount' => '5',
                'security_code' => '123456',
            ],
            [],
            static::$userA
        )->assertStatus(200)->json();

        # assert response
        $this->assertEquals($response['status'], Transfer::STATUS_PROCESSING);
        $this->assertEquals($response['user_id'], static::$userB->id);
        $this->assertEquals($response['username'], static::$userB->username);
        $this->assertEquals($response['side'], 'src');
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertTrue(Dec::eq($response['amount'], '5'));
        $this->assertTrue(is_null($response['confirmed_at']));
        $this->assertTrue(is_null($response['canceled_at']));

        # assert transaction
        static::$transfer = $this->TransferRepo->find($response['id']);
        $transaction = $this->TransferRepo->getTransaction(static::$transfer, Transaction::TYPE_TRANSFER_LOCK);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, optional($this->AccountRepo->findByUserCoin(static::$userA, 'USDT-ERC20'))->id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_TRANSFER_LOCK);
        $this->assertTrue(Dec::eq($transaction->amount, '5'));
        $this->assertTrue(Dec::eq($transaction->balance, '5'));
        $this->assertTrue(is_null($transaction->message));
        $this->assertTrue($transaction->is_locked);
        $this->assertEquals($transaction->transactable_type, 'Transfer');
        $this->assertEquals($transaction->transactable_id, static::$transfer->id);

        # assert verification
        $verification = app()->make(VerificationRepo::class)->getAvailable(static::$transfer);
        $this->assertTrue(!is_null($verification));
        $this->assertEquals($verification->verificable_type, 'Transfer');
        $this->assertEquals($verification->verificable_id, static::$transfer->id);
        $this->assertEquals($verification->type, Verification::TYPE_TRANSFER_CONFIRMATION);
        $this->assertEquals($verification->data, static::$userA->email);

        static::$verification_id = $verification->id;
        static::$verification_code = $verification->code;
    }

    public function testConfirmTransferErrorPages()
    {
        $id = 'abc';
        $code = 'abc';
        $response = $this->api(
            'GET',
            $this->link("api/transfers/confirm/{$id}/{$code}"),
            [],
            []
        )->assertStatus(302)->assertRedirect(url('/'));
    }

    public function testConfirmTransfer()
    {
        $id = static::$verification_id;
        $code = static::$verification_code;
        $response = $this->api(
            'GET',
            $this->link("api/transfers/confirm/{$id}/{$code}"),
            [],
            []
        )->assertStatus(302)->assertRedirect(url('/transfer-confirmation?status=1'));

        static::$transfer->refresh();
        $this->assertTrue(!is_null(static::$transfer->confirmed_at));
        $this->assertTrue(is_null(static::$transfer->canceled_at));

        # unlock transaction
        $transaction = $this->TransferRepo->getTransaction(static::$transfer, Transaction::TYPE_TRANSFER_UNLOCK);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, optional($this->AccountRepo->findByUserCoin(static::$userA, 'USDT-ERC20'))->id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_TRANSFER_UNLOCK);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::create(5)->additiveInverse()));
        $this->assertTrue(Dec::eq($transaction->balance, '0'));
        $this->assertTrue(is_null($transaction->message));
        $this->assertTrue($transaction->is_locked);
        $this->assertEquals($transaction->transactable_type, 'Transfer');
        $this->assertEquals($transaction->transactable_id, static::$transfer->id);

        # transfer out transaction
        $transaction = $this->TransferRepo->getTransaction(static::$transfer, Transaction::TYPE_TRANSFER_OUT);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, optional($this->AccountRepo->findByUserCoin(static::$userA, 'USDT-ERC20'))->id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_TRANSFER_OUT);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::create(5)->additiveInverse()));
        $this->assertTrue(Dec::eq($transaction->balance, '5'));
        $this->assertTrue(is_null($transaction->message));
        $this->assertTrue($transaction->is_locked === false);
        $this->assertEquals($transaction->transactable_type, 'Transfer');
        $this->assertEquals($transaction->transactable_id, static::$transfer->id);

        # transfer in transaction
        $transaction = $this->TransferRepo->getTransaction(static::$transfer, Transaction::TYPE_TRANSFER_IN);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, optional($this->AccountRepo->findByUserCoin(static::$userB, 'USDT-ERC20'))->id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_TRANSFER_IN);
        $this->assertTrue(Dec::eq($transaction->amount, '5'));
        $this->assertTrue(Dec::eq($transaction->balance, '5'));
        $this->assertTrue(is_null($transaction->message));
        $this->assertTrue($transaction->is_locked === false);
        $this->assertEquals($transaction->transactable_type, 'Transfer');
        $this->assertEquals($transaction->transactable_id, static::$transfer->id);
    }
}
