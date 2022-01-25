@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">充值Id：{{ $deposit->id }}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">充值細節</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">充值數量</dt>
                    <dd class="col-sm-9">{{ $deposit->amount }} {{ $deposit->coin }}</dd>
                    <dt class="col-sm-3">時間</dt>
                    <dd class="col-sm-9">{{ datetime($deposit->created_at) }}</dd>
                    <dt class="col-sm-3">Transaction</dt>
                    <dd class="col-sm-9">{{ $deposit->transaction }}</dd>
                    <dt class="col-sm-3">Address</dt>
                    <dd class="col-sm-9">{{ $deposit->address }}</dd>
                    <dt class="col-sm-3">Tag</dt>
                    <dd class="col-sm-9">{{ $deposit->tag }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">用戶資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">ID</dt>
                    <dd class="col-sm-9">
                        <a href="{{ route('admin.users.show', ['user' => $user->id]) }}">{{ $user->id }}</a>
                    </dd>
                    <dt class="col-sm-3">UserName</dt>
                    <dd class="col-sm-9">{{ $user->username }}</dd>
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $user->email }}</dd>
                    <dt class="col-sm-3">所屬群組</dt>
                    <dd class="col-sm-9">
                        <a href="{{ route('admin.groups.show', ['group' => $user->group_id]) }}">{{ $user->group_id }}</a>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@endpush
