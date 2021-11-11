@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" action="{{ route('admin.fee-settings-fixed.store') }}">
{{ csrf_field() }}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                @isset($group)
                群組<a href="{{ route('admin.groups.show', ['group' => $group->id]) }}"> {{ $group->name }} </a>
                @endisset
                {{ $coin.__("messages.fee_setting.types.{$type}") }}
            </h2>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">利潤基底：{{ $base }} {{ $coin }}</h3></div>
        <div class="card-block">
            @isset($group)
            <input type="hidden" name="applicable_id" value="{{ $group->id }}"></input>
            @endisset
            <input type="hidden" name="coin" value="{{ $coin }}"></input>
            <input type="hidden" name="type" value="{{ $type }}"></input>
            @include('widgets.forms.input', ['name' => 'discount', 'class' => 'text-lowercase', 'value' => $discount, 'title' => '折扣數(%)', 'required' => true, 'placeholder' => '請輸入折扣數(%)'])
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection
