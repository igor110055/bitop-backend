@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" id='confirm-form' action="{{ route('admin.users.transfers.store', ['user' => $src_user->id]) }}">
    {{ csrf_field() }}
    {{ method_field('POST') }}
    <div class="card">
        <div class="card-header"><h2 class="card-title">手動資產移轉，轉出用戶：<a href="{{ route('admin.users.show', ['user' => $src_user->id]) }}">{{ $src_user->username }}</a></h2></div>
        <div class="card-block">
            @include('widgets.forms.select', ['name' => 'dst_user_id', 'class' => 'user-search-select', 'values' => [], 'title' => '轉入用戶', 'required' => true])
            @include('widgets.forms.select', ['name' => 'coin', 'class' => '', 'values' => $coins, 'value' => '', 'title' => '轉帳幣別*', 'required' => true])
            @include('widgets.forms.input', ['name' => 'amount', 'class' => '', 'value' => '', 'title' => '數量*', 'required' => true])
			@include('widgets.forms.input', ['name' => 'note', 'value' => '', 'title' => '操作描述紀錄*', 'required' => true])
			@include('widgets.forms.input', ['name' => 'src_message', 'value' => '', 'title' => '轉出用戶的交易明細顯示訊息'])
            @include('widgets.forms.input', ['name' => 'dst_message', 'value' => '', 'title' => '轉入用戶的交易明細顯示訊息'])
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    @include('widgets.forms.user_select')
});
</script>
@endpush
