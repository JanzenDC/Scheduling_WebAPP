<?php

include_once 'module_management/n_js.php';

?>
<div class="flex items-center justify-end mb-4">
    <span class="text-sm text-gray-600 mr-2">Tutorial Mode</span>
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" id="tutorialToggle" class="sr-only peer">
        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
    </label>
</div>
<!-- Note Section: Informing about Module Management -->
<div class="bg-gray-200 p-3 rounded-md text-sm text-gray-700 mb-4">
    <strong>Module Management Note:</strong> 
    Only developers can create modules and add pages. Please contact your administrator for assistance if you need access to this feature.
</div>

<div class='flex justify-between items-center'>
    <!-- Add Module Button -->
    <div onclick='AddModule()' class='text-white bg-green-600 p-1 rounded-md cursor-pointer hover:bg-[#044389] transition duration-200 text-xs flex items-center justify-center w-32'>
        <i class="fa-solid fa-plus mr-1 text-sm"></i> 
        <span class="text-sm">Add Module</span>
    </div>

    <!-- Fetch Modules Button -->
    <div onclick='FetchModules()' class='text-white bg-blue-600 p-1 rounded-md cursor-pointer hover:bg-blue-700 transition duration-200 text-xs flex items-center justify-center w-32'>
        <i class="fa-solid fa-download mr-1 text-sm"></i> 
        <span class="text-sm">Fetch Modules</span>
    </div>
</div>


<!-- Fetch the pages that are saved here -->
<div class='mt-3'> 
    <div id='modulesList'></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Example Shepherd steps configuration
        const steps = [
            {
                id: 'step-1',
                text: 'This is the tutorial toggle. Use it to enable or disable the tour.',
                attachTo: { element: '#tutorialToggle', on: 'bottom' },
                buttons: [
                    {
                        text: 'Next',
                        action: () => ShepherdTour.tour.next()
                    }
                ]
            },
            {
                id: 'step-2',
                text: 'Click here to add a new module.',
                attachTo: { element: '.bg-green-600', on: 'top' },
                buttons: [
                    {
                        text: 'Back',
                        action: () => ShepherdTour.tour.back()
                    },
                    {
                        text: 'Finish',
                        action: () => ShepherdTour.tour.complete()
                    }
                ]
            }
        ];

        // Initialize Shepherd with custom steps
        ShepherdTour.init(steps);
    });
</script>
