@php
    use App\Models\Order;
@endphp
@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">訂單號：{{ $order->id}}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">訂單資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">
                        <span class="text-{{ $order->completed_at ? 'default' : 'danger' }}">
                            {{ $order->status }}
                        </span>
                    </dd>
                    @if ($cancel_info)
                    <dt class="col-sm-3">取消時間</dt>
                    <dd class="col-sm-9">{{ $cancel_info['canceled_at'] }}</dd>
                    <dt class="col-sm-3">取消操作者</dt>
                    <dd class="col-sm-9">{{ $cancel_info['action'] }}</dd>
                        @if ($cancel_info['action'] === 'Admin')
                        <dt class="col-sm-3">Admin</dt>
                        <dd class="col-sm-9">{{ $cancel_info['admin'] }}</dd>
                        <dt class="col-sm-3">取消原因</dt>
                        <dd class="col-sm-9">{{ $cancel_info['description'] }}</dd>
                        @endif
                    @endif
                    <dt class="col-sm-3">幣別</dt>
                    <dd class="col-sm-9">{{ $order->coin }}</dd>
                    <dt class="col-sm-3">數量</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($order->amount) }}</dd>
                    <dt class="col-sm-3">單價</dt>
                    <dd class="col-sm-9">{{ $order->unit_price }}</dd>
                    <dt class="col-sm-3">價格</dt>
                    <dd class="col-sm-9">{{ $order->total }}</dd>
                    <dt class="col-sm-3">手續費</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($order->fee) }}</dd>
                    <dt class="col-sm-3">利潤</dt>
                    <dd class="col-sm-9">{{ $order->profit }}</dd>
                </dl>
            </div>
        </div>
        <div class="card pb-4">
            <div class="card-header"><h3 class="card-title">收款銀行帳戶</h3></div>
             <div class="listview listview--hover">
                @foreach ($bank_accounts as $bank_account)
                <a href="" class="listview__item">
                    <div class="listview__content">
                        <div class="listview__heading">{{ $bank_account->bank_name }}</div>
                        <p>{{ $bank_account->account }}</p>
                        <p>{{ $bank_account->name }}</p>
                    </div>
                    @if (!is_null($payment) and $bank_account->id === $order->payment->id)
                    <span class="badge badge-pill badge-warning">CLAIMED</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">賣家資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">ID</dt>
                    <dd class="col-sm-9">
                        <a href="{{ route('admin.users.show', ['user' => $src_user->id]) }}">{{ $src_user->id }}</a>
                    </dd>
                    <dt class="col-sm-3">UserName</dt>
                    <dd class="col-sm-9">{{ $src_user->username }}</dd>
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $src_user->email }}</dd>
                    <dt class="col-sm-3">Mobile</dt>
                    <dd class="col-sm-9">{{ $src_user->mobile }}</dd>
                    <dt class="col-sm-3">所屬群組</dt>
                    <dd class="col-sm-9">
                        <a href="{{ route('admin.groups.show', ['group' => $src_user->group_id]) }}">{{ $src_user->group_id }}</a>
                    </dd>
                    <dt class="col-sm-3">所屬組織</dt>
                    <dd class="col-sm-9">
                        @if ($src_user->agency_id)
                        <a href="{{ route('admin.agencies.show', ['agency' => $src_user->agency_id]) }}">{{ $src_user->agency_id }}</a>
                        @else
                        none
                        @endif
                    </dd>
                </dl>
            </div>
            <div class="card-header"><h3 class="card-title">買家資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">ID</dt>
                    <dd class="col-sm-9">
                        <a href="{{ route('admin.users.show', ['user' => $dst_user->id]) }}">{{ $dst_user->id }}</a>
                    </dd>
                    <dt class="col-sm-3">UserName</dt>
                    <dd class="col-sm-9">{{ $dst_user->username }}</dd>
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $dst_user->email }}</dd>
                    <dt class="col-sm-3">Mobile</dt>
                    <dd class="col-sm-9">{{ $dst_user->mobile }}</dd>
                    <dt class="col-sm-3">所屬群組</dt>
                    <dd class="col-sm-9">
                        <a href="{{ route('admin.groups.show', ['group' => $dst_user->group_id]) }}">{{ $dst_user->group_id }}</a>
                    </dd>
                    <dt class="col-sm-3">所屬組織</dt>
                    <dd class="col-sm-9">
                        @if ($dst_user->agency_id)
                        <a href="{{ route('admin.agencies.show', ['agency' => $dst_user->agency_id]) }}">{{ $dst_user->agency_id }}</a>
                        @else
                        none
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    @if ($order->status === Order::STATUS_CLAIMED)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">管理者操作</h3></div>
                <div class="card-block">
                <form action="{{ route('admin.orders.update', ['order' => $order]) }}" method="post">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                    @include('widgets.forms.input', ['name' => 'description', 'value' => '', 'title' => 'Description', 'required' => true])
                    <button type="submit" name="action" value="cancel-order" class="btn btn-primary">強制取消</button>
                    <button type="submit" name="action" value="complete-order" class="btn btn-primary">強制完成</button>
                </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
