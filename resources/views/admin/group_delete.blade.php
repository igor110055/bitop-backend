@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])

<form method="post" action="{{ route('admin.groups.destroy', ['group' => $group->id]) }}">
{{ csrf_field() }}
{{ method_field('DELETE') }}

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">刪除群組：{{ $group->id }}</h2>
            <small class="card-subtitle">需將群組成員將全部移至其他群組</small>
        </div>
        <div class="card-block">
            @include('widgets.forms.select', ['name' => 'group_id', 'value' => '', 'title' => '將成員移置群組', 'values' => $groups, 'required' => true])
            @can('edit-groups')
            <button type="submit" class="btn btn-primary">確定刪除</button>
            @endcan
        </div>
    </div>


</form>
@endsection

@push('scripts')
<script>

</script>
@endpush
