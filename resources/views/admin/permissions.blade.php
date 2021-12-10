@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">權限管理</h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="groups" class="table table-bordered">
                <thead class="thead-default">
                    <tr>
                        <th>Roles</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    @if ($role->name !== 'super-admin')
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td>
                            <ul class="list list--check">
                            @foreach ($role->permissions as $permission)
                                <li>{{ $permission->name }}</li>
                            @endforeach
                            </ul>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
