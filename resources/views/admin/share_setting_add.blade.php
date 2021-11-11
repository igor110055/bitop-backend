@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])

<div class="card">
    <div class="card-header">
        <h2 class="card-title">群組 <a href="{{ route('admin.groups.show', ['group' => $group]) }}">{{ $group->name }}</a> 新增分帳設定</h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <form method="post" action="{{ route('admin.groups.share-settings.store', ['group' => $group]) }}">
            {{ csrf_field() }}

            @include('widgets.forms.select', ['name' => 'user_id', 'class' => 'user-search-select', 'values' => [], 'title' => 'Owner', 'required' => true])
            @include('widgets.forms.input', ['name' => 'percentage', 'type' => 'number', 'title' => '百分比', 'required' => true, 'placeholder' => '請輸入數字，有效至小數點下2位', 'attributes' => 'min="0" max="100" step="0.01"', 'help' => ''])
            <small></small>

            <a href="{{ route('admin.groups.share-settings', ['group' => $group]) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    @include('widgets.forms.user_select')
});
</script>
@endpush
