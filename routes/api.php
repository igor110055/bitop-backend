<?php

use Dingo\Api\Routing\Router;

use App\Http\Controllers\Api\{
    AuthController,
    MeController,
    AdvertisementController,
    BankController,
    BankAccountController,
    DepositController,
    ExchangeController,
    OrderController,
    TransferController,
    AccountController,
    TransactionController,
    UserController,
    GroupController,
    ContactController,
    InfoController,
    LimitationController,
    WithdrawalController,
    AddressController,
    AnnouncementController,
    WfpayController,
};

$api = app(Router::class);
$api->version('v1', function ($api) {

    # /api/auth/*
    $auth = AuthController::class;
    $api->group(['prefix' => 'auth'], function ($api) use ($auth) {
        $api->post('login', "{$auth}@login");
        $api->post('logout', "{$auth}@logout");
        $api->get('token', "{$auth}@refresh");
        $api->post('email-verification', "{$auth}@sendEmailVerification");
        //$api->post('mobile-verification', "{$auth}@sendMobileVerification");
        $api->post('password-verification', "{$auth}@sendPasswordVerification");
        $api->post('password-recovery', "{$auth}@recoverPassword");
        $api->post('register', "{$auth}@register");
        $api->post('upload', "{$auth}@uploadFiles");
        $api->post('request-verify', "{$auth}@requestVerifyIdentity");
        $api->put('change-password', "{$auth}@resetPassword");
        $api->post('security-code-verification', "{$auth}@sendSecurityCodeVerification");
        $api->post('security-code-recovery', "{$auth}@recoverSecurityCode");
        $api->post('reset-email-verification', "{$auth}@sendResetEmailVerification");
        //$api->post('reset-mobile-verification', "{$auth}@sendResetMobileVerification");
        $api->post('reset-email', "{$auth}@resetEmail");
        //$api->post('reset-mobile', "{$auth}@resetMobile");
        $api->post('two-factor-auth/pre-activate', "{$auth}@preActivateTFA");
        $api->post('two-factor-auth/activate', "{$auth}@activateTFA");
        $api->post('two-factor-auth/deactivate-verification', "{$auth}@sendDeactivateTFAVerification");
        $api->post('two-factor-auth/deactivate', "{$auth}@deactivateTFA");
        $api->get('device-token', "{$auth}@changeDeviceTokenStatus");
    });

    $me = MeController::class;
    $api->group(['prefix' => 'me'], function ($api) use ($me) {
        $api->get('/', "{$me}@show");
        $api->put('/', "{$me}@update");
        $api->get('/invitation_info', "{$me}@getInvitationInfo");
    });

    # /api/users/*
    $user = UserController::class;
    $api->group(['prefix' => 'users'], function ($api) use ($user) {
        $api->get('/{id}', "{$user}@show");
    });

    # /api/ads/*
    $ad = AdvertisementController::class;
    $api->group(['prefix' => 'ads'], function ($api) use ($ad) {
        $api->get('/', "{$ad}@getAds");
        $api->get('/preview', "{$ad}@preview");
        $api->get('{id}', "{$ad}@getAd");
        $api->post('/', "{$ad}@create");
        $api->put('{id}', "{$ad}@edit");
        $api->put('{id}/status', "{$ad}@updateStatus");
        $api->delete('{id}', "{$ad}@delete");
    });

    # /api/orders/*
    $order = OrderController::class;
    $api->group(['prefix' => 'orders'], function ($api) use ($order) {
        $api->get('/', "{$order}@index");
        $api->get('/preview', "{$order}@previewTrade");
        $api->get('/express-matches', "{$order}@matchExpressAds");
        $api->get('/express-settings', "{$order}@getExpressTradeSettings");
        $api->get('{id}', "{$order}@show");
        $api->post('/', "{$order}@trade");
        $api->post('/express', "{$order}@tradeExpress");
        $api->post('{id}/confirm-verification', "{$order}@sendConfirmVerification");
        $api->put('{id}/claim', "{$order}@claim");
        $api->put('{id}/revoke', "{$order}@revoke");
        $api->put('{id}/confirm', "{$order}@confirm");
        $api->delete('{id}', "{$order}@cancel");
    });

    # /api/banks/*
    $bank = BankController::class;
    $api->group(['prefix' => 'banks'], function ($api) use ($bank) {
        $api->get('currencies', "{$bank}@getCurrencies");
        $api->get('/', "{$bank}@getBankList");
    });

    # /api/bank-accounts/*
    $bank_account = BankAccountController::class;
    $api->group(['prefix' => 'bank-accounts'], function ($api) use ($bank_account) {
        $api->get('/', "{$bank_account}@index");
        $api->post('/', "{$bank_account}@create");
        $api->delete('{id}', "{$bank_account}@delete");
        $api->get('nationalities-currencies', "{$bank_account}@getNationalitiesCurrencies");
    });

    # /api/exchanges/*
    $exchange = ExchangeController::class;
    $api->group(['prefix' => 'exchanges'], function ($api) use ($exchange) {
        $api->get('/', "{$exchange}@getCoinPrice");
    });

    # /api/transfers/*
    $transfer = TransferController::class;
    $api->group(['prefix' => 'transfers'], function ($api) use ($transfer) {
        $api->get('/', "{$transfer}@getTransfers");
        $api->post('/', "{$transfer}@create");
        $api->get('/{id}', "{$transfer}@show");
        $api->get('/confirm/{id}/{code}', "{$transfer}@confirm");
    });

    # /api/withdrawals/*
    $withdrawal = WithdrawalController::class;
    $api->group(['prefix' => 'withdrawals'], function ($api) use ($withdrawal) {
        $api->get('preview', "{$withdrawal}@preview");
        $api->get('duplicate-check', "{$withdrawal}@duplicateCheck");
        $api->post('/', "{$withdrawal}@create");
        $api->get('/', "{$withdrawal}@getWithdrawals");
        $api->get('{id}', "{$withdrawal}@show");
        $api->get('/confirm/{id}/{code}', "{$withdrawal}@confirm");
    });

    # /api/deposits/*
    $deposit = DepositController::class;
    $api->group(['prefix' => 'deposits'], function ($api) use ($deposit) {
        $api->get('address', "{$deposit}@getAddress");
        $api->get('/', "{$deposit}@getDeposits");
        $api->get('{id}', "{$deposit}@show");
    });

    # /api/wallet/*
    $api->group(['prefix' => 'wallet'], function ($api) use ($deposit, $withdrawal) {
        $api->post('manual-withdrawal-callback', "{$withdrawal}@manualWithdrawalCallback");
        $api->post('withdrawal-callback/{id}', "{$withdrawal}@withdrawalCallback");
        $api->post('manual-deposit-callback', "{$deposit}@manualDepositCallback");
        $api->post('deposit-callback/{id}', "{$deposit}@depositCallback");
        $api->post('payin-callback/{id}', "{$deposit}@payinCallback");
        $api->post('payout-callback/{id}', "{$deposit}@payoutCallback");
        $api->post('approvement-callback/{id}', "{$deposit}@approvementCallback");
    });

    # /api/accounts/*
    $account = AccountController::class;
    $api->group(['prefix' => 'accounts'], function ($api) use ($account) {
        $api->get('/', "{$account}@index");
    });

    # /api/transactions/*
    $transaction = TransactionController::class;
    $api->group(['prefix' => 'transactions'], function ($api) use ($transaction) {
        $api->get('/', "{$transaction}@getTransactionList");
        $api->get('{id}', "{$transaction}@show");
    });

    # /api/groups/*
    $group = GroupController::class;
    $api->group(['prefix' => 'groups'], function ($api) use ($group) {
        $api->get('/', "{$group}@index");
        $api->post('/application', "{$group}@applyNewGroup");
        $api->get('/{id}/members', "{$group}@getGroupMembers");
        $api->post('/{id}/invitation', "{$group}@createInvitation");
    });

    # /api/contacts/*
    $contact = ContactController::class;
    $api->group(['prefix' => 'contacts'], function ($api) use ($contact) {
        $api->get('/', "{$contact}@index");
        $api->post('/', "{$contact}@create");
        $api->delete('/', "{$contact}@delete");
    });

    # /api/infos
    $info = InfoController::class;
    $api->group(['prefix' => 'infos'], function ($api) use ($info) {
        $api->get('/version', "{$info}@getVersion");
        $api->get('/coins', "{$info}@getCoinInfo");
        $api->get('/currencies', "{$info}@getCurrencyInfo");
        $api->get('/iso3166', "{$info}@getISO3166");
        $api->get('/wallet-status', "{$info}@getWalletStatus");
        $api->get('/configs', "{$info}@getConfig");
    });

    # /api/limitations/*
    $limitation = LimitationController::class;
    $api->group(['prefix' => 'limitations'], function ($api) use ($limitation) {
        $api->get('/', "{$limitation}@show");
    });

    # /api/addresses/*
    $address = AddressController::class;
    $api->group(['prefix' => 'addresses'], function ($api) use ($address) {
        $api->get('/', "{$address}@getAddresses");
        $api->get('/validate', "{$address}@validateAddress");
        $api->post('/', "{$address}@create");
        $api->delete('/{id}', "{$address}@delete");
        $api->delete('/', "{$address}@bulkDelete");
    });

    # /api/announcements/*
    $announcement = AnnouncementController::class;
    $api->group(['prefix' => 'announcements'], function ($api) use ($announcement) {
        $api->get('/', "{$announcement}@getAnnouncements");
        $api->get('/pinned', "{$announcement}@getPinnedAnnouncement");
        $api->get('/{id}', "{$announcement}@getAnnouncement");
    });

    # /api/nowhere
    $api->group(['prefix' => 'nowhere'], function ($api) use ($info) {
        $api->get('/', "{$info}@nowhere");
        $api->post('/', "{$info}@nowhere");
    });

    # /api/wfpay/*
    $wfpay = WfpayController::class;
    $api->group(['prefix' => 'wfpay'], function ($api) use ($wfpay) {
        $api->post('payment-callback/{id}', "{$wfpay}@paymentCallback");
        $api->post('transfer-callback/{id}', "{$wfpay}@transferCallback");
    });
});
