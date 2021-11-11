@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" action="{{ route('admin.groups.store') }}">
{{ csrf_field() }}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">群組管理：{{ $page_title }}</h2>
            <!--small class="card-subtitle"></small-->
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Group Info</h3></div>
        <div class="card-block">
            @include('widgets.forms.input', ['name' => 'id', 'class' => 'text-lowercase', 'value' => old('id', $group->id), 'title' => 'ID', 'required' => true, 'placeholder' => '請輸入小寫英文、數字、dash、底線，至少 6 個字元'])
            @include('widgets.forms.input', ['name' => 'name', 'value' => old('name', $group->name), 'title' => 'Name', 'required' => true])
            @include('widgets.forms.select', ['name' => 'user_id', 'class' => 'user-search-select', 'values' => [], 'title' => 'Owner', 'required' => true])
            <a href="/admin/groups" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    $(function () {
        @include('widgets.forms.user_select', ['user' => $group->owner])
    });
</script>
@endpush
