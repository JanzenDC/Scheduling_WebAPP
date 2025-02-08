<nav class="bg-white text-black p-4 fixed w-full top-0 z-30 drop-shadow-lg">
    <div class="flex justify-between items-center w-full">
        <!-- Left Section: Date -->
        <div class="flex items-center gap-4">
            <button id="menu-toggle" class="text-black md:hidden focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <a href="dashboard.php?page=dashboard" class="text-lg font-bold hidden ms-[250px] md:block">Wealth<span class='text-indigo-600'>Invest</span></a>
            <a href="dashboard.php?page=dashboard" class="text-lg font-bold md:hidden block">W<span class='text-indigo-600'>I</span></a>
        </div>

        <!-- Right Section: Date & Time -->
        <div class="text-right">
            <div id="current-date" class="text-sm"></div>
            <div id="current-time" class="text-xs mt-1"></div>
        </div>
    </div>
</nav>

<script>
    function updateDateTime() {
        const dateElement = document.getElementById('current-date');
        const timeElement = document.getElementById('current-time');

        const now = new Date();

        // Format the date as 'Day, Month Date, Year' (e.g., Mon, Jan 20, 2025)
        const dateString = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        dateElement.textContent = dateString;

        // Format the time as 'HH:mm:ss AM/PM' (e.g., 12:30:45 PM)
        const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        timeElement.textContent = timeString;
    }

    // Update every second
    setInterval(updateDateTime, 1000);

    // Initial call to set the time
    updateDateTime();
</script>
