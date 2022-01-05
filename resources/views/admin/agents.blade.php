@push('styles')
    <link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">業務管理：<a href="{{ route('admin.agencies.show', ['agency' => $agency->id]) }}"
            >{{ $agency->name }}</a></h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <a
            href="{{ route('admin.agencies.agents.create', ['agency' => $agency->id]) }}"
            class="btn btn-primary waves-effect"
        ><i class="zmdi zmdi-plus"></i> 新增業務</a>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="agents" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Id</th>
                        <th>UserName</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Group</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agents as $agent)
                    <tr>
                        <td><a href="{{ route('admin.users.show', ['user' => $agent->id]) }}">{{ $agent->id }}</a></td>
                        <td><a href="{{ route('admin.users.show', ['user' => $agent->id]) }}">{{ $agent->username }}</a></td>
                        <td><a href="{{ route('admin.users.show', ['user' => $agent->id]) }}">{{ $agent->name }}</a></td>
                        <td><a href="{{ route('admin.users.show', ['user' => $agent->id]) }}">{{ $agent->email }}</a></td>
                        <td><a href="{{ route('admin.groups.show', ['group' => $agent->group->id]) }}">{{ $agent->group->name }}</a></td>
                        <td><a class="delete" data-user-id="{{ $agent->id }}" href="#">刪除</a></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>Id</th>
                        <th>UserName</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Group</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
    $(function(){
        var table = $('#agents').DataTable();
    });

    $('#agents').on('click', '.delete', function (e) {
        e.preventDefault();
        var button = $(this);
        swal({
            title: '確認執行該操作?',
            text: '刪除此業務',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    type: 'POST',
                    url: '{{ route('admin.agencies.agents.delete', ['agency' => $agency->id]) }}',
                    data: {
                        _token: '{{ csrf_token() }}',
                        user_id: button.data('user-id'),
                    },
                    dataType: 'json',
                }).done(function () {
                    window.location.reload();
                }).fail(function (err) {
                    $.notify({ message: '有東西出錯了，請重新整理' }, { type: 'danger' });
                })
            }
        }).catch(swal.noop);
    });
</script>
@endpush
