<style>

    /* Custom CSS for smooth transitions */
    .sidebar-submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }

    .sidebar-submenu.active {
        max-height: 500px;
        transition: max-height 0.3s ease-in;
        
    }

    .rotate-90 {
        transform: rotate(90deg);
    }
</style>

<ul class="flex flex-col text-sm font-sans  ">
    <div class='flex justify-center items-center'>
        <image src="../../resources/images/company_logo.png" class='w-[150px] p-3'></image>
    </div>

    <li class="py-1 justify-center mt-2 hover:bg-[#3cc5dd] rounded-md">
        <a href="dashboard.php?page=dashboard" class="font-semibold flex ml-4  py-2  rounded-md hover:text-white items-center">
            <i class="fa-solid fa-house mr-3"></i> Dashboard
        </a>
    </li>

    <?php
    $modules = getModulesAndPages($conn, $userID);
    
    foreach ($modules as $module) {
        if (!empty($module['pages'])) {
            ?>
            <li class="p-3 ">
                <div class="treeview-parent">
                    <div class="flex items-center justify-between cursor-pointer">
                        <div class="flex items-center">
                            <i class="fas fa-caret-right mr-3 transition-transform duration-200 "></i>
                            <?php if (!empty($module['icon'])) { ?>
                                <i class="<?php echo htmlspecialchars($module['icon']); ?> mr-3"></i>
                            <?php } ?>
                            <span class="text-white font-semibold"><?php echo htmlspecialchars($module['module_name']); ?></span>
                        </div>
                    </div>
                </div>
                <ul class="pl-6 mt-3 space-y-3">
                    <?php foreach ($module['pages'] as $page) { ?>
                        <li class="py-3  hover:bg-[#3cc5dd] rounded-md">
                            <a href="dashboard.php?page=<?php echo htmlspecialchars($module['module_alias'] . '/' . $page['page_alias']); ?>" class="flex font-semibold  items-center  px-3 hover:text-white">
                                <?php if (!empty($page['icon'])) { ?>
                                    <i class="<?php echo htmlspecialchars($page['icon']); ?> mr-3"></i>
                                <?php } ?>
                                <?php echo htmlspecialchars($page['page_name']); ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </li>
        <?php }
    } ?>
    
    <li id="moduleManagement" class="p-3" style="display: none;">
        <div class="treeview-parent">
            <div class="flex items-center justify-between cursor-pointer">
                <div class="flex items-center">
                    <i class="fas fa-caret-right mr-3 transition-transform duration-200"></i>
                    <i class="fas fa-cogs mr-3"></i>
                    <span class="text-white">Module Management</span>
                </div>
            </div>
        </div>
        <ul class="pl-6 mt-3 space-y-3">
            <li class="py-3  hover:bg-[#3cc5dd] rounded-md">
                <a href="dashboard.php?page=module_management/module_management_main" class="flex items-center text-white hover:text-white font-semibold">
                    <i class="fas fa-plus mx-3"></i> Add Module
                </a>
            </li>
            <li class="py-3  hover:bg-[#3cc5dd] rounded-md">
                <a href="dashboard.php?page=page_management/page_management" class="flex items-center text-white hover:text-white font-semibold">
                    <i class="fas fa-plus mx-3"></i> Add Pages
                </a>
            </li>
        </ul>
    </li>
    
    <li class="p-3  hover:bg-[#3cc5dd]">
        <a href="javascript:void(0);" onclick="logoutUser();" class="flex items-center text-white hover:text-white">
            <i class="fas fa-sign-out-alt mr-3"></i> Logout
        </a>
    </li>
</ul>

<script>
// Check dev mode state on page load
document.addEventListener('DOMContentLoaded', function() {
    const devMode = sessionStorage.getItem('devMode');
    if (devMode === 'true') {
        const moduleManagement = document.getElementById('moduleManagement');
        if (moduleManagement) {
            moduleManagement.style.display = 'block';
        }
    }
});

function devModeOn() {
    const moduleManagement = document.getElementById('moduleManagement');
    if (moduleManagement) {
        moduleManagement.style.display = 'block';
        sessionStorage.setItem('devMode', 'true');
    }
    console.log('Developer mode activated');
}

function devModeOff() {
    const moduleManagement = document.getElementById('moduleManagement');
    if (moduleManagement) {
        moduleManagement.style.display = 'none';
        sessionStorage.setItem('devMode', 'false');
    }
    console.log('Developer mode deactivated');
}

// Clear dev mode when logging out
const originalLogoutUser = window.logoutUser || function() {};
window.logoutUser = function() {
    sessionStorage.removeItem('devMode');
    originalLogoutUser();
};
</script>