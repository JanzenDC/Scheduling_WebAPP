<!-- Favicon -->
<link rel="icon" href="/nexusPH/resources/images/nexusPH.ico" type="image/x-icon">

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
    @keyframes blink {
        0% { opacity: 0; }
        50% { opacity: 1; }
        100% { opacity: 0; }
    }
    .priority-label {
    margin-left: 5px;
    }

    .availability-label {
        margin-left: 5px;
    }
    .blinking-star {
        color: gold; /* Change the color to your preference */
        animation: blink 1s infinite;
    }

    .select2-result-user {
        padding: 4px;
    }
    .select2-result-user .user-name {
        color: #000;
    }
    .select2-result-user .user-position {
        font-size: 0.875rem;
        color: #666;
    }
    .select2-container {
        z-index: 99999;
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
  .dataTables_filter label input[type="search"]{
   height:30px;
   width:250px;
   border-radius: 10px;
   background:#f5f5f550;
   padding: 0 10px 2px 10px;
  }


  .dataTables_length label select{
    height:30px;
    margin-bottom:10px;
    
  }
  .dataTables_wrapper{
    padding: 20px;
    background:#fff;
    border-radius:10px;
    box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);

  }
  .dataTables_wrapper tbody{
    background:white
    
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
