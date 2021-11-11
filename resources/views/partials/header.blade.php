<header class="header">
    @if(Auth::check())
    <div class="navigation-trigger hidden-xl-up" data-ma-action="aside-open" data-ma-target=".sidebar">
        <div class="navigation-trigger__inner">
            <i class="navigation-trigger__line"></i>
            <i class="navigation-trigger__line"></i>
            <i class="navigation-trigger__line"></i>
        </div>
    </div>
    @endif

    <div class="header__logo hidden-sm-down">
        <h1><a href="{{ route('admin.index') }}">{{ Config::get('app.name') }}</a></h1>
    </div>

    @if(Auth::check())
    <ul class="top-nav">

        <li class="dropdown hidden-xs-down">
            <a href="" data-toggle="dropdown"><i class="zmdi zmdi-more-vert"></i></a>

            <div class="dropdown-menu dropdown-menu-right">
                <a href="/" class="dropdown-item"
                    ><i class="zmdi zmdi-home zmdi-hc-fw"></i> Home</a>
                <a href="{{ route('admin.logout') }}" class="dropdown-item"
                    ><i class="zmdi zmdi-sign-in zmdi-hc-fw"></i> Logout</a>
            </div>
        </li>

    </ul>
    @endif
</header>

@if(Auth::guest())
@push('styles')
<style>
    header.header {
        position: relative;
    }
</style>
@endpush
@endif
