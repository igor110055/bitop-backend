@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">群組管理</h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <a
            href="{{ route('admin.groups.create') }}"
            class="btn btn-primary waves-effect"
        ><i class="zmdi zmdi-plus"></i> 新增群組</a>
        <a
            href="{{ route('admin.groups.applications') }}"
            class="btn btn-primary waves-effect"
        >群組申請列表</a>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="groups" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Id</th>
                        <th>Group Name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                    <tr>
                        <td><a href="{{ route('admin.groups.show', ['group' => $group->id]) }}">{{ $group->id }}</a></td>
                        <td><a href="{{ route('admin.groups.show', ['group' => $group->id]) }}">{{ $group->name }}</a></td>
                        <td>
                            <a class="mr-3" href="{{ route('admin.groups.users', ['group' => $group->id]) }}">成員列表</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/vendors/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="/vendors/bower_components/jszip/dist/jszip.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script>
$(function(){
    var table = $('#groups').DataTable();
});
</script>
@endpush
