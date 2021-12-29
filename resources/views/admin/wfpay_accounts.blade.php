@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Wfpay 帳號設定</h2>
    </div>
</div>
@foreach ($accounts as $account)
<form method="post" action="{{ route('admin.wfpays.store') }}">
    {{ csrf_field() }}
    {{ method_field('POST') }}

    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ $account->id }}</h3></div>
        <div class="card-block">
            <input type="hidden" name="id" value="{{ $account->id }}">
            @include('widgets.forms.select', ['name' => 'is_active', 'value' => ((data_get($account, 'is_active') === true) ? 1 : 0), 'title' => '啟用/停用', 'values' => [1 => '啟用', 0 => '停用'], 'required' => true])
            @include('widgets.forms.input', ['name' => "rank", 'type'=>"number", 'value' => data_get($account, 'rank'), 'title' => '優先度', 'required' => true, 'help' => '數字越大，優先度越高'])

            <div class="mb-2">可用支付方式</div>
            @foreach ($methods as $method)
            <label class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" name="methods[]" value="{{ $method }}" {{ in_array($method, data_get($account, "configs.payment_methods", []))? "checked": "" }} >
                <span class="custom-control-indicator"></span>
                <span class="custom-control-description">{{ $method }}</span>
            </label>
            <div class="clearfix mb-2"></div>
            @endforeach
        </div>
        <div class="card-block">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endforeach

@endsection
