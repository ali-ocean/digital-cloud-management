<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Digital Cloud Management LaunchPad</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <!-- Sidebar -->
        <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 shadow-lg transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0"
             style="display: none;">
            <div class="flex items-center justify-center h-16 px-4 bg-gray-900">
                <h1 class="text-white text-lg font-semibold">Digital Cloud Management</h1>
            </div>
            <nav class="mt-5 px-2">
                <a href="#" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-cloud mr-3"></i>
                    Cloud Providers
                </a>
                <a href="#" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-server mr-3"></i>
                    Virtual Machines
                </a>
                <a href="#" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-chart-line mr-3"></i>
                    Monitoring
                </a>
                <a href="#" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-dollar-sign mr-3"></i>
                    Billing
                </a>
                <a href="#" class="group flex items-center px-2 py-2 text-base font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                    <i class="fas fa-shield-alt mr-3"></i>
                    Security
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <button @click="sidebarOpen = ! sidebarOpen" class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                                <i class="fas fa-bars"></i>
                            </button>
                            <h1 class="text-xl font-semibold text-gray-900">Digital Cloud Management LaunchPad</h1>
                        </div>
                        <div class="flex items-center space-x-4">
                            <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-plus mr-2"></i>
                                New VM
                            </button>
                            <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                <i class="fas fa-sync mr-2"></i>
                                Sync All
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i class="fas fa-cloud text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Cloud Providers</dt>
                                        <dd class="text-lg font-semibold text-gray-900">3</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i class="fas fa-server text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total VMs</dt>
                                        <dd class="text-lg font-semibold text-gray-900">12</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Alerts</dt>
                                        <dd class="text-lg font-semibold text-gray-900">5</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                    <i class="fas fa-dollar-sign text-white text-xl"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Monthly Cost</dt>
                                        <dd class="text-lg font-semibold text-gray-900">$1,234</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VMs Table -->
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Virtual Machines</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost/Month</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">web-server-01</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Running</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">GCP</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$45.00</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900">Manage</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">database-01</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Running</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">DigitalOcean</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$32.00</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900">Manage</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">app-server-01</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Stopped</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Huawei</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$28.00</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900">Manage</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Sidebar overlay for mobile -->
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 transition-opacity lg:hidden"
             @click="sidebarOpen = false"
             style="display: none;">
            <div class="absolute inset-0 bg-gray-600 opacity-75"></div>
        </div>
    </div>

    <script>
        // Initialize Alpine.js
        document.addEventListener('alpine:init', () => {
            console.log('Digital Cloud Management Dashboard loaded');
        });
    </script>
</body>
</html>
