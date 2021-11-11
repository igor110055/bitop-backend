@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            銀行帳戶管理：{{ $bank_account->id }}
            @if ($bank_account->deleted_at)
                <span class="badge badge-pill badge-default">{{ __("messages.bank_account.status.deleted") }}</span>
            @else
                @if ($bank_account->is_verified)
                    <span class="badge badge-pill badge-success">{{ __("messages.bank_account.status.active") }}</span>
                @else
                    <span class="badge badge-pill badge-danger">{{ __("messages.bank_account.status.pending") }}</span>
                @endif
            @endif
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>
    <div class="card-block">
        @if (!$bank_account->deleted_at)
            @if ($bank_account->is_verified)
                <button class="btn btn-danger" data-toggle="modal" data-target="#modal-reject">刪除</button>
            @else
                <button id="btn-approve" type="submit" class="btn btn-primary">審核通過</button>
                <button class="btn btn-danger" data-toggle="modal" data-target="#modal-reject">審核不通過</button>
            @endif
            <div class="modal fade" id="modal-reject" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title pull-left">此銀行帳號將被刪除。選擇原因，將寄出通知信告知用戶</h5>
                        </div>
                        <form class="authentication-form" method="post" action="{{ route('admin.bank-accounts.verify', ['bank_account' => $bank_account->id]) }}">
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
                                    @include('widgets.forms.input', ['name' => 'other_reason', 'value' => '', 'title' => '其他', 'placeholder' => '請以用戶看得懂得語言，輸入其他原因'])
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
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        @include('partials.user_profile')
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">銀行帳號資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">ID</dt>
                    <dd class="col-sm-9">{{ $bank_account->id }}</dd>
                    <dt class="col-sm-3">銀行</dt>
                    <dd class="col-sm-9">{{ $bank->nationality }} {{ $bank->name }}</dd>
                    <dt class="col-sm-3">分行</dt>
                    <dd class="col-sm-9">{{ $bank_account->bank_branch_name }} {{ ($bank_account->bank_branch_phonetic_name ? "($bank_account->bank_branch_phonetic_name)" : '')}}</dd>
                    <dt class="col-sm-3">戶名</dt>
                    <dd class="col-sm-9">{{ $bank_account->name }}</dd>
                    <dt class="col-sm-3">英文戶名</dt>
                    <dd class="col-sm-9">{{ $bank_account->phonetic_name }}</dd>
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
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title">審核紀錄</h3></div>
            <div class="card-block">
                <div class="table-responsive">
                    <table id="applications" class="table table-striped">
                        <thead class="thead-default">
                            <tr>
                                <th>時間</th>
                                <th>行為</th>
                                <th>原因</th>
                                <th>操作人</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($admin_actions as $action)
                            <tr>
                                <td>{{ datetime($action->created_at) }}</td>
                                <td>{{ __("messages.admin_action.actions.{$action->type}") }}</td>
                                <td>{{ $action->description }}</td>
                                <td><a href="/admin/users/{{ $action->admin_id }}">{{ $action->admin->name }}({{ $action->admin->id }})</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
$(function () {
    $('#btn-approve').on("click", function(e) {
        console.log('1');
        e.preventDefault();
        var _token = "{{ csrf_token() }}";
        swal({
            title: '確認通過驗證?',
            text: '將會寄送通過審核通知信給用戶',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '{{ route('admin.bank-accounts.verify', ['bank_account' => $bank_account->id]) }}',
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
            url: '{{ route('admin.bank-accounts.verify', ['bank_account' => $bank_account->id]) }}',
            method: 'PUT',
            data: $(this).serialize(),
            dataType: 'json',
        }).done(function (data) {
            handleNext(data.next);
        }).fail(function (err) {
            $.notify({ message: 'Oops... there is something wrong!.' }, { type: 'danger' });
        });
    });
});

function handleNext(next_user)
{
    swal({
        title: '完成',
        type: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#32c787',
        confirmButtonText: '前往下一個位待審核銀行帳號',
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
