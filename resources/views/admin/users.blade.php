@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
        @if (isset($group))
            群組 <a href="{{ route('admin.groups.show', ['group' => $group->id]) }}">{{ $group->name }}</a> 成員列表
        @else
            用戶列表
        @endif
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>
    <div class="card-block row">
        <div class="col-sm-3">
            @include('widgets.forms.select', ['name' => 'status', 'class' => 'search-control', 'title' => 'Verify Authentication Status', 'value' => (isset($status) ? $status : null), 'values' => __("messages.user.auth_status")])
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="users" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Id</th>
                        <th>UserName</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Group</th>
                        <th>Verification</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Id</th>
                        <th>UserName</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Group</th>
                        <th>Verification</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script>
$(function () {
    var table = $('#users').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/users/search/',
            data: {
                @if (isset($group))
                    group: "{{ $group->id }}",
                @endif
                @if (isset($status))
                    status: "{{ $status }}",
                @endif
            }
        },
        columns: [
            {
                data: 'id',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/users/' + row.id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'username',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/users/' + row.id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'name',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/users/' + row.id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'email',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/users/' + row.id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'group_id',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/groups/' + row.group_id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'authentication_status',
                render: function (data, type, row) {
                    var info = {
                        'passed': { class: 'success', text: @json(__("messages.user.auth_status.passed")) },
                        'rejected': { class: 'danger', text: @json(__("messages.user.auth_status.rejected")) },
                        'processing': { class: 'default', text: @json(__("messages.user.auth_status.processing")) },
                        'unauthenticated': { class: 'default', text: @json(__("messages.user.auth_status.unauthenticated")) },
                    };
                    var i = info[row.authentication_status];
                    return $('<span/>')
                        .text(i.text)
                        .addClass('badge badge-pill badge-' + i.class)
                        .prop('outerHTML');
                },
            },
        ],
    });

    $('#search-submit').on('click', function (e) {
        var status = $('[name="status"] option:selected').val();
        var param = {
            "status": status,
            @if (isset($group))
                group: "{{ $group->id }}",
            @endif
        };
        table.settings()[0].ajax.data = param;
        table
            .ajax
            .url('/admin/users/search/')
            .load(null, false);
    });
});
</script>
@endpush
