<!-- Favicon -->
<link rel="icon" href="/nexusPH/resources/images/company_logo.png" type="image/png">

<!-- Core Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.2/axios.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>

<!-- jQuery and jQuery UI -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" rel="stylesheet">

<!-- Select2 CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet">

<!-- Utility Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<!-- UI Components -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css" rel="stylesheet">

<!-- Toastr CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- DataTables -->
<link href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>

<!-- DataTables Buttons -->
<link href="https://cdn.datatables.net/buttons/2.3.0/css/buttons.dataTables.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/buttons/2.3.0/js/dataTables.buttons.min.js"></script>

<!-- DataTables Dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

<!-- FullCalendar -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.min.js"></script>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Bootstrap -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<!-- Shepherd.js -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/shepherd.js/8.0.1/css/shepherd.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/shepherd.js/8.0.1/js/shepherd.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>


<style>
    /* Style the dropdown container */
    .select2-container--default .select2-selection--single {
        height: 40px;
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 5px 10px;
    }

    /* Style selected option */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: 14px;
        font-weight: bold;
        color: #333;
    }

    /* Style dropdown options */
    .select2-results__option {
        font-size: 14px;
        padding: 10px;
    }

    /* Highlight user role with a different color */
    .select2-results__option .user-role {
        color: #666;
        font-size: 12px;
        font-style: italic;
        display: block;
        margin-top: 2px;
    }

    body {
        display: grid;
        grid-template-areas:
            " #sidebar #navbar"
            "sidebar main-content";
    }
    #navbar {
        grid-area: navbar;
    }
    #sidebar {
        grid-area: sidebar;
    }
    #main-content {
        grid-area: main-content;
    }   
    .tooltip {
        position: absolute;
        background-color: rgba(0, 0, 0, 0.75);
        color: #fff;
        padding: 5px;
        border-radius: 4px;
        font-size: 12px;
        pointer-events: none;
        visibility: hidden;
        z-index: 9999;
    }
    .tooltip-top {
        transform: translateY(-100%);
    }   
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
    /* Custom jQuery UI Dialog styles */
    .ui-dialog {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    .ui-dialog-titlebar {
        background-color: #044389;
        color: white;
        font-size: 18px;
        font-weight: bold;
    }
    .ui-dialog-title {
        color: white;
    }
    .ui-dialog-content {
        padding: 20px;
        font-size: 16px;
        color: #333;
    }
    .ui-dialog-buttonpane {
        text-align: center;
        padding: 10px;
    }
    .ui-dialog-buttonpane button {
        background-color: #044389;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 14px;
        cursor: pointer;
    }
/* Modern DataTable Container */
.dataTables_wrapper {
    @apply p-6 bg-white rounded-xl shadow-lg;
    margin: 2rem 0;
}

/* Search Input Styling */
.dataTables_filter label input[type="search"] {
    @apply w-64 h-10 px-4 py-2 rounded-lg border-2 border-gray-200 
    focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 
    transition-colors duration-200 outline-none bg-gray-50;
}

/* Length Select Styling */
.dataTables_length label select {
    @apply h-10 px-3 py-2 mx-2 rounded-lg border-2 border-gray-200 
    focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 
    transition-colors duration-200 outline-none bg-gray-50;
}

/* Table Header */
.dataTable thead th {
    @apply px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold 
    text-left border-b-0;
}

/* Table Body */
.dataTable tbody td {
    @apply px-6 py-4 border-b border-gray-100 text-gray-700;
}

/* Striped Rows */
.dataTable tbody tr:nth-child(even) {
    @apply bg-gray-50;
}

/* Hover Effect */
.dataTable tbody tr:hover {
    @apply bg-blue-50 transition-colors duration-150;
}

/* Pagination Buttons */
.dataTables_paginate .paginate_button {
    @apply px-4 py-2 mx-1 rounded-lg border border-gray-200 text-gray-700 
    hover:bg-blue-500 hover:text-white hover:border-blue-500 
    transition-colors duration-200;
}

.dataTables_paginate .paginate_button.current {
    @apply bg-blue-500 text-white border-blue-500;
}

/* Info Text */
.dataTables_info {
    @apply text-gray-600 mt-4;
}

/* Processing Display */
.dataTables_processing {
    @apply bg-white bg-opacity-90 px-4 py-2 rounded-lg shadow-md;
}

/* Responsive Table */
@media (max-width: 768px) {
    .dataTables_wrapper {
        @apply px-4;
    }
    
    .dataTables_filter label input[type="search"] {
        @apply w-full max-w-xs;
    }
    
    .dataTables_length label select {
        @apply w-24;
    }
}
 
/* //ANCHOR - Adjust the display */

  #tabs {
    font-family: Arial, sans-serif;
    margin: 20px 0;
}

#tabs ul {
    padding: 0;
    list-style-type: none;
    /* display: flex; */
    /* justify-content: space-around; */
    border-bottom: 2px solid #ddd;
    margin: 0;
}

#tabs ul li {
    margin: 0;
}

#tabs ul li a {
    display: block;
    padding: 10px 20px;
    color: #333;
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    border-radius: 5px 5px 0 0;
    background-color: #f7f7f7;
    transition: background-color 0.3s ease, color 0.3s ease;
}

#tabs ul li a:hover {
    background-color: #5044e4;
    color: white;
}

#tabs .ui-tabs-active a {
    background-color: #5044e4;
    color: white;
    border-bottom: 2px solid #5044e4;
}

#tabs div {
    display: none;
    padding: 20px;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 5px 5px;
    background-color: #fff;
}

.tab-link {
    @apply block px-4 py-2 text-gray-600 border-b-2 border-transparent transition duration-300 hover:text-blue-600 hover:border-blue-600 cursor-pointer;
}

.active-tab {
    @apply text-white bg-blue-600 border-blue-600 font-semibold rounded-t-lg;
}

.tab-content {
    @apply p-4 bg-gray-100 border border-gray-300 rounded-md mt-4;
}


</style>
