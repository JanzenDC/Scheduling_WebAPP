<?php
include_once 'task_management/n_js.php';
?>
<div class="max-w-4xl mx-auto p-6">
    <!-- Notification Area -->
    <div id="notificationArea" class="hidden"></div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-6">Create New Task</h2>
        
        <!-- Task Details -->
        <div class="space-y-6">
            <div>
                <label for="taskName" class="block text-sm font-medium text-gray-700">Name of Task</label>
                <input type="text" id="taskName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <!-- Description field -->
            <div class="mt-4">
                <label for="taskDescription" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="taskDescription" rows="3" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Enter task description here..."></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="taskDate" class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" id="taskDate" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="startTime" class="block text-sm font-medium text-gray-700">Time Start</label>
                    <input type="time" id="startTime" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="endTime" class="block text-sm font-medium text-gray-700">Time End</label>
                    <input type="time" id="endTime" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <!-- People Selection Section -->
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">People Involved</h3>
                
                <!-- Search Box -->
                <div class="relative">
                    <input type="text" id="userSearch" placeholder="Search users..." 
                           class="block w-full pl-4 pr-10 py-2 rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Available Users Grid -->
                <div id="peopleInvolved" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Users will be dynamically inserted here -->
                </div>

                <!-- Selected Users Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Selected Users</label>
                    <div id="selectedUsers" class="flex flex-wrap gap-2">
                        <!-- Selected users will be dynamically inserted here -->
                    </div>
                </div>
            </div>

            <!-- Conflicts Section -->
            <div id="conflictsArea" class="hidden">
                <!-- Conflicts will be dynamically inserted here -->
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4">
                <button id="checkConflicts" 
                        class="inline-flex items-center px-4 py-2 border border-yellow-300 rounded-md shadow-sm text-sm font-medium text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    CHECK FOR CONFLICT
                </button>

                <button id="createTask" 
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Task
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div id="loadingSpinner" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
</div>