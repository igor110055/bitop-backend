@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">提現Id：{{ $withdrawal->id }}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">提現細節</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">提現數量</dt>
                    <dd class="col-sm-9">{{ $withdrawal->amount }} {{ $withdrawal->coin }}</dd>
                    <dt class="col-sm-3">手續費</dt>
                    <dd class="col-sm-9">{{ $withdrawal->fee }} {{ $withdrawal->coin }}</dd>
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">{{ $withdrawal->status }}</dd>
                    <dt class="col-sm-3">發起時間</dt>
                    <dd class="col-sm-9">{{ datetime($withdrawal->created_at) }}</dd>
                    <dt class="col-sm-3">完成時間</dt>
                    <dd class="col-sm-9">{{ datetime($withdrawal->notified_at) }}</dd>
                    <dt class="col-sm-3">Address</dt>
                    <dd class="col-sm-9">{{ $withdrawal->address }}</dd>
                    <dt class="col-sm-3">Transaction</dt>
                    <dd class="col-sm-9">{{ $withdrawal->transaction }}</dd>
                    <dt class="col-sm-3">Tag</dt>
                    <dd class="col-sm-9">{{ $withdrawal->tag }}</dd>
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
