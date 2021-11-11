<aside class="sidebar">
    <div class="scrollbar-inner">
        <div class="user">
            <div class="user__info" data-toggle="dropdown">
                <!--img class="user__img" src="demo/img/profile-pics/8.jpg" alt=""-->
                <div>
                    @php
                    $user = Auth::user();
                    @endphp
                    <div class="user__name">{{ $user->first_name }} {{ $user->last_name }}</div>
                    <div class="user__email">{{ $user->email }}</div>
                </div>
            </div>

            <div class="dropdown-menu">
                <a class="dropdown-item" href="">Profile</a>
                <a class="dropdown-item" href="">Settings</a>
            </div>
        </div>

        @php
        function active($prefix)
        {
            $route = request()->route();
            if ($route) {
                $name = $route->getName();
                if ($name) {
                    $prefix = "admin.{$prefix}";
                    if ($name === $prefix or
                        \Illuminate\Support\Str::startsWith($name, "{$prefix}.")) {
                        return 'navigation__active';
                    }
                }
            }
            return '';
        }

        function can($menu)
        {
            $user = Auth::user();
            $model = $menu['model'];
            if (is_callable($model)) {
                return $model($user);
            }
            return $user->can('index', $model);
        }

        $menus = [
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '管理首頁',
                'path' => 'index',
                'icon' => 'home',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '全站設定',
                'path' => 'configs',
                'icon' => 'settings',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '用戶管理',
                'path' => 'users',
                'icon' => 'accounts-list',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '權限管理',
                'path' => 'permissions',
                'icon' => 'shield-check',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '群組管理',
                'path' => 'groups',
                'icon' => 'accounts',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '手續費設定',
                'path' => 'fee-settings',
                'icon' => 'money-box',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '匯率管理',
                'path' => 'exchange-rates',
                'icon' => 'money',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '組織管理',
                'path' => 'agencies',
                'icon' => 'globe',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '廣告管理',
                'path' => 'advertisements',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '訂單管理',
                'path' => 'orders',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '銀行帳戶管理',
                'path' => 'bank-accounts',
                'icon' => 'assignment-account',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '報表',
                'path' => 'reports',
                'icon' => 'chart',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '限額設定',
                'path' => 'limitations',
                'icon' => 'money-off',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '交易明細',
                'path' => 'transactions',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '提現管理',
                'path' => 'withdrawals',
                'icon' => 'money-box',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '充值管理',
                'path' => 'deposits',
                'icon' => 'money-box',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '公告管理',
                'path' => 'announcements',
                'icon' => 'notifications',
            ],
            /*[
                'model' => \App\Http\Controllers\Admin\ExportExchanges::class,
                'title' => '轉出管理',
                'path' => 'export-exchanges',
                'icon' => 'arrow-right-top',
            ],
            [
                'model' => \App\Http\Controllers\Admin\ImportExchanges::class,
                'title' => '充值管理',
                'path' => 'import-exchanges',
                'icon' => 'arrow-left-bottom',
            ],
            [
                'model' => \App\Models\Account::class,
                'title' => '交易明細',
                'path' => 'transactions',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => \App\Http\Controllers\Admin\ImportExchanges::class,
                'title' => '手動明細',
                'path' => 'system-exchanges',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => \App\Models\Channel::class,
                'title' => '通道管理',
                'path' => 'channels',
                'icon' => 'square-down ',
            ],
            [
                'model' => \App\Models\User::class,
                'title' => '用戶管理',
                'path' => 'users',
                'icon' => 'accounts-list',
            ],
            [
                'model' => \App\Models\Group::class,
                'title' => '群組管理',
                'path' => 'groups',
                'icon' => 'accounts',
            ],
            [
                'model' => \App\Models\ExchangeRateAdjustment::class,
                'title' => '匯率設定',
                'path' => 'exchange-rates',
                'icon' => 'money ',
            ],
            [
                'model' => \App\Models\FeeSetting::class,
                'title' => '手續費設定',
                'path' => 'fee-settings',
                'icon' => 'money-box ',
            ],
            [
                'model' => \App\Models\Limit::class,
                'title' => '限額設定',
                'path' => 'limits',
                'icon' => 'money-off ',
            ],
            [
                'model' => \App\Models\Stock::class,
                'title' => '股票管理',
                'path' => 'stocks',
                'icon' => 'trending-up ',
            ],
            [
                'model' => \App\Models\Merchant::class,
                'title' => '商戶管理',
                'path' => '',
                'icon' => 'store',
                'submenus' => [
                    [ 'path' => 'merchants', 'title' => '商戶列表' ],
                    [ 'path' => 'orders', 'title' => '訂單管理' ],
                ],
            ],
            [
                'model' => \App\Models\Bank::class,
                'title' => '銀行管理',
                'path' => 'banks',
                'icon' => 'balance',
            ],
            [
                'model' => \App\Models\AdminBankAccount::class,
                'title' => '銀行帳戶管理',
                'path' => 'admin-bank-accounts',
                'icon' => 'book',
            ],
            [
                'model' => \App\Models\User::class,
                'title' => '富邦虛擬帳號',
                'path' => 'fubon',
                'icon' => 'account-o',
            ],
            [
                'model' => \App\Models\Notification::class,
                'title' => '公告管理',
                'path' => 'notifications',
                'icon' => 'notifications',
            ],
*/
        ];
        @endphp
        <ul class="navigation">
        @foreach($menus as $menu)
            @if(can($menu))
            <li class="{{ isset($menu['submenus']) ? 'navigation__sub' : '' }} {{ active($menu['path']) }}">
                <a href="/admin/{{ $menu['path'] }}"><i
                    class="zmdi zmdi-{{ $menu['icon'] }} zmdi-hc-fw"></i>
                    {{ $menu['title'] }}</a>
                @if(isset($menu['submenus']))
                <ul>
                    @foreach($menu['submenus'] as $submenu)
                    <li><a href="/admin/{{ $submenu['path'] }}">{{ $submenu['title'] }}</a></li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endif
        @endforeach
        </ul>
    </div>
</aside>
