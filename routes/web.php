<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('admin/login', 'Admin\\AuthController@login')->name('login');
Route::post('admin/auth/login', 'Admin\\AuthController@login');

Route::group(
    [
        'namespace' => 'Admin',
        'prefix' => 'admin',
        'middleware' => ['assign.guard:web', 'auth.admin']
    ],
    function () {
        Route::redirect('/', '/admin/index');
        Route::get('index', 'IndexController@index')->name('admin.index');
        Route::get('auth/logout', 'AuthController@logout')->name('admin.logout');

        Route::get('bank-accounts/search/', 'BankAccountController@search');
        Route::put('bank-accounts/{bank_account}/verify', 'BankAccountController@verify')->name('admin.bank-accounts.verify');
        Route::resource('bank-accounts', 'BankAccountController', ['only' => ['index', 'show'], 'as' => 'admin']);

        Route::get('users/search/', 'UserController@search');
        Route::get('users/select-search', 'UserController@selectSearch');
        Route::put('users/{auth}/verify', 'UserController@verify')->name('admin.users.verify');
        Route::get('users/{user}/orders', 'UserController@orderList')->name('admin.users.orders');
        Route::get('users/{user}/orders/search', 'UserController@getOrders')->name('admin.users.orders.search');
        Route::get('users/{user}/advertisements', 'UserController@advertisementList')->name('admin.users.advertisements');
        Route::get('users/{user}/advertisements/search', 'UserController@getAdvertisements')->name('admin.users.advertisements.search');
        Route::get('users/{user}/limitations', 'UserController@getLimitations')->name('admin.users.limitations');
        Route::get('users/{user}/limitations/edit/{type}/{coin}', 'UserController@editLimitations')->name('admin.users.limitations.edit');
        Route::post('users/{user}/limitations/store', 'UserController@storeLimitation')->name('admin.users.limitations.store');
        Route::put('users/{user}/admin-lock', 'UserController@adminLock')->name('admin.users.admin-lock');
        Route::get('users/{user}/feature-lock', 'UserController@createFeatureLock')->name('admin.users.feature-lock');
        Route::post('users/{user}/feature-lock', 'UserController@storeFeatureLock')->name('admin.users.feature-lock.store');
        Route::get('users/{user}/authorize-admin', 'UserController@authorizeAdmin')->name('admin.users.admin.authorize');
        Route::get('users/{user}/authorize-tester', 'UserController@authorizeTester')->name('admin.users.admin.authorize-tester');
        Route::put('users/{user}/role', 'UserController@updateRole')->name('admin.users.role.update');
        Route::post('users/{user}/deactivate-tfa', 'UserController@deactivateTFA')->name('admin.users.deactivate-tfa');
        Route::get('users/{user}/transfers', 'UserController@createTransfer')->name('admin.users.transfers.create');
        Route::post('users/{user}/transfers', 'UserController@storeTransfer')->name('admin.users.transfers.store');
        Route::put('users/{user}', 'UserController@update')->name('admin.users.update');
        Route::resource('users', 'UserController', ['only' => ['index', 'show', 'edit'], 'as' => 'admin']);

        Route::get('accounts/transactions/search/', 'AccountController@search');
        Route::get('accounts/{account}', "AccountController@show")->name('admin.accounts.show');
        Route::get('accounts/{account}/manipulation', 'AccountController@createManipulation')->name('admin.accounts.manipulations.create');
        Route::post('accounts/{account}/manipulation', 'AccountController@storeManipulation')->name('admin.accounts.manipulations.store');

        Route::get('groups/{group}/users', 'GroupController@getUsers')->name('admin.groups.users');
        Route::get('groups/{group}/share-settings', 'GroupController@getShareSettings')->name('admin.groups.share-settings');
        Route::get('groups/{group}/share-settings/create', 'GroupController@createShareSetting')->name('admin.groups.share-settings.create');
        Route::post('groups/{group}/share-settings', 'GroupController@storeShareSetting')->name('admin.groups.share-settings.store');
        Route::delete('groups/{group}/share-settings', 'GroupController@destoryShareSetting')->name('admin.groups.share-settings.destroy');
        Route::get('groups/{group}/fee-settings', 'GroupController@getFeeSettings')->name('admin.groups.fee-settings');
        Route::get('groups/{group}/fee-settings/edit/{type}/{coin}', 'GroupController@editFeeSettings')->name('admin.groups.fee-settings.edit');
        Route::get('groups/{group}/limitations', 'GroupController@getLimitations')->name('admin.groups.limitations');
        Route::get('groups/{group}/limitations/edit/{type}/{coin}', 'GroupController@editLimitations')->name('admin.groups.limitations.edit');
        Route::post('groups/{group}/limitations/store', 'GroupController@storeLimitation')->name('admin.groups.limitations.store');
        Route::put('groups/{group}', 'GroupController@update')->name('admin.groups.update');
        Route::post('groups', 'GroupController@store')->name('admin.groups.store');
        Route::get('groups/applications/', 'GroupController@getApplications')->name('admin.groups.applications');
        Route::get('groups/applications/{application}', 'GroupController@getApplication')->name('admin.groups.application');
        Route::post('groups/applications/{application}/verify', 'GroupController@verifyApplication')->name('admin.groups.application-verify');
        Route::resource('groups', 'GroupController', ['only' => ['index', 'show', 'create'], 'as' => 'admin']);

        Route::get('fee-settings/data', 'FeeSettingController@data');
        Route::get('fee-settings/edit/{type}/{coin}', 'FeeSettingController@edit')->name('admin.fee-settings.edit');
        Route::post('fee-settings', 'FeeSettingController@store')->name('admin.fee-settings.store');
        Route::post('fee-settings/fixed', 'FeeSettingController@storeFixed')->name('admin.fee-settings-fixed.store');
        Route::resource('fee-settings', 'FeeSettingController', ['only' => ['index'], 'as' => 'admin']);

        Route::get('agencies/{agency}/agents', 'AgencyController@getAgents')->name('admin.agencies.agents');
        Route::get('agencies/{agency}/agents/create', 'AgencyController@createAgent')->name('admin.agencies.agents.create');
        Route::post('agencies/{agency}/agents', 'AgencyController@storeAgent')->name('admin.agencies.agents.store');
        Route::post('agencies/{agency}/agents/delete', 'AgencyController@deleteAgent')->name('admin.agencies.agents.delete');
        Route::put('agencies/{agency}', 'AgencyController@update')->name('admin.agencies.update');
        Route::post('agencies', 'AgencyController@store')->name('admin.agencies.store');
        Route::resource('agencies', 'AgencyController', ['only' => ['index', 'create', 'show', 'edit'], 'as' => 'admin']);

        Route::get('assets/transactions', 'AssetController@getTransactions')->name('admin.assets.transactions');
        Route::get('assets/{asset}/manipulation', 'AssetController@createManipulation')->name('admin.assets.manipulations.create');
        Route::post('assets/{asset}/manipulation', 'AssetController@storeManipulation')->name('admin.assets.manipulations.store');
        Route::resource('assets', 'AssetController', ['only' => ['show'], 'as' => 'admin']);

        Route::get('exchange-rates', 'ExchangeRateController@index');
        Route::get('exchange-rates/get/{group?}', 'ExchangeRateController@get');
        Route::post('exchange-rates/create', 'ExchangeRateController@create');

        Route::get('orders/list', 'OrderController@getOrders')->name('admin.orders.list');
        Route::put('orders/{order}', 'OrderController@update')->name('admin.orders.update');
        Route::resource('orders', 'OrderController', ['only' => ['index', 'show'], 'as' => 'admin']);

        Route::get('limitations/edit/{type}/{coin}', 'LimitationController@edit')->name('admin.limitations.edit');
        Route::resource('limitations', 'LimitationController', ['only' => ['index', 'store'], 'as' => 'admin']);

        Route::get('reports/exchange-rates', 'ReportController@exchangeRates')->name('admin.report.exchange-rates');
        Route::get('reports/coin-prices', 'ReportController@coinPrices')->name('admin.report.coin-prices');
        Route::get('reports/accounts', 'ReportController@accounts')->name('admin.report.accounts');
        Route::get('reports/wallet_balances', 'ReportController@walletBalances')->name('admin.report.wallet-balances');
        Route::get('reports/orders', 'ReportController@orders')->name('admin.report.orders');
        Route::get('reports/fees', 'ReportController@fees')->name('admin.report.fees');
        Route::get('reports/fee-shares', 'ReportController@feeShares')->name('admin.report.fee-shares');
        Route::get('reports/withdrawal-deposit', 'ReportController@withdrawalsDeposits')->name('admin.report.withdrawals-deposits');
        Route::get('reports/ads', 'ReportController@ads')->name('admin.report.ads');
        Route::get('reports/transfers', 'ReportController@transfers')->name('admin.report.transfers');
        Route::get('reports/assets', 'ReportController@assets')->name('admin.report.assets');
        Route::get('reports/{date}', 'ReportController@daily')->name('admin.report.daily');
        Route::get('reports', 'ReportController@index')->name('admin.report.index');

        Route::get('transactions/search', 'TransactionController@search')->name('admin.transactions.search');
        Route::resource('transactions', 'TransactionController', ['only' => ['index'], 'as' => 'admin']);

        Route::get('withdrawals/search', 'WithdrawalController@search')->name('admin.withdrawals.search');
        Route::resource('withdrawals', 'WithdrawalController', ['only' => ['index', 'show'], 'as' => 'admin']);

        Route::get('deposits/search', 'DepositController@search')->name('admin.deposits.search');
        Route::resource('deposits', 'DepositController', ['only' => ['index', 'show'], 'as' => 'admin']);

        Route::put('announcements/{announcement}/cancel', 'AnnouncementController@cancel');
        Route::post('announcements/{announcement}/email-broadcast', 'AnnouncementController@emailBroadcast')->name('admin.announcements.email-broadcast');
        Route::put('announcements/{announcement}', 'AnnouncementController@update')->name('admin.announcements.update');
        Route::post('announcements', 'AnnouncementController@store')->name('admin.announcements.store');
        Route::resource('announcements', 'AnnouncementController', ['only' => ['index', 'show'], 'as' => 'admin']);

        Route::get('advertisements/list', 'AdvertisementController@getAdvertisements')->name('admin.advertisements.list');
        Route::put('advertisements/{advertisement}', 'AdvertisementController@update')->name('admin.advertisements.update');
        Route::resource('advertisements', 'AdvertisementController', ['only' => ['index', 'show'], 'as' => 'admin']);

        Route::resource('permissions', 'PermissionController', ['only' => ['index'], 'as' => 'admin']);

        Route::post('configs/wallet-activation', 'ConfigController@storeWalletActivation')->name('admin.configs.wallet-activation');
        Route::post('configs/withdrawal-fee-factor', 'ConfigController@storeWithdrawalFeeFactor')->name('admin.configs.withdrawal-fee-factor');
        Route::post('configs/withdrawal-limit', 'ConfigController@storeWithdrawalLimit')->name('admin.configs.withdrawal-limit');
        Route::post('configs/wfpay-activation', 'ConfigController@storeWfpayActivation')->name('admin.configs.wfpay-activation');
        Route::post('configs/app-version', 'ConfigController@storeAppVersionSetting')->name('admin.configs.app-version');
        Route::post('configs/express-auto-release', 'ConfigController@storeExpressAutoReleaseLimit')->name('admin.configs.express-auto-release');
        Route::resource('configs', 'ConfigController', ['only' => ['index'], 'as' => 'admin']);

        Route::get('wallet_balances/transactions', 'WalletBalanceController@getTransactions')->name('admin.wallet-balances.transactions');
        Route::get('wallet_balances/transactions/search', 'WalletBalanceController@transactionSearch')->name('admin.wallet-balances.transactions.search');

        Route::resource('wfpays', 'WfpayController', ['only' => ['index', 'store'], 'as' => 'admin']);
    }
);
