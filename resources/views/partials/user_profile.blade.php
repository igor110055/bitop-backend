<div class="card">
    <div class="card-header"><h3 class="card-title">基本資料</h3></div>
    <div class="card-block">
        <dl class="row">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9"><a href="/admin/users/{{ $user->id }}">{{ $user->id }}</a></dd>
            <dt class="col-sm-3">姓名</dt>
            <dd class="col-sm-9">{{ $user->name }}</dd>
            <dt class="col-sm-3">顯示名稱</dt>
            <dd class="col-sm-9">{{ $user->username }}</dd>
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9">{{ $user->email }}</dd>
            <dt class="col-sm-3">所屬群組</dt>
            <dd class="col-sm-9">
                @if ($user->group)
                    <a href="/admin/groups/{{ $user->group->id }}">{{ $user->group->name }}</a>
                @else
                    無
                @endif
            </dd>
            <dt class="col-sm-3">國籍</dt>
            <dd class="col-sm-9">{{ $user->nationality }}</dd>
            <dt class="col-sm-3">偏好語言</dt>
            <dd class="col-sm-9">{{ $user->locale ?: '未設定' }}</dd>
            <dt class="col-sm-3">實名驗證狀態</dt>
            <dd class="col-sm-9">
                <span class="text-{{ $user->is_verified ? 'default' : 'danger' }}">
                    {{ __("messages.user.auth_status.{$user->authentication_status}") }}
                </span>
            </dd>
            <dt class="col-sm-3">可登入管理後台</dt>
            <dd class="col-sm-9">
                {{ $user->is_admin ? 'Yes' : 'No' }}
            </dd>
            <dt class="col-sm-3">二步驟驗證狀態</dt>
            <dd class="col-sm-9">
                @if ($user->two_factor_auth)
                    <span class="badge badge-pill badge-info">activate</span>
                @else
                    <span class="badge badge-pill badge-default">non-activate</span>
                @endif
            </dd>
        </dl>
    </div>
</div>