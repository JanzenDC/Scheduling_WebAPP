<?php
include_once 'task_management/ct_js.php';
?>

<div class="p-6 bg-white shadow-md rounded-lg w-full max-w-lg mx-auto">
    <form id="task-form">
        <div class="mb-4">
            <label for="task-name" class="block text-sm font-medium text-gray-700">Task Name</label>
            <input type="text" id="task-name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea id="description" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
        </div>

        <div class="mb-4">
            <label for="task-date" class="block text-sm font-medium text-gray-700">Task Date</label>
            <input type="date" id="task-date" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div class="mb-4">
            <label for="start-time" class="block text-sm font-medium text-gray-700">Start Time</label>
            <input type="time" id="start-time" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div class="mb-4">
            <label for="end-time" class="block text-sm font-medium text-gray-700">End Time</label>
            <input type="time" id="end-time" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <button type="button" id="next-button" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md opacity-50 cursor-not-allowed">
            Next
        </button>
    </form>
    
    <div id="assign-task-section" class="mt-4 hidden">
        <label for="user-select" class="block text-sm font-medium text-gray-700">Assign Users</label>
        <select id="user-select" class="w-full select2 mt-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" multiple>
        </select>
        
        <button id="save-task-button" 
            class="w-full mt-4 bg-green-600 text-white font-semibold text-lg py-3 rounded-lg transition duration-300 ease-in-out hover:bg-green-700 active:scale-95 disabled:bg-indigo-300 disabled:cursor-not-allowed">
            Save Task
        </button>
    </div>


</div>
