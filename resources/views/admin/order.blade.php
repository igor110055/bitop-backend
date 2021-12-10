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
                    <dt class="col-sm-3">快捷訂單</dt>
                    <dd class="col-sm-9">
                        {{ ($order->is_express? 'Yes' : 'No') }}
                    </dd>
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">
                        <span class="text-{{ $order->completed_at ? 'default' : 'danger' }}">
                            {{ __("messages.order.status.{$order->status}") }}
                        </span>
                    </dd>
                    @if ($cancel_info)
                    <dt class="col-sm-3">取消時間</dt>
                    <dd class="col-sm-9">{{ datetime($cancel_info['canceled_at']) }}</dd>
                    <dt class="col-sm-3">取消操作者</dt>
                    <dd class="col-sm-9">{{ $cancel_info['action'] }}</dd>
                        @if ($cancel_info['action'] === 'Admin')
                        <dt class="col-sm-3">Admin</dt>
                        <dd class="col-sm-9">{{ $cancel_info['admin'] }}</dd>
                        <dt class="col-sm-3">取消原因</dt>
                        <dd class="col-sm-9">{{ $cancel_info['description'] }}</dd>
                        @endif
                    @endif
                    <dt class="col-sm-3">商品</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($order->amount) }} {{ $order->coin }}</dd>
                    <dt class="col-sm-3">總價</dt>
                    <dd class="col-sm-9">{{ $order->total }} {{ $order->currency }}</dd>
                    <dt class="col-sm-3">單價</dt>
                    <dd class="col-sm-9">{{ $order->unit_price }}</dd>
                    <dt class="col-sm-3">手續費</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($order->fee) }} {{ $order->coin }}</dd>
                    <dt class="col-sm-3">廣告</dt>
                    <dd class="col-sm-9"><a href="{{ route('admin.advertisements.show', ['advertisement' => $ad->id]) }}">{{ ($ad->id) }}</a></dd>
                    <dt class="col-sm-3">廣告主</dt>
                    <dd class="col-sm-9"><a href="{{ route('admin.users.show', ['user' => $ad_owner->id]) }}">{{ $ad_owner->id }} ({{ $ad_owner->username }})</a></dd>
                </dl>
            </div>
        </div>
        @if ($action === 'express-buy')
        <div class="card">
            <div class="card-header"><h3 class="card-title">買家三方支付資訊</h3></div>
            @foreach ($wfpayments as $wfpayment)
            <hr>
            <div class="card-block pb-1">
                <dl class="row">
                    <dt class="col-sm-3">訂單號</dt>
                    <dd class="col-sm-9">{{ $wfpayment->id }}</dd>
                    <dt class="col-sm-3">生成時間</dt>
                    <dd class="col-sm-9">{{ datetime($wfpayment->created_at) }}</dd>
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">{{ $wfpayment->status }}</dd>
                    <dt class="col-sm-3">支付方式</dt>
                    <dd class="col-sm-9">{{ $wfpayment->payment_method }}</dd>
                    <dt class="col-sm-3">應付金額</dt>
                    <dd class="col-sm-9">{{ $wfpayment->guest_payment_amount ?: $wfpayment->total  }}</dd>
                    @if (!is_null($wfpayment->payment_info))
                    <div class="col-sm-3">付款資訊</div>
                    <div class="col-sm-9">
                        <div class="row">
                            @foreach($wfpayment->payment_info as $a => $v)
                            <dt class="col-sm-3">{{ $a }}</dt>
                            <dd class="col-sm-9">{{ $v }}</dd>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </dl>
            </div>
            @endforeach
        </div>
        @endif
        @if ($action === 'express-sell')
        <div class="card">
            <div class="card-header"><h3 class="card-title">三方支付下發資訊</h3></div>
            @foreach ($wftransfers as $wftransfer)
            <hr>
            <div class="card-block pb-1">
                <dl class="row">
                    <dt class="col-sm-3">訂單號</dt>
                    <dd class="col-sm-9">{{ $wftransfer->id }}</dd>
                    <dt class="col-sm-3">生成時間</dt>
                    <dd class="col-sm-9">{{ datetime($wftransfer->created_at) }}</dd>
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">{{ $wftransfer->status }}</dd>
                    @if (!$wftransfer->admin_actions->isEmpty())
                    @php
                        $admin_action = $wftransfer->admin_actions->first();
                    @endphp
                    <dt class="col-sm-3">操作管理員</dt>
                    <dd class="col-sm-9"><a href="/admin/users/{{ $admin_action->admin_id }}">{{ $admin_action->admin->name }}({{ $admin_action->admin->id }})</a></dd>
                    <dt class="col-sm-3">操作記錄說明</dt>
                    <dd class="col-sm-9">{{ $admin_action->description }}</dd>
                    @endif
                </dl>
            </div>
            @endforeach
        </div>
        @endif
        <div class="card pb-4">
            <div class="card-header"><h3 class="card-title">賣家收款銀行帳戶</h3></div>
             <div class="listview listview--hover">
                @foreach ($bank_accounts as $bank_account)
                <a href="{{ route('admin.bank-accounts.show', ['bank_account' => $bank_account]) }}" class="listview__item">
                    <div class="listview__content">
                        <div class="listview__heading">{{ $bank_account->bank_name }}</div>
                        <p>{{ $bank_account->account }}</p>
                        <p>{{ $bank_account->name }}</p>
                    </div>
                    @if (!is_null($payment_dst) and $bank_account->is($payment_dst))
                    <span class="badge badge-pill badge-warning">指定付款帳號</span>
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
                    <!-- <dt class="col-sm-3">所屬組織</dt>
                    <dd class="col-sm-9">
                        @if ($src_user->agency_id)
                        <a href="{{ route('admin.agencies.show', ['agency' => $src_user->agency_id]) }}">{{ $src_user->agency_id }}</a>
                        @else
                        none
                        @endif
                    </dd> -->
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
                    <!-- <dt class="col-sm-3">所屬組織</dt>
                    <dd class="col-sm-9">
                        @if ($dst_user->agency_id)
                        <a href="{{ route('admin.agencies.show', ['agency' => $dst_user->agency_id]) }}">{{ $dst_user->agency_id }}</a>
                        @else
                        none
                        @endif
                    </dd> -->
                </dl>
            </div>
        </div>
    </div>
    @can('edit-orders')
    @if ($order->status === Order::STATUS_PROCESSING or $order->status === Order::STATUS_CLAIMED)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">管理者操作</h3></div>
                <div class="card-block">
                    <form action="{{ route('admin.orders.update', ['order' => $order]) }}" method="post">
                        {{ csrf_field() }}
                        {{ method_field('PUT') }}
                        @include('widgets.forms.input', ['name' => 'description', 'value' => '', 'title' => '操作記錄說明', 'required' => true])
                        <button type="submit" name="action" value="cancel-order" class="btn btn-primary">強制取消</button>
                        <button type="submit" name="action" value="complete-order" class="btn btn-primary">強制完成</button>
                        @if ($action === 'express-sell')
                        <button type="submit" name="action" value="new-order-transfer" class="btn btn-primary">執行新的下發</button>
                        @endif
                    </form>
                    <div class="mt-3">
                        <b>說明</b>
                        <ul>
                            <li>強制取消：訂單將取消，加密貨幣將<b>不會</b>釋放給買家。確認買家未付款時使用。</li>
                            <li>強制完成：訂單將完成，加密貨幣將釋放給買家。確認買家已付款時使用。</li>
                            @if ($action === 'express-sell')
                            <li>執行新的下發：當原本的下發確定已失敗，向三方支付要求發送新的代付，下發至賣家的銀行卡。<br>當下發成功，訂單將自動完成，不需手動強制完成。</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endcan
</div>
@endsection
