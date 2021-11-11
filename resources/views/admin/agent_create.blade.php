@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" action="{{ route('admin.agencies.agents.store', ['agency' => $agency->id]) }}">
    {{ csrf_field() }}
    <div class="card">
        <div class="card-header">
        <h2 class="card-title">新增業務：<a href="{{ route('admin.agencies.show', ['agency' => $agency->id]) }}"
            >{{ $agency->name }}</a></h2>
            <!--small class="card-subtitle"></small-->
        </div>
    </div>
    <div class="card">
        <div class="card-block">
            @include('widgets.forms.select', ['name' => 'user_id', 'class' => 'user-search-select', 'values' => [], 'title' => '選擇用戶', 'required' => true])
            <a href="{{ route('admin.agencies.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function () {
    @include('widgets.forms.user_select')
});
</script>
@endpush