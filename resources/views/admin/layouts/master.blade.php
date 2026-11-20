<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Admin Dashboard')</title>
    

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @include('partials.typography', ['surface' => 'admin'])
    
<style>
    /* --- ✨ GLOBAL & LAYOUT STYLES ✨ --- */
    :root {
        --sidebar-bg: #111827;
        --sidebar-link-color: #9CA3AF;
        --sidebar-link-hover: #F9FAFB;
        --sidebar-link-active: #3B82F6;
        --content-bg: #F9FAFB;
        --navbar-bg: #FFFFFF;
        --text-color: #1F2937;
        --text-muted: #6B7280;
        --card-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --border-color: #E5E7EB;
    }
    body { background-color: var(--content-bg); font-family: var(--font-ui); color: var(--text-color); }
    .sidebar { background-color: var(--sidebar-bg); }
    @media (min-width: 992px) {
        .main-layout { display: grid; grid-template-columns: 280px minmax(0, 1fr); } /* minmax(0,..): a wide table scrolls inside its box instead of widening the page */
        .main-content { min-width: 0; }
        .sidebar { position: sticky; top: 0; height: 100vh; }
    }
    .offcanvas-header { background: var(--sidebar-bg); border-bottom: 1px solid rgba(255,255,255,0.1); }
    .offcanvas-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

    /************************************************************/
    /* --- ✨ STYLES FOR YOUR CUSTOM LOGO ✨ --- */
    /************************************************************/
    .sidebar-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        color: white;
        font-weight: 700;
        padding: 1.25rem 1rem;
        text-decoration: none;
        letter-spacing: 1px;
        font-size: 1.1rem;
    }
    .sidebar-brand-logo {
        height: 30px; /* Adjust the height of your logo here */
        width: auto;
    }
    
    .sidebar .nav-link { color: var(--sidebar-link-color); padding: 0.8rem 1.5rem; font-size: 0.95rem; font-weight: 500; transition: all 0.2s ease-in-out; border-left: 3px solid transparent; display: flex; align-items: center; gap: 1rem; }
    .sidebar .nav-link:hover { color: var(--sidebar-link-hover); background: rgba(255, 255, 255, 0.05); border-left-color: var(--sidebar-link-active); }
    .sidebar .nav-link.active { color: white; font-weight: 600; background: var(--sidebar-link-active); border-left-color: #a7c8fd; }
    .sidebar .nav-link .fa-fw { width: 1.25em; text-align: center; }
    .sidebar .collapse .nav-link { font-size: 0.85rem; padding-left: 3.5rem; }
    .sidebar-heading { padding: 0.75rem 1.5rem; font-size: 0.8125rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
    .top-nav { background-color: var(--navbar-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1020; }
    .card { border: 1px solid var(--border-color); border-radius: 0.75rem; box-shadow: none; transition: all 0.2s ease; }
    .card:hover { transform: translateY(-4px); box-shadow: var(--card-shadow); }
    .card-header { background-color: #fff; border-bottom: 1px solid var(--border-color); font-weight: 600; }
    .stat-card .card-body { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .stat-card-icon { display: flex; align-items: center; justify-content: center; width: 3.5rem; height: 3.5rem; border-radius: 50%; font-size: 1.5rem; color: #fff; }
    .stat-card-content .stat-label { font-size: 0.85rem; font-weight: 500; text-transform: uppercase; color: var(--text-muted); }
    .stat-card-content .stat-value { font-size: 1.75rem; font-weight: 700; color: var(--text-color); }
    .stat-card-icon.bg-primary { background-color: #3B82F6; }
    .stat-card-icon.bg-success { background-color: #10B981; }
    .stat-card-icon.bg-warning { background-color: #F59E0B; }
    .stat-card-icon.bg-info { background-color: #38BDF8; }
    .placeholder-card-body { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 250px; background-color: #fff; border-top: 1px solid var(--border-color); text-align: center; }
    @keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .row > [class*='col-'] { animation: slideInUp 0.5s ease-out forwards; }
    .row > [class*='col-']:nth-child(2) { animation-delay: 0.1s; } .row > [class*='col-']:nth-child(3) { animation-delay: 0.2s; } .row > [class*='col-']:nth-child(4) { animation-delay: 0.3s; }
    @media (max-width: 767.98px) {
        .table-responsive-cards thead { display: none !important; }
        .table-responsive-cards tbody, .table-responsive-cards tr, .table-responsive-cards td { display: block !important; width: 100% !important; }
        .table-responsive-cards tr { margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: 0.5rem; overflow: hidden; }
        .table-responsive-cards td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .table-responsive-cards tr td:last-child { border-bottom: 0; }
        .table-responsive-cards td::before { content: attr(data-label); display: block; font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem; }
        .table-responsive-cards td[data-label="Actions"] { padding-top: 1rem; padding-bottom: 1rem; }
        .table-responsive-cards td[data-label="Actions"] .action-buttons { justify-content: flex-start; }
    }
    body, .card, .card-body, .table { overflow-wrap: break-word; word-wrap: break-word; word-break: break-word; -webkit-hyphens: auto; -ms-hyphens: auto; hyphens: auto; }
    .action-buttons { display: flex; gap: 0.5rem; justify-content: flex-end; flex-wrap: wrap; }

/* ---- Touch targets: at least 44 x 44 px on phones and tablets (measured by tools/local/responsive-audit.mjs) ---- */
.table-wrapper, .table-responsive { position: relative; overflow-x: auto; -webkit-overflow-scrolling: touch; } /* relative: a visually-hidden header cell must not stick out of the scroll box */
@media (max-width: 1024px), (pointer: coarse) {
    .btn, .navbar-toggler, .page-link, .form-control, .form-select { min-height: 44px; }
    .navbar-toggler { min-width: 44px; }
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; }
    .btn-sm { min-width: 44px; }
    .sidebar .nav-link, .offcanvas .nav-link { min-height: 46px; display: flex; align-items: center; }
    .news-editor__toolbar .btn { min-width: 44px !important; min-height: 44px !important; }
    .page-link { min-width: 44px; justify-content: center; }
    .card-body > a:not(.btn), .card-header > a:not(.btn) { display: inline-flex; align-items: center; min-height: 44px; }
    td .form-check-input, th .form-check-input { margin: 0; }
    .sky-tap { cursor: pointer; min-width: 44px; min-height: 44px; vertical-align: middle; }
    th.sky-tap { height: 48px; }
    .form-check-input { width: 1.35rem; height: 1.35rem; }
    .form-check, .form-check-label { min-height: 44px; }
}
@media (max-width: 575.98px) { .form-label.small, .form-text, .small.text-muted { font-size: 1rem; } }
    </style>

 <link href="{{ asset('assets/css/sky-confirm.css') }}" rel="stylesheet">
@stack('styles')
</head>
<body>
    @php($adminUnreadInbox = \App\Models\Inquiry::unread()->count())
    
    <div class="main-layout">
        <!-- ===== DESKTOP SIDEBAR ===== -->
        <nav class="sidebar d-none d-lg-flex flex-column">
            
            {{-- ✨ YOUR NEW LOGO IS HERE ✨ --}}
            <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
                <img src="{{ asset('assets/images/skylink-logo.webp') }}" alt="Skylink Logo" class="sidebar-brand-logo">
                <span>SKYLINK ADMIN</span>
            </a>

            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.inbox.*') ? 'active' : '' }}" href="{{ route('admin.inbox.index') }}"><i class="fas fa-inbox fa-fw"></i> Inbox @if ($adminUnreadInbox)<span class="badge rounded-pill text-bg-danger ms-auto">{{ $adminUnreadInbox }}<span class="visually-hidden"> unread</span></span>@endif</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.anniversary.*') ? 'active' : '' }}" href="{{ route('admin.anniversary.edit') }}"><i class="fas fa-champagne-glasses fa-fw"></i> Anniversary Ceremony</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.hero-video.*') ? 'active' : '' }}" href="{{ route('admin.hero-video.edit') }}"><i class="fas fa-film fa-fw"></i> Homepage Video</a></li>
                <li class="sidebar-heading mt-2">Content</li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#contentCollapseDesktop"><i class="fas fa-file-alt fa-fw"></i> Manage Content <i class="fas fa-chevron-down ms-auto"></i></a>
                    <div class="collapse {{ request()->routeIs('admin.portfolio.*', 'admin.news.*', 'admin.videos.*') ? 'show' : '' }}" id="contentCollapseDesktop">
                        <ul class="nav flex-column"><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.portfolio.*') ? 'active' : '' }}" href="{{ route('admin.portfolio.index') }}">Portfolio</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">News</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.videos.*') ? 'active' : '' }}" href="{{ route('admin.videos.index') }}">Videos</a></li></ul>
                    </div>
                </li>
                <li class="sidebar-heading mt-2">Management</li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="collapse" href="#managementCollapseDesktop"><i class="fas fa-cogs fa-fw"></i> Site Management <i class="fas fa-chevron-down ms-auto"></i></a>
                    <div class="collapse {{ request()->routeIs('admin.services.*', 'admin.service-categories.*', 'admin.products.*', 'admin.subscribers.*', 'admin.contact-messages.*') ? 'show' : '' }}" id="managementCollapseDesktop">
                        <ul class="nav flex-column"><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}" href="{{ route('admin.services.index') }}">Services</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.service-categories.*') ? 'active' : '' }}" href="{{ route('admin.service-categories.index') }}">Service Categories</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">Products</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.subscribers.*') ? 'active' : '' }}" href="{{ route('admin.subscribers.index') }}">Subscribers</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}" href="{{ route('admin.contact-messages.index') }}">Messages</a></li></ul>
                    </div>
                </li>
            </ul>
            <div class="mt-auto"><hr class="my-3" style="border-color: rgba(255,255,255,0.1);"><ul class="nav flex-column p-2"><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}"><i class="fas fa-user-circle fa-fw"></i> My Profile</a></li><li class="nav-item"><a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li></ul></div>
        </nav>

        <!-- ===== MOBILE OFFCANVAS SIDEBAR ===== -->
        <div class="offcanvas offcanvas-start sidebar d-lg-none" tabindex="-1" id="sidebarMenu">
            <div class="offcanvas-header">

                {{-- ✨ YOUR NEW LOGO IS HERE TOO ✨ --}}
                <a href="{{ route('admin.dashboard') }}" class="sidebar-brand p-0">
                    <img src="{{ asset('assets/images/skylink-logo.webp') }}" alt="Skylink Logo" class="sidebar-brand-logo">
                    <span>SKYLINK ADMIN</span>
                </a>
                
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.inbox.*') ? 'active' : '' }}" href="{{ route('admin.inbox.index') }}"><i class="fas fa-inbox fa-fw"></i> Inbox @if ($adminUnreadInbox)<span class="badge rounded-pill text-bg-danger ms-auto">{{ $adminUnreadInbox }}<span class="visually-hidden"> unread</span></span>@endif</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.anniversary.*') ? 'active' : '' }}" href="{{ route('admin.anniversary.edit') }}"><i class="fas fa-champagne-glasses fa-fw"></i> Anniversary Ceremony</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.hero-video.*') ? 'active' : '' }}" href="{{ route('admin.hero-video.edit') }}"><i class="fas fa-film fa-fw"></i> Homepage Video</a></li>
                    <li class="sidebar-heading mt-2">Content</li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#contentCollapseMobile"><i class="fas fa-file-alt fa-fw"></i> Manage Content <i class="fas fa-chevron-down ms-auto"></i></a>
                        <div class="collapse {{ request()->routeIs('admin.portfolio.*', 'admin.news.*', 'admin.videos.*') ? 'show' : '' }}" id="contentCollapseMobile">
                            <ul class="nav flex-column"><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.portfolio.*') ? 'active' : '' }}" href="{{ route('admin.portfolio.index') }}">Portfolio</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}" href="{{ route('admin.news.index') }}">News</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.videos.*') ? 'active' : '' }}" href="{{ route('admin.videos.index') }}">Videos</a></li></ul>
                        </div>
                    </li>
                    <li class="sidebar-heading mt-2">Management</li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#managementCollapseMobile"><i class="fas fa-cogs fa-fw"></i> Site Management <i class="fas fa-chevron-down ms-auto"></i></a>
                        <div class="collapse {{ request()->routeIs('admin.services.*', 'admin.service-categories.*', 'admin.products.*', 'admin.subscribers.*', 'admin.contact-messages.*') ? 'show' : '' }}" id="managementCollapseMobile">
                            <ul class="nav flex-column"><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}" href="{{ route('admin.services.index') }}">Services</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.service-categories.*') ? 'active' : '' }}" href="{{ route('admin.service-categories.index') }}">Service Categories</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">Products</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.subscribers.*') ? 'active' : '' }}" href="{{ route('admin.subscribers.index') }}">Subscribers</a></li><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}" href="{{ route('admin.contact-messages.index') }}">Messages</a></li></ul>
                        </div>
                    </li>
                </ul>
                <div class="mt-auto"><hr class="my-3" style="border-color: rgba(255,255,255,0.1);"><ul class="nav flex-column p-2"><li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}"><i class="fas fa-user-circle fa-fw"></i> My Profile</a></li><li class="nav-item"><a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt fa-fw"></i> Logout</a></li></ul></div>
            </div>
        </div>

        <!-- ===== MAIN CONTENT ===== -->
        <main class="main-content">
            <nav class="navbar navbar-light top-nav px-3"><button class="navbar-toggler border-0 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><i class="fas fa-bars"></i></button><div class="ms-auto"><div class="dropdown"><button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-user-circle me-1"></i> {{ Auth::user()->name ?? 'Admin' }}</button><ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="{{ route('admin.profile.edit') }}"><i class="fas fa-user-edit me-2"></i>My Profile</a></li><li><a class="dropdown-item" href="{{ url('/') }}" target="_blank" rel="noopener"><i class="fas fa-eye me-2"></i>View Site</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li></ul></div></div></nav>
            <div class="p-4"><div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0">@yield('page-title', 'Dashboard')</h1></div>@if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif @if(session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
                @yield('content')
            </div>
        </main>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none"> @csrf </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/sky-confirm.js') }}"></script>
@stack('scripts')
</body>
</html>