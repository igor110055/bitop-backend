@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            @isset($group)
            群組<a href="{{ route('admin.groups.show', ['group' => $group->id]) }}"> {{ $group->name }} </a>
            @endisset
            手續費
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

@foreach ($range_settings as $type => $type_settings)
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
            {{ __("messages.fee_setting.types.{$type}") }}
            </h2>
        </div>
        <div class="card-block">
        @foreach ($type_settings as $coin => $coin_settings)
            <div class="card-block__title">{{ $coin }}</div>
            @if (isset($group) && $coin_settings->isEmpty())
            <p>使用系統設定</p>
            @else
            <div class="table-responsive">
                <table id="fee-settings" class="table table-bordered mt-0">
                    <thead class="thead-default">
                        <tr>
                            <th>範圍起始 ({{ $coin }})</th>
                            <th>範圍小於（{{ $coin }}）</th>
                            <th>手續費</th>
                            <th>單位</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coin_settings as $setting)
                        <tr>
                            <th>{{ $setting->range_start }}</th>
                            <th>{{ $setting->range_end }}</th>
                            <th>{{ $setting->value }}</th>
                            <th>{{ $setting->unit }}</th>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @isset($group)
            <a href="{{ route('admin.groups.fee-settings.edit', ['group' => $group->id, 'type' => $type, 'coin' => $coin]) }}" class="btn btn-primary mb-5">前往設定</a>
            @else
            <a href="{{ route('admin.fee-settings.edit', ['type' => $type, 'coin' => $coin]) }}" class="btn btn-primary mb-5">前往設定</a>
            @endisset
        @endforeach
        </div>
    </div>
@endforeach

@foreach ($fix_settings as $type => $type_settings)
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
            {{ __("messages.fee_setting.types.{$type}") }}
            </h2>
        </div>
        <div class="card-block">
        @foreach ($type_settings as $coin => $coin_settings)
            <div class="card-block__title">{{ $coin }}</div>
            @if (isset($group) && $coin_settings->isEmpty())
            <p>使用系統設定</p>
            @else
            <div class="table-responsive">
                <table id="fee-settings" class="table table-bordered mt-0">
                    <thead class="thead-default">
                        <tr>
                            <th>成本</th>
                            <th>基礎提現金額</th>
                            <th>折扣數 (%)</th>
                            <th>折扣後金額 ({{ $coin }})</th>
                            <th>換算金額 (USD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coin_settings as $setting)
                        <tr>
                            <th>{{ $withdrawal_fee_costs[$coin] }}</th>
                            <th>{{ $withdrawal_fee_base[$coin] }}</th>
                            <th>{{ round($setting->value, 2) }} %</th>
                            <th>{{ $withdrawal_fee[$coin]['amount'] }}</th>
                            <th>{{ $withdrawal_fee[$coin]['price'] }}</th>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            @isset($group)
            <a href="{{ route('admin.groups.fee-settings.edit', ['group' => $group->id, 'type' => $type, 'coin' => $coin]) }}" class="btn btn-primary mb-5">前往設定</a>
            @else
            <a href="{{ route('admin.fee-settings.edit', ['type' => $type, 'coin' => $coin]) }}" class="btn btn-primary mb-5">前往設定</a>
            @endisset
        @endforeach
        </div>
    </div>
@endforeach
@endsection
