<aside class="sidebar">
    <div class="scrollbar-inner">
        <div class="user">
            @php
                $user = Auth::user();
            @endphp
            <a class="user__info" href="/admin/users/{{ $user->id }}">
                <div>
                    <div class="user__name">{{ $user->first_name }} {{ $user->last_name }}</div>
                    <div class="user__email">{{ $user->email }}</div>
                </div>
            </a>
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
                'title' => '訂單管理',
                'path' => 'orders',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '用戶管理',
                'path' => 'users',
                'icon' => 'accounts-list',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '廣告管理',
                'path' => 'advertisements',
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
                'title' => '全站交易明細',
                'path' => 'transactions',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '群組管理',
                'path' => 'groups',
                'icon' => 'accounts',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '商戶管理',
                'path' => 'merchants',
                'icon' => 'money',
            ],
            [
                'model' => function ($user) { return $user->can('edit-configs'); },
                'title' => '全站設定',
                'path' => 'configs',
                'icon' => 'settings',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '手續費設定',
                'path' => 'fee-settings',
                'icon' => 'money-box',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '限額設定',
                'path' => 'limitations',
                'icon' => 'money-off',
            ],
            [
                'model' => function ($user) { return $user->can('view-auth'); },
                'title' => '權限管理',
                'path' => 'permissions',
                'icon' => 'shield-check',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '公告管理',
                'path' => 'announcements',
                'icon' => 'notifications',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '報表',
                'path' => 'reports',
                'icon' => 'chart',
            ],
            [
                'model' => function ($user) { return $user->is_admin; },
                'title' => '發送交易資料管理',
                'path' => 'export_logs',
                'icon' => 'view-list-alt',
            ],
            [
                'model' => function ($user) { return $user->hasRole('super-admin'); },
                'title' => '匯率管理',
                'path' => 'exchange-rates',
                'icon' => 'money',
            ],
            [
                'model' => function ($user) { return $user->hasRole('super-admin'); },
                'title' => '組織管理',
                'path' => 'agencies',
                'icon' => 'globe',
            ],
            [
                'model' => function ($user) { return $user->hasRole('super-admin'); },
                'title' => 'WalletBalance Transactons',
                'path' => 'wallet_balances/transactions',
                'icon' => 'money-box',
            ],
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
