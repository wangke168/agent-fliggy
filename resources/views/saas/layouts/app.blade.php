<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SaaS Platform')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --app-bg-color: #f0f2f5;
            --sidebar-bg: #ffffff;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 80px;
            --header-bg: #000000;
            --header-height: 60px;
            --header-text-color: #ffffff;
            --border-color: #e8e8e8;
            --text-color: #262626;
            --menu-item-color: #595959;
            --menu-item-hover-bg: #f0f2f5;
            --menu-item-active-bg: #e6f7ff;
            --menu-item-active-color: #1890ff;
        }
        body {
            background-color: var(--app-bg-color);
            font-size: 14px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .top-header {
            height: var(--header-height);
            background-color: var(--header-bg);
            color: var(--header-text-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        .main-body {
            display: flex;
            flex-grow: 1;
            padding-top: var(--header-height);
        }
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: width 0.2s ease-in-out;
            overflow-x: hidden;
            position: relative; /* Crucial for positioning the toggle button */
        }
        .sidebar-menu {
            padding: 8px;
        }
        .sidebar-menu .nav-link {
            color: var(--menu-item-color);
            margin-bottom: 4px;
            padding: 10px 16px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        .sidebar-menu .nav-link i {
            margin-right: 10px;
            font-size: 16px;
        }
        .sidebar-menu .nav-link:hover {
            background-color: var(--menu-item-hover-bg);
        }
        .sidebar-menu .nav-link.active {
            background-color: var(--menu-item-active-bg);
            color: var(--menu-item-active-color);
            font-weight: 600;
        }
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        .sidebar.collapsed .sidebar-menu .nav-link span {
            display: none;
        }
        .sidebar.collapsed .sidebar-menu .nav-link {
            justify-content: center;
        }
        .sidebar.collapsed .sidebar-menu .nav-link i {
            margin-right: 0;
            font-size: 20px;
        }
        .content-workspace {
            padding: 24px;
            flex-grow: 1;
        }
        .sidebar-toggle {
            position: absolute;
            top: 20px;
            right: 0; /* Positioned at the right edge of the sidebar */
            transform: translateX(50%); /* Move it halfway out */
            width: 24px;
            height: 24px;
            background-color: #fff;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1020;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: transform 0.3s;
        }
        .sidebar.collapsed .sidebar-toggle {
            transform: translateX(50%) rotate(180deg);
        }
    </style>
</head>
<body>
    <header class="top-header">
        <div class="d-flex align-items-center">
            <i class="bi bi-app-indicator me-3" style="font-size: 24px;"></i>
            <span class="fs-5">SaaS Platform</span>
        </div>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="https://i.pravatar.cc/32" alt="" width="32" height="32" class="rounded-circle me-2">
                <strong>Admin User</strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#">Sign out</a></li>
            </ul>
        </div>
    </header>

    <div class="main-body">
        <aside class="sidebar" id="sidebar">
            <ul class="nav flex-column sidebar-menu mt-3">
                <li class="nav-item">
                    <a class="nav-link" href="#" title="Dashboard"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('saas.products.index') }}" title="Products"><i class="bi bi-box-seam"></i> <span>Products</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" title="Orders"><i class="bi bi-receipt"></i> <span>Orders</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" title="Accounts"><i class="bi bi-people"></i> <span>Accounts</span></a>
                </li>
            </ul>
            <div class="sidebar-toggle" id="sidebar-toggle">
                <i class="bi bi-chevron-left"></i>
            </div>
        </aside>

        <main class="content-workspace">
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');

            const toggleSidebar = () => {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            };

            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', toggleSidebar);
            }
        });
    </script>
</body>
</html>
