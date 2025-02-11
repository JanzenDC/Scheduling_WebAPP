<?php
include_once 'task_management/ct_js.php';
?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-lg mx-auto bg-white rounded-xl shadow-2xl overflow-hidden">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-8 py-6">
      <h2 class="text-2xl font-bold text-white">Create New Task</h2>
      <p class="text-blue-100 mt-1">Fill in the task details below</p>
    </div>

    <div class="p-8">
      <form id="task-form" class="space-y-6">
        <!-- Task Name -->
        <div class="relative">
          <label for="task-name" class="text-sm font-medium text-gray-700 block mb-2">Task Name</label>
          <input type="text" id="task-name" 
            class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-colors duration-200 outline-none"
            placeholder="Enter task name">
        </div>

        <!-- Description -->
        <div class="relative">
          <label for="description" class="text-sm font-medium text-gray-700 block mb-2">Description</label>
          <textarea id="description" rows="4" 
            class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-colors duration-200 outline-none"
            placeholder="Describe your task"></textarea>
        </div>

        <!-- Date and Time Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Task Date -->
          <div class="col-span-1">
            <label for="task-date" class="text-sm font-medium text-gray-700 block mb-2">Date</label>
            <input type="date" id="task-date" 
              class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-colors duration-200 outline-none">
          </div>

          <!-- Start Time -->
          <div class="col-span-1">
            <label for="start-time" class="text-sm font-medium text-gray-700 block mb-2">Start Time</label>
            <input type="time" id="start-time" 
              class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-colors duration-200 outline-none">
          </div>

          <!-- End Time -->
          <div class="col-span-1">
            <label for="end-time" class="text-sm font-medium text-gray-700 block mb-2">End Time</label>
            <input type="time" id="end-time" 
              class="block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-colors duration-200 outline-none">
          </div>
        </div>

        <!-- Next Button -->
        <button type="button" id="next-button" 
          class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 px-6 rounded-lg font-semibold text-lg shadow-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 transform hover:-translate-y-0.5 opacity-50 cursor-not-allowed">
          Next
        </button>
      </form>

      <!-- Assign Task Section -->
      <div id="assign-task-section" class="mt-8 hidden space-y-6">
        <div>
          <label for="user-select" class="text-sm font-medium text-gray-700 block mb-2">Assign Users</label>
          <select id="user-select" class="select2 block w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-colors duration-200 outline-none" multiple>
          </select>
        </div>

        <button id="save-task-button" 
          class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 px-6 rounded-lg font-semibold text-lg shadow-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-200 transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:transform-none">
          Save Task
        </button>
      </div>
    </div>
  </div>
</div>