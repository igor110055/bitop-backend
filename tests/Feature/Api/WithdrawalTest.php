<?php

namespace Tests\Feature\Api;

use Dec\Dec;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Http\Controllers\Api\WithdrawalController;
use App\Repos\Interfaces\{
    WithdrawalRepo,
    AccountRepo,
    LimitationRepo,
    VerificationRepo,
    WalletBalanceRepo,
};
use App\Services\{
    AccountServiceInterface,
    AccountService,
    WalletServiceInterface,
};
use App\Models\{
    User,
    Withdrawal,
    Transaction,
    Verification,
};

class WithdrawalTest extends TestCase
{
    use ApiTestTrait;

    protected static $setUpHasRunOnce = false;
    protected static $user;
    protected static $withdrawal;
    protected static $verification;
    protected static $wallet_id;

    public function setUp() : void
    {
        parent::setUp();
        if (!static::$setUpHasRunOnce) {
            static::$user = User::factory()->create();

            $this->WithdrawalRepo = app()->make(WithdrawalRepo::class);
            $this->AccountRepo = app()->make(AccountRepo::class);
            $this->AccountRepo->allByUserOrCreate(static::$user);

            # init
            $this->AccountRepo->deposit(static::$user, 'USDT-ERC20', '10');

            static::$setUpHasRunOnce = true;
        } else {
            $this->WithdrawalRepo = app()->make(WithdrawalRepo::class);
            $this->AccountRepo = app()->make(AccountRepo::class);
        }
    }

    public function testPreview()
    {
        # suff
        $response = $this->apiAs(
            'GET',
            $this->link('api/withdrawals/preview'),
            [
                'coin' => 'USDT-ERC20',
                'amount' => '8',
            ],
            [],
            static::$user
        )->assertStatus(200)->json();
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertTrue(Dec::eq($response['amount'], '8'));
        $this->assertTrue(!is_null($response['fee']));
        $this->assertTrue($response['out_of_limits'] === false);
        $this->assertTrue($response['balance_insufficient'] === false);

        # insuff
        $response = $this->apiAs(
            'GET',
            $this->link('api/withdrawals/preview'),
            [
                'coin' => 'USDT-ERC20',
                'amount' => '10.1',
            ],
            [],
            static::$user
        )->assertStatus(200)->json();
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertTrue(Dec::eq($response['amount'], '10.1'));
        $this->assertTrue(!is_null($response['fee']));
        $this->assertTrue($response['out_of_limits'] === false);
        $this->assertTrue($response['balance_insufficient'] === true);
    }

    public function testNotDuplicated()
    {
        $response = $this->apiAs(
            'GET',
            $this->link('api/withdrawals/preview'),
            [
                'coin' => 'USDT-ERC20',
                'amount' => '8',
                'address' => 'abc',
            ],
            [],
            static::$user
        )->assertStatus(200)->json();
    }

    public function testCreateWithdrawalErrors()
    {
        # mockery
        $ws = \Mockery::mock(WalletServiceInterface::class);
        $ws->shouldReceive('getAddressValidation')->andReturn(true);

        $this->app->instance(WalletServiceInterface::class, $ws);

        # wrong security code
        $response = $this->apiAs(
            'POST',
            $this->link('api/withdrawals'),
            [
                'coin' => 'USDT-ERC20',
                'amount' => '8',
                'address' => 'abc',
                'security_code' => '12345',
            ],
            [],
            static::$user
        )->assertStatus(401)->json();
        $this->assertEquals($response['class'], 'Auth\WrongSecurityCodeError');

        # amount < 0
        $response = $this->apiAs(
            'POST',
            $this->link('api/withdrawals'),
            [
                'coin' => 'USDT-ERC20',
                'amount' => '-1',
                'address' => 'abc',
                'security_code' => '123456',
            ],
            [],
            static::$user
        )->assertStatus(400)->json();
        $this->assertEquals($response['class'], 'WithdrawLimitationError');

        $lr = \Mockery::mock(LimitationRepo::class);
        $lr->shouldReceive('checkLimitation')->andReturn(true);
        $this->app->instance(LimitationRepo::class, $lr);

        # insuff
        $response = $this->apiAs(
            'POST',
            $this->link('api/withdrawals'),
            [
                'coin' => 'USDT-ERC20',
                'amount' => '11',
                'address' => 'abc',
                'security_code' => '123456',
            ],
            [],
            static::$user
        )->assertStatus(400)->json();
        $this->assertEquals($response['class'], 'Account\InsufficientBalanceError');
    }

    public function testCreateWithdrawal()
    {
        $ws = \Mockery::mock(WalletServiceInterface::class);
        $ws->shouldReceive('getAddressValidation')->andReturn(true);
        $lr = \Mockery::mock(LimitationRepo::class);
        $lr->shouldReceive('checkLimitation')->andReturn(true);
        $this->app->instance(WalletServiceInterface::class, $ws);
        $this->app->instance(LimitationRepo::class, $lr);

        $response = $this->apiAs(
            'POST',
            $this->link('api/withdrawals'),
            [
                'coin' => 'USDT-ERC20',
                'amount' => '8',
                'address' => 'random_address',
                'security_code' => '123456',
            ],
            [],
            static::$user
        )->assertStatus(201)->json();

        # assert response
        $this->assertEquals($response['user_id'], static::$user->id);
        $this->assertEquals($response['account_id'], optional($this->AccountRepo->findByUserCoin(static::$user, 'USDT-ERC20'))->id);
        $this->assertEquals($response['status'], Withdrawal::STATUS_PROCESSING);
        $this->assertEquals($response['coin'], 'USDT-ERC20');
        $this->assertEquals($response['address'], 'random_address');
        $this->assertTrue(Dec::eq($response['amount'], '8'));
        $this->assertTrue(is_null($response['tag']));
        $this->assertTrue(is_null($response['message']));
        $this->assertTrue(is_null($response['confirmed_at']));

        $account_id = optional($this->AccountRepo->findByUserCoin(static::$user, 'USDT-ERC20'))->id;
        # assert withdrawal
        static::$withdrawal = $this->WithdrawalRepo->find($response['id']);
        $this->assertTrue(!is_null(static::$withdrawal));
        $this->assertEquals(static::$withdrawal->user_id, static::$user->id);
        $this->assertEquals(static::$withdrawal->account_id, $account_id);
        $this->assertTrue(is_null(static::$withdrawal->wallet_id));
        $this->assertTrue(is_null(static::$withdrawal->transaction));
        $this->assertTrue(is_null(static::$withdrawal->tag));
        $this->assertTrue(Dec::eq(static::$withdrawal->amount, '8'));
        $this->assertTrue(is_null(static::$withdrawal->src_amount));
        $this->assertTrue(is_null(static::$withdrawal->dst_amount));
        $this->assertTrue(!is_null(static::$withdrawal->fee));
        $this->assertTrue(is_null(static::$withdrawal->wallet_fee));
        $this->assertTrue(is_null(static::$withdrawal->wallet_fee_coin));
        $this->assertTrue(static::$withdrawal->is_full_payment);
        $this->assertTrue(!is_null(static::$withdrawal->fee_setting_id));
        $this->assertTrue(is_null(static::$withdrawal->message));
        $this->assertTrue(is_null(static::$withdrawal->response));
        $this->assertTrue(is_null(static::$withdrawal->callback_response));
        $this->assertTrue(is_null(static::$withdrawal->confirmed_at));
        $this->assertTrue(is_null(static::$withdrawal->submitted_confirmed_at));
        $this->assertTrue(is_null(static::$withdrawal->notified_at));
        $this->assertTrue(is_null(static::$withdrawal->canceled_at));
        $this->assertTrue(!is_null(static::$withdrawal->expired_at));

        # assert transaction
        $transaction = $this->WithdrawalRepo->getTransaction(static::$withdrawal, Transaction::TYPE_WALLET_WITHDRAWAL_LOCK);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, $account_id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_WALLET_WITHDRAWAL_LOCK);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::add(static::$withdrawal->amount, static::$withdrawal->fee)));
        $this->assertTrue(Dec::eq($transaction->balance, Dec::add(static::$withdrawal->amount, static::$withdrawal->fee)));
        $this->assertTrue($transaction->is_locked);
        $this->assertEquals($transaction->transactable_type, 'Withdrawal');
        $this->assertEquals($transaction->transactable_id, static::$withdrawal->id);

        # assert verification
        static::$verification = app()->make(VerificationRepo::class)->getAvailable(static::$withdrawal);
        $this->assertTrue(!is_null(static::$verification));
        $this->assertEquals(static::$verification->verificable_type, 'Withdrawal');
        $this->assertEquals(static::$verification->verificable_id, static::$withdrawal->id);
        $this->assertEquals(static::$verification->type, Verification::TYPE_WITHDRAWAL_CONFIRMATION);
        $this->assertEquals(static::$verification->data, static::$user->email);
    }

    public function testConfirmWithdrawalErrorPages()
    {
        $id = 'abc';
        $code = 'abc';
        $response = $this->api(
            'GET',
            $this->link("api/withdrawals/confirm/{$id}/{$code}"),
            [],
            []
        )->assertStatus(302)->assertRedirect(url('/'));
    }

    public function testConfirmWithdrawal()
    {
        $id = static::$verification->id;
        $code = static::$verification->code;
        $response = $this->api(
            'GET',
            $this->link("api/withdrawals/confirm/{$id}/{$code}"),
            [],
            []
        )->assertStatus(302)->assertRedirect(url('/withdrawal-confirmation?status=1'));

        # verification
        static::$verification->refresh();
        $this->assertTrue(!is_null(static::$verification->verified_at));

        $account_id = optional($this->AccountRepo->findByUserCoin(static::$user, 'USDT-ERC20'))->id;
        # withdrawal
        static::$withdrawal->refresh();
        $this->assertEquals(static::$withdrawal->user_id, static::$user->id);
        $this->assertEquals(static::$withdrawal->account_id, $account_id);
        $this->assertTrue(is_null(static::$withdrawal->wallet_id));
        $this->assertTrue(is_null(static::$withdrawal->transaction));
        $this->assertTrue(is_null(static::$withdrawal->tag));
        $this->assertTrue(Dec::eq(static::$withdrawal->amount, '8'));
        $this->assertTrue(is_null(static::$withdrawal->src_amount));
        $this->assertTrue(is_null(static::$withdrawal->dst_amount));
        $this->assertTrue(!is_null(static::$withdrawal->fee));
        $this->assertTrue(is_null(static::$withdrawal->wallet_fee));
        $this->assertTrue(is_null(static::$withdrawal->wallet_fee_coin));
        $this->assertTrue(static::$withdrawal->is_full_payment);
        $this->assertTrue(!is_null(static::$withdrawal->fee_setting_id));
        $this->assertTrue(is_null(static::$withdrawal->message));
        $this->assertTrue(is_null(static::$withdrawal->response));
        $this->assertTrue(is_null(static::$withdrawal->callback_response));
        $this->assertTrue(!is_null(static::$withdrawal->confirmed_at));
        $this->assertTrue(is_null(static::$withdrawal->submitted_confirmed_at));
        $this->assertTrue(is_null(static::$withdrawal->notified_at));
        $this->assertTrue(is_null(static::$withdrawal->canceled_at));
        $this->assertTrue(!is_null(static::$withdrawal->expired_at));
    }

    public function testSubmitWithdraw()
    {
        $ws = \Mockery::mock(WalletServiceInterface::class);
        $ws->shouldReceive('verifyRequest')->andReturn(true);
        $ws->shouldReceive('checkWithdrawalResponseParameter')->andReturn(true);
        static::$wallet_id = generate_code(24, 'alpha');
        $response = [
            'id' => static::$wallet_id,
            'address' => static::$withdrawal->address,
            'account' => config('services.wallet.account'),
            'transaction' => '0xabc',
            'currency' => config('services.wallet.coin_map')[static::$withdrawal->coin],
            'amount' => static::$withdrawal->amount,
            'src_amount' => '8',
            'dst_amount' => '8',
            'fee' => '0.0002',
            'fee_currency' => 'eth',
            'is_full_payment' => true,
            'callback' => static::$withdrawal->getCallback(),
            'client_id' => static::$withdrawal->id,
        ];
        $ws->shouldReceive('withdrawal')->andReturn($response);
        $this->app->instance(WalletServiceInterface::class, $ws);

        $as = app()->make(AccountServiceInterface::class);
        $as->submitWithdrawal(static::$withdrawal);

        $account_id = optional($this->AccountRepo->findByUserCoin(static::$user, 'USDT-ERC20'))->id;
        static::$withdrawal->refresh();
        # assert withdrawal
        $this->assertEquals(static::$withdrawal->user_id, static::$user->id);
        $this->assertEquals(static::$withdrawal->account_id, $account_id);
        $this->assertEquals(static::$withdrawal->wallet_id, static::$wallet_id);
        $this->assertEquals(static::$withdrawal->transaction, $response['transaction']);
        $this->assertTrue(is_null(static::$withdrawal->tag));
        $this->assertTrue(Dec::eq(static::$withdrawal->amount, '8'));
        $this->assertTrue(Dec::eq(static::$withdrawal->src_amount, $response['src_amount']));
        $this->assertTrue(Dec::eq(static::$withdrawal->dst_amount, $response['dst_amount']));
        $this->assertTrue(!is_null(static::$withdrawal->fee));
        $this->assertTrue(Dec::eq(static::$withdrawal->wallet_fee, $response['fee']));
        $this->assertEquals(static::$withdrawal->wallet_fee_coin, $response['fee_currency']);
        $this->assertTrue(static::$withdrawal->is_full_payment);
        $this->assertTrue(!is_null(static::$withdrawal->fee_setting_id));
        $this->assertTrue(is_null(static::$withdrawal->message));
        $this->assertTrue(!is_null(static::$withdrawal->response));
        $this->assertTrue(is_null(static::$withdrawal->callback_response));
        $this->assertTrue(!is_null(static::$withdrawal->confirmed_at));
        $this->assertTrue(!is_null(static::$withdrawal->submitted_at));
        $this->assertTrue(!is_null(static::$withdrawal->submitted_confirmed_at));
        $this->assertTrue(is_null(static::$withdrawal->notified_at));
        $this->assertTrue(is_null(static::$withdrawal->canceled_at));
        $this->assertTrue(!is_null(static::$withdrawal->expired_at));

        # assert transaction
        $transaction = $this->WithdrawalRepo->getTransaction(static::$withdrawal, Transaction::TYPE_WALLET_WITHDRAWAL_UNLOCK);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, $account_id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_WALLET_WITHDRAWAL_UNLOCK);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::add(static::$withdrawal->amount, static::$withdrawal->fee)->additiveInverse()));
        $this->assertTrue(Dec::eq($transaction->balance, '0'));
        $this->assertTrue($transaction->is_locked);
        $this->assertEquals($transaction->transactable_type, 'Withdrawal');
        $this->assertEquals($transaction->transactable_id, static::$withdrawal->id);

        $transaction = $this->WithdrawalRepo->getTransaction(static::$withdrawal, Transaction::TYPE_WALLET_WITHDRAWAL);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, $account_id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_WALLET_WITHDRAWAL);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::create(static::$withdrawal->amount)->additiveInverse()));
        $this->assertTrue(Dec::eq($transaction->balance, '2'));
        $this->assertTrue($transaction->is_locked === false);
        $this->assertEquals($transaction->transactable_type, 'Withdrawal');
        $this->assertEquals($transaction->transactable_id, static::$withdrawal->id);

        $transaction = $this->WithdrawalRepo->getTransaction(static::$withdrawal, Transaction::TYPE_WITHDRAWAL_FEE);
        $this->assertTrue(!is_null($transaction));
        $this->assertEquals($transaction->account_id, $account_id);
        $this->assertEquals($transaction->coin, 'USDT-ERC20');
        $this->assertEquals($transaction->type, Transaction::TYPE_WITHDRAWAL_FEE);
        $this->assertTrue(Dec::eq($transaction->amount, Dec::create(static::$withdrawal->fee)->additiveInverse()));
        $this->assertTrue(Dec::eq($transaction->balance, Dec::sub(2, static::$withdrawal->fee)));
        $this->assertTrue($transaction->is_locked === false);
        $this->assertEquals($transaction->transactable_type, 'Withdrawal');
        $this->assertEquals($transaction->transactable_id, static::$withdrawal->id);

        # TODO wallet balance
    }

    public function testWithdrawlCallback()
    {
        $withdrawal = static::$withdrawal;
        $ws = \Mockery::mock(WalletServiceInterface::class);
        $ws->shouldReceive('verifyRequest')->andReturn(true);
        $this->app->instance(WalletServiceInterface::class, $ws);

        $response = [
            'id' => static::$wallet_id,
            'address' => static::$withdrawal->address,
            'account' => config('services.wallet.account'),
            'transaction' => '0xabc',
            'currency' => config('services.wallet.coin_map')[static::$withdrawal->coin],
            'amount' => static::$withdrawal->amount,
            'src_amount' => '8',
            'dst_amount' => '8',
            'fee' => '0.0001',
            'fee_currency' => 'eth',
            'is_full_payment' => true,
            'callback' => static::$withdrawal->getCallback(),
            'client_id' => static::$withdrawal->id,
        ];

        $this->api(
            'POST',
            $this->link("api/wallet/withdrawal-callback/{$withdrawal->id}"),
            $response,
            [],
            static::$user
        )->assertStatus(200);

        $account_id = optional($this->AccountRepo->findByUserCoin(static::$user, 'USDT-ERC20'))->id;
        static::$withdrawal->refresh();
        # assert withdrawal
        $this->assertEquals(static::$withdrawal->user_id, static::$user->id);
        $this->assertEquals(static::$withdrawal->account_id, $account_id);
        $this->assertEquals(static::$withdrawal->wallet_id, static::$wallet_id);
        $this->assertEquals(static::$withdrawal->transaction, $response['transaction']);
        $this->assertTrue(is_null(static::$withdrawal->tag));
        $this->assertTrue(Dec::eq(static::$withdrawal->amount, '8'));
        $this->assertTrue(Dec::eq(static::$withdrawal->src_amount, $response['src_amount']));
        $this->assertTrue(Dec::eq(static::$withdrawal->dst_amount, $response['dst_amount']));
        $this->assertTrue(!is_null(static::$withdrawal->fee));
        $this->assertEquals(static::$withdrawal->wallet_fee_coin, $response['fee_currency']);
        $this->assertTrue(static::$withdrawal->is_full_payment);
        $this->assertTrue(!is_null(static::$withdrawal->fee_setting_id));
        $this->assertTrue(is_null(static::$withdrawal->message));
        $this->assertTrue(!is_null(static::$withdrawal->response));
        $this->assertTrue(!is_null(static::$withdrawal->callback_response));
        $this->assertTrue(!is_null(static::$withdrawal->confirmed_at));
        $this->assertTrue(!is_null(static::$withdrawal->submitted_at));
        $this->assertTrue(!is_null(static::$withdrawal->submitted_confirmed_at));
        $this->assertTrue(!is_null(static::$withdrawal->notified_at));
        $this->assertTrue(is_null(static::$withdrawal->canceled_at));
        $this->assertTrue(!is_null(static::$withdrawal->expired_at));

        # TODO wallet balance
    }
}
