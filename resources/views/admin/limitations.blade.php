@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            @if (isset($group))
            Group {{ $group->id }}
            @elseif (isset($user))
            User {{ $user->username }}
            @endif
            限額設定
        </h2>
    </div>
</div>

@foreach ($limitations as $type => $type_limitations)
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
            @lang("messages.limitation.types.{$type}")
            </h2>
        </div>
        <div class="card-block">
            <div class="table-responsive">
                <table class="table table-bordered mt-0">
                    <thead class="thead-default">
                        <tr>
                            <th>幣別</th>
                            <th>下限</th>
                            <th>上限</th>
                            <th>設定</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($type_limitations as $coin => $limitation)
                        <tr>
                            <th>{{ $coin }}</th>
                            <th>{{ data_get($limitation, 'min') }}</th>
                            <th>{{ data_get($limitation, 'max') }}</th>
                            <th>
                            @if (isset($group))
                            <a href="{{ route('admin.groups.limitations.edit', ['group' => $group, 'type' => $type, 'coin' => $coin]) }}">設定</a>
                            @elseif (isset($user))
                            <a href="{{ route('admin.users.limitations.edit', ['user' => $user, 'type' => $type, 'coin' => $coin]) }}">設定</a>
                            @else
                            <a href="{{ route('admin.limitations.edit', ['type' => $type, 'coin' => $coin]) }}">設定</a>
                            @endif
                            </th>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
@endsection
