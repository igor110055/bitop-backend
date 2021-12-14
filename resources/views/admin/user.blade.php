@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">用戶管理：{{ $user->username }}</h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        @can('edit-users')
        @if (!$is_root)
        <a
            href="{{ route('admin.users.edit', ['user' => $user->id]) }}"
            class="btn btn-primary waves-effect"
        >編輯用戶</a>
        @endif
        @endcan

        <a
            href="{{ route('admin.users.orders', ['user' => $user->id]) }}"
            class="btn btn-primary waves-effect"
        >訂單管理</a>
        <a
            href="{{ route('admin.users.advertisements', ['user' => $user->id]) }}"
            class="btn btn-primary waves-effect"
        >廣告管理</a>
        <a
            href="{{ route('admin.users.limitations', ['user' => $user->id]) }}"
            class="btn btn-primary waves-effect"
        >限額設定</a>

        @can('edit-accounts')
        <a
            href="{{ route('admin.users.transfers.create', ['user' => $user->id]) }}"
            class="btn btn-primary waves-effect"
        >手動資產移轉</a>
        @endcan

        @can('edit-users')
        @if (!$is_root)
        <a
            href="{{ route('admin.users.feature-lock', ['user' => $user->id]) }}"
            class="btn btn-primary waves-effect"
        >鎖定特定功能</a>
        @else
            @role('super-admin')
            <a
                href="{{ route('admin.users.feature-lock', ['user' => $user->id]) }}"
                class="btn btn-primary waves-effect"
            >鎖定特定功能</a>
            @endrole
        @endif
        @endcan

        @can('edit-auth')
        @if (!$is_root and ($user->id !== auth()->user()->id))
            @if ($user->is_admin)
            <a
                href="{{ route('admin.users.admin.authorize', ['user' => $user->id]) }}"
                class="btn btn-outline-warning waves-effect"
            >取消管理員權限</a>
            @else
            <a
                href="{{ route('admin.users.admin.authorize', ['user' => $user->id]) }}"
                class="btn btn-outline-warning waves-effect"
            >提升為管理員</a>
            @endif
        @endif
        @endcan

        @role('super-admin')
        @if ($user->is_tester)
        <a
            href="{{ route('admin.users.admin.authorize-tester', ['user' => $user->id]) }}"
            class="btn btn-outline-info waves-effect"
        >取消測試權限</a>
        @else
        <a
            href="{{ route('admin.users.admin.authorize-tester', ['user' => $user->id]) }}"
            class="btn btn-outline-info waves-effect"
        >啟用測試權限</a>
        @endif
        @endrole

    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        @include('partials.user_profile')
        <div class="card">
            <div class="card-header"><h3 class="card-title">帳號狀態</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">
                        @if ($user_locks->isEmpty())
                            <span class="badge badge-pill badge-info">normal</span>
                        @else
                            @foreach ($user_locks as $lock)
                            <span class="badge badge-pill badge-danger">{{ $lock->type.'-lock' }}</span>
                            @endforeach
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card pb-4">
            <div class="card-header"><h3 class="card-title">實名認證</h3></div>
            <div class="card-block">
            @if($auth)
                <dl class="row">
                    <dt class="col-sm-3">送出時間(UTC)</dt>
                    <dd class="col-sm-9">{{ $auth->created_at }}</dd>
                    <dt class="col-sm-3">驗證狀態</dt>
                    <dd class="col-sm-9">
                        <span class="text-{{ $user->is_verified ? 'default' : 'danger' }}">
                            {{ __("messages.user.auth_status.{$auth->status}") }}
                        </span>
                    </dd>
                    <dt class="col-sm-3">姓</dt>
                    <dd class="col-sm-9">{{ $auth->last_name }}</dd>
                    <dt class="col-sm-3">名</dt>
                    <dd class="col-sm-9">{{ $auth->first_name }}</dd>
                    <dt class="col-sm-3">顯示名稱</dt>
                    <dd class="col-sm-9">{{ $auth->username }}</dd>
                    <dt class="col-sm-3">身分證號</dt>
                    <dd class="col-sm-9">{{ $auth->id_number }}</dd>
                    @if ($auth->verified_at)
                    <dt class="col-sm-3">處理時間(UTC)</dt>
                    <dd class="col-sm-9">{{ $auth->verified_at }}</dd>
                    @endif
                </dl>
                <div class="card-block__title">檔案</div>
                <div class="row">
                    @if ($files)
                        @foreach ($files as $file)
                        <div class="col-md-6 col-lg-4">
                            <img class="card-img-top" src="{{ $file->link }}" alt="uploaded file">
                        </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <p>未上傳任何檔案</p>
                        </div>
                    @endif
                </div>
                <div class="mt-3">

                    @can('verify-users')

                    @if (($auth->status === 'processing') or ($auth->status === 'rejected'))
                        @if ($is_username_available)
                            <button id="btn-approve" type="submit" class="btn btn-primary">通過驗證</button>
                        @else
                            <button class="btn btn-primary disabled">顯示名稱已被其他用戶註冊，無法通過驗證</button>
                        @endif
                    @endif
                    @if (($auth->status === 'processing') or ($auth->status === 'passed'))
                    <button class="btn btn-danger" data-toggle="modal" data-target="#modal-reject">拒絕</button>
                    <div class="modal fade" id="modal-reject" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title pull-left">選擇拒絕原因，將寄出通知信告知</h5>
                                </div>
                                <form class="authentication-form" method="post" action="{{ route('admin.users.verify', ['auth' => $auth->id]) }}">
                                    <div class="modal-body">
                                        {{ csrf_field() }}
                                        {{ method_field('PUT') }}
                                        <input type="hidden" name="action" value="reject">

                                        @foreach ($reject_reasons as $key => $reason)
                                        <label class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="reasons[]" value="{{ $key }}">
                                            <span class="custom-control-indicator"></span>
                                            <span class="custom-control-description">{{ $reason }}</span>
                                        </label>
                                        <div class="clearfix mb-2"></div>
                                        @endforeach
                                        <div class="mt-3">
                                            @include('widgets.forms.input', ['name' => 'other_reason', 'value' => '', 'title' => '其他', 'required' => true, 'placeholder' => '請以用戶看得懂得語言，輸入其他原因'])
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" type="button" class="btn btn-primary" value="">送出</button>
                                        <button type="button" class="btn btn-link" data-dismiss="modal">取消</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif

                    @endcan

                </div>
                @else
                <p>未驗證</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">虛擬幣帳戶</h3></div>
            <div class="card-block">
                <table id="accounts" class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>餘額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                        <tr>
                            <td><a href="{{ route('admin.accounts.show', ['account' => $account->id]) }}">{{ $account->coin }}</a></td>
                            <td>{{ formatted_coin_amount($account->balance, $account->coin) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card pb-4">
            <div class="card-header"><h3 class="card-title">銀行帳戶</h3></div>
             <div class="listview listview--hover">
                @foreach ($bank_accounts as $bank_account)
                <a href="{{ route('admin.bank-accounts.show', ['bank_account' => $bank_account->id]) }}" class="listview__item">
                    <div class="listview__content">
                        <dl class="row">
                            <dt class="col-sm-3">ID</dt>
                            <dd class="col-sm-9">{{ $bank_account->id }}</dd>
                            <dt class="col-sm-3">銀行</dt>
                            <dd class="col-sm-9">{{ $bank_account->bank->nationality }} {{ $bank_account->bank_name }}</dd>
                            <dt class="col-sm-3">省份/城市</dt>
                            <dd class="col-sm-9">{{ $bank_account->bank_province_name }} {{ $bank_account->bank_city_name }}</dd>
                            <dt class="col-sm-3">帳戶類型</dt>
                            <dd class="col-sm-9">{{ $bank_account->type }}</dd>
                            <dt class="col-sm-3">戶名</dt>
                            <dd class="col-sm-9">{{ $bank_account->name }}</dd>
                            <dt class="col-sm-3">帳號</dt>
                            <dd class="col-sm-9">{{ $bank_account->account }}</dd>
                            <dt class="col-sm-3">幣別</dt>
                            <dd class="col-sm-9">{{ implode(' ,', $bank_account->currency) }}</dd>
                            <dt class="col-sm-3">建立時間</dt>
                            <dd class="col-sm-9">{{ datetime($bank_account->created_at) }}</dd>
                            @if ($bank_account->is_verified)
                            <dt class="col-sm-3">審核時間</dt>
                            <dd class="col-sm-9">{{ datetime($bank_account->verified_at) }}</dd>
                            @endif
                            @if (!is_null($bank_account->deleted_at))
                            <dt class="col-sm-3">刪除時間</dt>
                            <dd class="col-sm-9">{{ datetime($bank_account->deleted_at) }}</dd>
                            @endif
                        </dl>
                    </div>
                    @if ($bank_account->deleted_at)
                        <span class="badge badge-pill badge-default">{{ __("messages.bank_account.status.deleted") }}</span>
                    @else
                        @if ($bank_account->is_verified)
                            <span class="badge badge-pill badge-success">{{ __("messages.bank_account.status.active") }}</span>
                        @else
                            <span class="badge badge-pill badge-danger">{{ __("messages.bank_account.status.pending") }}</span>
                        @endif
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        @can('edit-users')
        @if(!$is_root)
        <div class="card">
            <div class="card-header"><h3 class="card-title">鎖定使用者</h3></div>
            <div class="card-block">
                <form action="{{ route('admin.users.admin-lock', ['user' => $user]) }}" method="post">
                    {{ csrf_field() }}
                    {{ method_field('PUT') }}
                    @include('widgets.forms.input', ['name' => 'description', 'value' => '', 'title' => 'Description', 'required' => true])
                    @if ($admin_lock->isEmpty())
                    <button type="submit" name="action" value="lock" class="btn btn-primary">鎖定</button>
                    @else
                    <button type="submit" name="action" value="unlock" class="btn btn-primary">解除鎖定</button>
                    @endif
                </form>
            </div>
        </div>
        @endif
        @endcan

        @can('edit-users')
        @if ($user->two_factor_auth)
        <div class="card">
            <div class="card-header"><h3 class="card-title">強制關閉二次驗證</h3></div>
            <div class="card-block">
                <form action="{{ route('admin.users.deactivate-tfa', ['user' => $user]) }}" method="post">
                    {{ csrf_field() }}
                    @include('widgets.forms.input', ['name' => 'description', 'value' => '', 'title' => 'Description', 'required' => true])
                    <button type="submit" name="action" value="lock" class="btn btn-danger">強制關閉</button>
                </form>
            </div>
        </div>
        @endif
        @endcan

        @can('edit-auth')
        @if ($user->is_admin and !$is_root)
        <div class="card">
            <div class="card-header"><h3 class="card-title">管理者角色設定</h3></div>
            <div class="card-block">
                <form id="update-role" method="post" action="{{ route('admin.users.role.update', ['user' => $user]) }}">
                    {{ csrf_field() }}
                    {{ method_field('PUT') }}
                    @include('widgets.forms.select', ['name' => 'role', 'class' => 'search-control', 'title' => 'Current Role', 'value' => $role, 'values' => __("messages.user.role")])
                    <button class="btn btn-primary mt-4" id="update-role" name="submit" value="1">更動</button>
                </form>
            </div>
        </div>
        @endif
        @endcan
    </div>


@endsection

@push('scripts')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
$(function () {
    @if ($auth)
    $('#btn-approve').on("click", function(e) {
        e.preventDefault();
        var _token = "{{ csrf_token() }}";
        swal({
            title: '確認通過驗證?',
            text: '將會寄送通過驗證通知信給用戶',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '{{ route('admin.users.verify', ['auth' => $auth->id]) }}',
                    method: 'PUT',
                    data: { action: "approve", _token: _token },
                    dataType: 'json',
                }).done(function (data) {
                    handleNext(data.next);
                }).fail(function (err) {
                    $.notify({ message: 'Oops... there is something wrong!.' }, { type: 'danger' });
                });
            }
        }).catch(swal.noop);
    });

    $('.authentication-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route('admin.users.verify', ['auth' => $auth->id]) }}',
            method: 'PUT',
            data: $(this).serialize(),
            dataType: 'json',
        }).done(function (data) {
            handleNext(data.next);
        }).fail(function (err) {
            $.notify({ message: 'Oops... there is something wrong!.' }, { type: 'danger' });
        });
    });
    @endif
});

function handleNext(next_user)
{
    swal({
        title: '完成',
        type: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#32c787',
        confirmButtonText: '前往下一位待驗證用戶',
        cancelButtonText: '完成',
        showConfirmButton: (next_user !== null)
    }).then((result) => {
        if (result.value) {
            window.location = next_user;
        } else {
            window.location.reload();
        }
    });
}
</script>
@endpush
