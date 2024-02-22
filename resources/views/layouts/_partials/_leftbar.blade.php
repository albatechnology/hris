<aside class="main-sidebar sidebar-light-orange elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('dashboard.index') }}" class="brand-link bg-light">
        <img src="{{ asset('img/logo.png') }}" alt="img" class="brand-image">
        <span class="brand-text font-weight-light">{{ env('APP_NAME', '&nbsp;') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('admin-lte/dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ auth()->user()->name }}</a>
            </div>
        </div>

        <!-- SidebarSearch Form -->
        <div class="form-inline mt-3">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

                @foreach (\App\Services\MenuService::menu() as $menu)
                @if (count($menu->submenus) <= 1) @if (auth()->user()->type->hasPermission($menu->submenus[0]->permission, 'read'))
                    <li class="nav-item {{ request()->is($menu->submenus[0]->url . '*') ? 'menu-open' : null }}">
                        <a href="{{ url($menu->submenus[0]->url) }}" class="nav-link {{ request()->is($menu->submenus[0]->url . '*') ? 'active' : null }}">
                            <i class="nav-icon {{ $menu->icon }}"></i>
                            <p>
                                {{ $menu->submenus[0]->title }}
                            </p>
                        </a>
                    </li>
                    @endif
                    @else
                    @if (in_array(true, collect(\Arr::pluck($menu->submenus, 'permission'))->map(fn($v) => auth()->user()->type->hasPermission($v, 'read'))->toArray()))
                    <li class="nav-item {{ request()->is($menu->getAllSubmenuRoutes()) ? 'menu-open' : null }}">
                        <a href="javascript:;" class="nav-link {{ request()->is($menu->getAllSubmenuRoutes()) ? 'active' : null }}">
                            <i class="nav-icon {{ $menu->icon }}"></i>
                            <p>
                                {{ $menu->title }}
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">
                            @foreach ($menu->submenus as $submenu)
                            @if (auth()->user()->type->hasPermission($submenu->permission, 'read'))
                            <li class="nav-item active">
                                <a href="{{ url($submenu->url) }}" class="nav-link {{ request()->is($submenu->url . '*') ? 'active' : null }}">
                                    <i class="{{ $submenu->icon }} nav-icon"></i>
                                    <p> {{ $submenu->title }}</p>
                                </a>
                            </li>
                            @endif
                            @endforeach
                        </ul>
                    </li>
                    @endif
                    @endif
                    @endforeach
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
