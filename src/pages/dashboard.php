<?php 
require 'main_query.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <?php include_once '../header_cdn.php'; ?>
    <script>
        const userPermissions = <?php echo json_encode($permissions); ?>;
        console.log("User Permissions: ",userPermissions);
    </script>
</head>
<body class="">
    <div>
        <?php include_once 'navbar.php'; ?>
    </div>
    <div class="flex min-h-screen ">
        <!-- Sidebar -->
        <div id="sidebar"  class="w-64 bg-[#044389] text-white flex justify-center align-center fixed h-full transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40 tracking-wider overflow-y-auto">
            <?php include_once 'left_sidebar/sidebar_l.php'; ?>
        </div>

        <!-- Main Content -->
        <div id="main-content" class="flex-1 md:ml-64 mt-20 transition-all duration-300 ease-in-out">
            <div class="bg-white p-6 rounded-lg shadow-md h-full">
                <div class='bg-[#044389] text-white p-2 rounded-lg mb-4'>

                
                    <?php
                    // Split the current page path into parts based on '/'
                    $page_parts = explode('/', $current_page);
                    $breadcrumbs = [];

                    // Always display "Dashboard" as a clickable link
                    $breadcrumbs[] = "<a href='dashboard.php?page=dashboard' class='text-white-600 hover:text-black text-sm md:text-base'>Dashboard</a>";

                    // Add the Module Name (second part)
                    if (isset($page_parts[1])) {
                        $module_name = ucwords(str_replace('_', ' ', $page_parts[1])); // Capitalize module name
                        $breadcrumbs[] = "<span class='text-white text-sm md:text-base font-bold'>{$module_name}</span>";
                    }

                    // Add the Page Name (third part)
                    if (isset($page_parts[2])) {
                        $page_name = ucwords(str_replace('_', ' ', $page_parts[2])); // Capitalize page name
                        $breadcrumbs[] = "<span class='text-white text-sm md:text-base font-bold'>{$page_name}</span>";
                    }

                    // Display breadcrumbs as a joined string with ' / ' separators
                    echo implode(' <span class="mx-2 text-white">/</span> ', $breadcrumbs);
                    ?>
                </div>
                
                <?php 
                if ($permissions['can_view'] == 1 || $current_page === 'dashboard' || $current_page === 'dashboardmain'): ?> 
                    <?php
                    if ($current_page === 'dashboard') {
                        $include_file = 'dashboardmain.php';
                    } else {
                        $include_file = "{$current_page}.php";
                    }
                    if (file_exists($include_file)) {
                        include_once $include_file;
                    } else {
                        echo "<p class='text-red-500'>Page not found.</p>";
                    }
                    ?>
                <?php else: ?>
                    <?php include_once 'noPermission.php';?>
                <?php endif; ?>
            </div>
        </div>







    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            menuToggle?.addEventListener('click', function (e) {
                e.stopPropagation();
                sidebar.classList.toggle('-translate-x-full');
            });
            document.addEventListener('click', function (e) {
                if (window.innerWidth < 768 && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                }
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('-translate-x-full');
                } else {
                    sidebar.classList.add('-translate-x-full');
                }
            });
        });
    </script>
    <script src='globalVariable.js'></script>
</body>
</html>
