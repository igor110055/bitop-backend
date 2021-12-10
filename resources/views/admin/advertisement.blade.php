@php
    use App\Models\Advertisement;
@endphp
@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="card">
    <div class="card-header">
        <h2 class="card-title">廣告：{{ $advertisement->id}}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">廣告資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">快捷廣告</dt>
                    <dd class="col-sm-9">
                        {{ ($advertisement->is_express? 'Yes' : 'No') }}
                    </dd>
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">
                        <span class="text-{{ !$advertisement->deleted_at ? 'default' : 'danger' }}">
                            {{ $advertisement->status }}
                        </span>
                    </dd>
                    @if ($delete_info)
                    <dt class="col-sm-3">刪除時間</dt>
                    <dd class="col-sm-9">{{ $delete_info['deleted_at'] }}</dd>
                    <dt class="col-sm-3">刪除操作者</dt>
                    <dd class="col-sm-9">{{ $delete_info['action'] }}</dd>
                        @if ($delete_info['action'] === 'Admin')
                        <dt class="col-sm-3">Admin</dt>
                        <dd class="col-sm-9">{{ $delete_info['admin'] }}</dd>
                        <dt class="col-sm-3">取消原因</dt>
                        <dd class="col-sm-9">{{ $delete_info['description'] }}</dd>
                        @endif
                    @endif
                    <dt class="col-sm-3">擁有者</dt>
                    <dd class="col-sm-9">{{ $advertisement->owner->username }}</dd>
                    <dt class="col-sm-3">買賣</dt>
                    <dd class="col-sm-9">{{ $advertisement->type }}</dd>
                    <dt class="col-sm-3">幣別</dt>
                    <dd class="col-sm-9">{{ $advertisement->coin }}</dd>
                    <dt class="col-sm-3">數量</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($advertisement->amount) }}</dd>
                    <dt class="col-sm-3">剩餘數量</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($advertisement->remaining_amount) }}</dd>
                    @if ($advertisement->type === 'sell')
                    <dt class="col-sm-3">初始凍結手續費</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($advertisement->fee) }}</dd>
                    <dt class="col-sm-3">剩餘凍結手續費</dt>
                    <dd class="col-sm-9">{{ formatted_coin_amount($advertisement->remaining_fee) }}</dd>
                    @endif
                    <dt class="col-sm-3">法幣</dt>
                    <dd class="col-sm-9">{{ $advertisement->currency }}</dd>
                    <dt class="col-sm-3">單價</dt>
                    <dd class="col-sm-9">{{ $advertisement->unit_price }}</dd>
                    <dt class="col-sm-3">最小限額</dt>
                    <dd class="col-sm-9">{{ $advertisement->min_limit }}</dd>
                    <dt class="col-sm-3">最大限額</dt>
                    <dd class="col-sm-9">{{ $advertisement->max_limit }}</dd>
                    <dt class="col-sm-3">最小交易次數</dt>
                    <dd class="col-sm-9">{{ $advertisement->min_trades }}</dd>
                    <dt class="col-sm-3">付款時限</dt>
                    <dd class="col-sm-9">{{ $advertisement->payment_window }} minutes</dd>
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
                    @if ($bank_account->deleted_at)
                        <span class="badge badge-pill badge-danger">deleted</span>
                    @else
                        <span class="badge badge-pill badge-info">available</span>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>

    @can('edit-advertisements')
    @if ($advertisement->status === Advertisement::STATUS_AVAILABLE)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">強制下架</h3></div>
                <div class="card-block">
                <form action="{{ route('admin.advertisements.update', ['advertisement' => $advertisement]) }}" method="post">
                {{ csrf_field() }}
                {{ method_field('PUT') }}
                    @include('widgets.forms.input', ['name' => 'description', 'value' => '', 'title' => 'Description', 'required' => true])
                    <button type="submit" name="action" value="pull-advertisement" class="btn btn-primary">強制下架</button>
                </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endcan
</div>
@endsection
