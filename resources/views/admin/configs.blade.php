@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">全站設定</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<form method="post" action="{{ route('admin.configs.wallet-activation') }}">
{{ csrf_field() }}
{{ method_field('POST') }}

    <div class="card">
        <div class="card-header"><h3 class="card-title">Wallet 設定</h3></div>
        <div class="card-block">
            @include('widgets.forms.select', ['name' => 'wallet[deactivated]', 'value' => ((data_get($wallet_configs, 'deactivated') === true) ? 1 : 0), 'title' => '停用充值、提現功能', 'values' => [1 => '是', 0 => '否'], 'required' => true])
        </div>
        <div class="card-block">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
<form method="post" action="{{ route('admin.configs.withdrawal-fee-factor') }}">
{{ csrf_field() }}
{{ method_field('POST') }}

    <div class="card">
        <div class="card-header"><h3 class="card-title">提現手續費公式參數設定</h3></div>
        @foreach ($coins as $coin)
        <div class="card-block">
            <div class="card-block__title">{{ $coin }}</div>
            @include('widgets.forms.input', ['name' => "{$coin}_base", 'class' => 'text-lowercase', 'value' => data_get($withdrawal_fee_factor, "$coin.base"), 'title' => '基礎提現手續費', 'required' => true])
            @include('widgets.forms.input', ['name' => "{$coin}_pw_ratio", 'class' => 'text-lowercase', 'value' => data_get($withdrawal_fee_factor, "$coin.pw_ratio"), 'title' => 'Payout/Withdrawal比值', 'required' => true])
        </div>
        @endforeach
        <div class="card-block">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
<form method="post" action="{{ route('admin.configs.withdrawal-limit') }}">
{{ csrf_field() }}
{{ method_field('POST') }}

    <div class="card">
        <div class="card-header"><h3 class="card-title">提現限額設定</h3></div>
        <div class="card-block">
            @include('widgets.forms.input', ['name' => "daily_limit", 'class' => 'text-lowercase', 'value' => data_get($withdrawal_limit, 'daily'), 'title' => '每日提現限額(Base currency)', 'required' => true])
        </div>
        <div class="card-block">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
<form method="post" action="{{ route('admin.configs.app-version') }}">
{{ csrf_field() }}
{{ method_field('POST') }}

    <div class="card">
        <div class="card-header"><h3 class="card-title">版號設定</h3></div>
        @foreach (['web', 'ios', 'android'] as $platform)
        <div class="card-block">
            <div class="card-block__title">{{ $platform }}</div>
            @include('widgets.forms.input', ['name' => "{$platform}_latest", 'class' => 'text-lowercase', 'value' => data_get($app_versions, "$platform.latest"), 'title' => '最新版號', 'required' => true])
            @include('widgets.forms.input', ['name' => "{$platform}_min", 'class' => 'text-lowercase', 'value' => data_get($app_versions, "$platform.min"), 'title' => '支援最舊版號', 'required' => true])
        </div>
        @endforeach
        <div class="card-block">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection
