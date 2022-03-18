@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">編輯用戶：{{ $user->username }}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<form method="post" action="{{ route('admin.users.update', ['user' => $user->id]) }}">
{{ csrf_field() }}
{{ method_field('PUT') }}

    <div class="card">
        <div class="card-header"><h3 class="card-title">User Info</h3></div>
        <div class="card-block">
            @include('widgets.forms.input', ['name' => 'id', 'value' => $user->id, 'title' => 'ID', 'disabled' => true])
            @include('widgets.forms.input', ['name' => 'name', 'value' => old('name', $user->username), 'title' => 'Username', 'required' => true])
            @include('widgets.forms.select', ['name' => 'group_id', 'value' => $user->group_id, 'title' => '所屬群組', 'values' => $groups, 'required' => true])
            @include('widgets.forms.input', ['name' => 'password', 'value' => '', 'title' => '重新設置密碼', 'help' => '若不重設密碼，請留空白'])
            <a href="/admin/users" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>


</form>
@endsection

@push('scripts')
<script>
$(function () {
});
</script>
@endpush
