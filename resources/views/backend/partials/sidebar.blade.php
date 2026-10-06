<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">
    <div class="navbar-header">
        <ul class="nav navbar-nav flex-row align-items-center">
            <li class="nav-item mr-auto">
                <a class="navbar-brand m-0" href="{{ route('admin.dashboard') }}">
                    <img src="{{ asset($setting->admin_logo ?? 'backend/app-assets/images/logo/logo.png') }}" width="40" alt="logo">
                    <h2 class="brand-text">{{ $setting->admin_title ?? 'My Admin' }}</h2>
                </a>
            </li>
            <li class="nav-item nav-toggle">
                <a class="nav-link modern-nav-toggle pr-0" data-toggle="collapse">
                    <i class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-feather="x"></i>
                    <i class="d-none d-xl-block collapse-toggle-icon font-medium-4 text-primary" data-feather="disc" data-ticon="disc"></i>
                </a>
            </li>
        </ul>
    </div>

    <div class="shadow-bottom"></div>

    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

            <!-- Dashboard -->
            @can('dashboard menu')
            <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.dashboard') }}">
                    <i data-feather="home"></i>
                    <span class="menu-title text-truncate">Dashboard</span>
                </a>
            </li>
            @endcan

            <!-- Users -->
            @can('users menu')
            @php
                $customerCount = \App\Models\User::where('user_type', 'customer')->where('status', '!=', 'active')->count();
                $riderCount = \App\Models\User::where('user_type', 'rider')->where('status', '!=', 'active')->count();
                $deliveryManCount = \App\Models\User::where('user_type', 'delivery_man')->where('status', '!=', 'active')->count();
                $vendorCount = \App\Models\User::where('user_type', 'vendor')->where('status', '!=', 'active')->count();
                $trashCount = \App\Models\User::onlyTrashed()->count();
            @endphp
            <li class="nav-item has-sub {{ request()->routeIs(['admin.sub_admin.*', 'admin.customer.*', 'admin.rider.*', 'admin.delivery_man.*', 'admin.vendor.*', 'admin.trash_user.*']) ? 'open active' : '' }}">
                <a class="d-flex align-items-center" href="#">
                    <i data-feather="users"></i>
                    <span class="menu-title text-truncate">Users</span>
                </a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.sub_admin.*') ? 'active' : '' }}">
                        <a class="d-flex align-items-center" href="{{ route('admin.sub_admin.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Sub Admins</span>
                        </a>
                    </li>
                    @can('customer list')
                    <li class="{{ request()->routeIs('admin.customer.*') ? 'active' : '' }}">
                        <a class="d-flex align-items-center" href="{{ route('admin.customer.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Customer</span>
                            @if($customerCount > 0)
                                <span class="badge rounded-pill bg-danger ms-auto me-1">{{ $customerCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endcan
                    @can('rider list')
                    <li class="{{ request()->routeIs('admin.rider.*') ? 'active' : '' }}">
                        <a class="d-flex align-items-center" href="{{ route('admin.rider.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Rideshare Driver</span>
                            @if($riderCount > 0)
                                <span class="badge rounded-pill bg-danger ms-auto me-1">{{ $riderCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endcan
                    @can('delivery_man list')
                    <li class="{{ request()->routeIs('admin.delivery_man.*') ? 'active' : '' }}">
                        <a class="d-flex align-items-center" href="{{ route('admin.delivery_man.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Delivery Driver</span>
                            @if($deliveryManCount > 0)
                                <span class="badge rounded-pill bg-danger ms-auto me-1">{{ $deliveryManCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endcan
                    @can('vendor list')
                    <li class="{{ request()->routeIs('admin.vendor.*') ? 'active' : '' }}">
                        <a class="d-flex align-items-center" href="{{ route('admin.vendor.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Vendor</span>
                            @if($vendorCount > 0)
                                <span class="badge rounded-pill bg-danger ms-auto me-1">{{ $vendorCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endcan
                    <li class="{{ request()->routeIs('admin.trash_user.*') ? 'active' : '' }}">
                        <a class="d-flex align-items-center" href="{{ route('admin.trash_user.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Trash Users</span>
                            @if($trashCount > 0)
                                <span class="badge rounded-pill bg-danger ms-auto me-1">{{ $trashCount }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            <!-- Roles & Permissions -->
            @can('roles menu')
            <li class="nav-item has-sub {{ request()->routeIs(['admin.role.*', 'admin.permissions.*']) ? 'open active' : '' }}">
                <a class="d-flex align-items-center" href="#">
                    <i data-feather="shield"></i>
                    <span class="menu-title text-truncate">Roles & Permissions</span>
                </a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.role.*') ? 'active' : '' }}">
                        <a class="d-flex align-items-center" href="{{ route('admin.role.list') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Roles</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            <!-- E-Commerce -->
            <li class="navigation-header">
                <span>E-Commerce</span>
                <i data-feather="more-horizontal"></i>
            </li>
            
            @can('delivery_options menu')
            <li class="{{ request()->routeIs('admin.delivery_options.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.delivery_options.index') }}">
                    <i data-feather="truck"></i>
                    <span class="menu-title text-truncate">Delivery Options</span>
                </a>
            </li>
            @endcan

            @can('categories menu')
            <li class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.categories.index') }}">
                    <i data-feather="list"></i>
                    <span class="menu-title text-truncate">Categories</span>
                </a>
            </li>
            @endcan

            @can('vouchers menu')
            <li class="nav-item has-sub {{ request()->routeIs('admin.vouchers.*') || request()->has('type') ? 'open active' : '' }}">
                <a class="d-flex align-items-center" href="#">
                    <i data-feather="gift"></i>
                    <span class="menu-title text-truncate">Vouchers</span>
                </a>
                <ul class="menu-content">
                    <li class="{{ request()->query('type') == 'food' ? 'active' : '' }}">
                        <a href="{{ route('admin.vouchers.index', ['type' => 'food']) }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Food Vouchers</span>
                        </a>
                    </li>
                    <li class="{{ request()->query('type') == 'ride' ? 'active' : '' }}">
                        <a href="{{ route('admin.vouchers.index', ['type' => 'ride']) }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Ride Vouchers</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            @can('support_chat menu')
            <li class="{{ request()->routeIs('admin.support_chat.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.support_chat.index') }}">
                    <i data-feather="message-circle"></i>
                    <span class="menu-title text-truncate">Support Chat</span>
                </a>
            </li>
            @endcan

            <!-- Marketing -->
            <li class="navigation-header">
                <span>Marketing</span>
                <i data-feather="more-horizontal"></i>
            </li>

            @can('campaigns menu')
            <li class="{{ request()->routeIs('admin.campaigns.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.campaigns.index') }}">
                    <i data-feather="target"></i>
                    <span class="menu-title text-truncate">Free Delivery</span>
                </a>
            </li>
            @endcan

            @can('ads menu')
            <li class="{{ request()->routeIs('admin.ads.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.ads.index') }}">
                    <i data-feather="image"></i>
                    <span class="menu-title text-truncate">Ads Management</span>
                </a>
            </li>
            @endcan

            <!-- Tracking & Finance -->
            <li class="navigation-header">
                <span>Tracking & Finance</span>
                <i data-feather="more-horizontal"></i>
            </li>

            @can('orders menu')
            <li class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.orders.index') }}">
                    <i data-feather="shopping-bag"></i>
                    <span class="menu-title text-truncate">Order Tracking</span>
                </a>
            </li>
            @endcan

            @can('rides menu')
            <li class="{{ request()->routeIs('admin.rides.*') ? 'active' : '' }}">
                <a class="d-flex align-items-center" href="{{ route('admin.rides.index') }}">
                    <i data-feather="map-pin"></i>
                    <span class="menu-title text-truncate">Ride Tracking</span>
                </a>
            </li>
            @endcan

            @can('transport menu')
            <li class="nav-item has-sub {{ request()->routeIs(['admin.transport_types.*', 'admin.transport_type_categories.*']) ? 'open active' : '' }}">
                <a class="d-flex align-items-center" href="#">
                    <i data-feather="navigation"></i>
                    <span class="menu-title text-truncate">Transport</span>
                </a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('admin.transport_type_categories.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.transport_type_categories.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Categories</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.transport_types.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.transport_types.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Types</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            <!-- Settings Group -->
            @can('settings menu')
            <li class="navigation-header">
                <span>Settings</span>
                <i data-feather="more-horizontal"></i>
            </li>

            <li class="nav-item has-sub {{ request()->routeIs(['profile', 'system.setting', 'admin.setting', 'admin.setting.mail', 'admin.setting.integration']) ? 'open active' : '' }}">
                <a class="d-flex align-items-center" href="#">
                    <i data-feather="settings"></i>
                    <span class="menu-title text-truncate">System Settings</span>
                </a>
                <ul class="menu-content">
                    <li class="{{ request()->routeIs('profile') ? 'active' : '' }}">
                        <a href="{{ route('profile') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Profile Settings</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('system.setting') ? 'active' : '' }}">
                        <a href="{{ route('system.setting') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">General Settings</span>
                        </a>
                    </li>
                   
                    <li class="{{ request()->routeIs('admin.setting') ? 'active' : '' }}">
                        <a href="{{ route('admin.setting') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Admin Settings</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.order_settings.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.order_settings.index') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Order Settings (VAT)</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.setting.mail') ? 'active' : '' }}">
                        <a href="{{ route('admin.setting.mail') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Mail Settings</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.setting.integration') ? 'active' : '' }}">
                        <a href="{{ route('admin.setting.integration') }}">
                            <i data-feather="circle"></i>
                            <span class="menu-item">Integration Settings</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

        </ul>
    </div>
</div>