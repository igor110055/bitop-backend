@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" id='confirm-form' action="{{ route('admin.accounts.manipulations.store', ['account' => $account->id]) }}">
{{ csrf_field() }}
    <div class="card">
        <div class="card-header"><h2 class="card-title">帳戶手動操作：<a href="{{ route('admin.users.show', ['user' => $user->id]) }}">{{ $user->username }}</a> 的 <a href="{{ route('admin.accounts.show', ['account' => $account->id]) }}">{{ $account->coin }} 帳戶</a></h2></div>
        <div class="card-block">
            @include('widgets.forms.select', ['name' => 'type', 'class' => '', 'values' => $types, 'value' => '', 'title' => '操作類型*', 'required' => true])
            @include('widgets.forms.input', ['name' => 'amount', 'class' => '', 'value' => '', 'title' => '金額*', 'required' => true])
            @include('widgets.forms.input', ['name' => 'unit_price', 'class' => '', 'value' => '', 'title' => '單價(僅在「充值」時填寫)'])
			@include('widgets.forms.input', ['name' => 'note', 'value' => '', 'title' => '備註(後台顯示)'])
			@include('widgets.forms.input', ['name' => 'message', 'value' => '', 'title' => '訊息(前端使用者顯示)'])
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
$(function () {
    $('#confirm-form').on('submit', function (e) {
        e.preventDefault();
        var user = {!! json_encode($user) !!};
        var account = {!! json_encode($account) !!};
        var type = $('[name="type"]').val();
        var amount = $('[name="amount"]').val();
        var form = this;
        if (type == 'manual-deposit') {
            type = '存入';
        } else if (type == 'manual-withdrawal') {
            type = '扣款';
        }
        if (amount != 0) {
            swal({
                title: '確認執行該操作?',
                text: '對 '+ user.username + ' ' + type + ' ' + amount + ' ' + account.coin,
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.value) {
                    form.submit();
                }
            }).catch(swal.noop);
        }
    });

});
</script>
@endpush

