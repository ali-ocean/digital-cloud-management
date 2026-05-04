<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Digital Cloud Management LaunchPad</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
        .fade-enter-from, .fade-leave-to { opacity: 0; }
        .slide-enter-active, .slide-leave-active { transition: transform 0.3s; }
        .slide-enter-from { transform: translateX(-100%); }
        .slide-leave-to { transform: translateX(100%); }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased" x-data="{
    sidebarOpen: false,
    currentPage: 'dashboard',
    loading: false,
    notifications: [],
    user: null
}" x-init="initApp()">
    
    <!-- Loading Screen -->
    <div x-show="loading" x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-xl">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-gray-700">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 shadow-lg transform -translate-x-full lg:translate-x-0 lg:static lg:inset-0"
             style="display: none;">
            <div class="flex items-center justify-center h-16 bg-gray-800">
                <h1 class="text-white text-lg font-semibold">
                    <i class="fas fa-cloud mr-2"></i>
                    Digital Cloud Management
                </h1>
            </div>
            
            <!-- Navigation -->
            <nav class="mt-5 px-2">
                <div class="space-y-1">
                    <a href="#" @click="currentPage = 'dashboard'" 
                       :class="currentPage === 'dashboard' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>
                    <a href="#" @click="currentPage = 'providers'"
                       :class="currentPage === 'providers' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-cloud mr-3"></i>
                        Cloud Providers
                    </a>
                    <a href="#" @click="currentPage = 'vms'"
                       :class="currentPage === 'vms' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-server mr-3"></i>
                        Virtual Machines
                    </a>
                    <a href="#" @click="currentPage = 'monitoring'"
                       :class="currentPage === 'monitoring' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-chart-line mr-3"></i>
                        Monitoring
                    </a>
                    <a href="#" @click="currentPage = 'billing'"
                       :class="currentPage === 'billing' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-dollar-sign mr-3"></i>
                        Billing
                    </a>
                    <a href="#" @click="currentPage = 'security'"
                       :class="currentPage === 'security' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-shield-alt mr-3"></i>
                        Security
                    </a>
                    <a href="#" @click="currentPage = 'backups'"
                       :class="currentPage === 'backups' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-database mr-3"></i>
                        Backups
                    </a>
                    <a href="#" @click="currentPage = 'alerts'"
                       :class="currentPage === 'alerts' ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'"
                       class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-bell mr-3"></i>
                        Alerts
                        <span x-show="notifications.length > 0" 
                              class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1"
                              x-text="notifications.length"></span>
                    </a>
                </div>
                
                <!-- User Section -->
                <div class="absolute bottom-0 w-full p-4 border-t border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">Admin User</p>
                            <p class="text-xs text-gray-400">admin@digitalcloud.com</p>
                        </div>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <button @click="sidebarOpen = ! sidebarOpen" 
                                    class="lg:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                                <i class="fas fa-bars"></i>
                            </button>
                            <h2 class="ml-4 text-xl font-semibold text-gray-900" x-text="getPageTitle()"></h2>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Sync Button -->
                            <button @click="syncAllData()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center">
                                <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': loading}"></i>
                                Sync All
                            </button>
                            <!-- Notifications -->
                            <div class="relative">
                                <button @click="toggleNotifications()" 
                                        class="p-2 rounded-full text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-bell"></i>
                                    <span x-show="notifications.length > 0" 
                                          class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <!-- Dashboard Page -->
                <div x-show="currentPage === 'dashboard'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('dashboard')
                </div>

                <!-- Cloud Providers Page -->
                <div x-show="currentPage === 'providers'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('providers')
                </div>

                <!-- Virtual Machines Page -->
                <div x-show="currentPage === 'vms'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('vms')
                </div>

                <!-- Monitoring Page -->
                <div x-show="currentPage === 'monitoring'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('monitoring')
                </div>

                <!-- Billing Page -->
                <div x-show="currentPage === 'billing'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('billing')
                </div>

                <!-- Security Page -->
                <div x-show="currentPage === 'security'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('security')
                </div>

                <!-- Backups Page -->
                <div x-show="currentPage === 'backups'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('backups')
                </div>

                <!-- Alerts Page -->
                <div x-show="currentPage === 'alerts'" x-transition:enter="fade-enter-active" x-transition:leave="fade-leave-active">
                    @include('alerts')
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

    <!-- JavaScript -->
    <script>
        function initApp() {
            console.log('Digital Cloud Management Console initialized');
            // Initialize with real data
            loadDashboardData();
        }

        function getPageTitle() {
            const titles = {
                'dashboard': 'Dashboard',
                'providers': 'Cloud Providers',
                'vms': 'Virtual Machines',
                'monitoring': 'Monitoring',
                'billing': 'Billing & Costs',
                'security': 'Security',
                'backups': 'Backup Management',
                'alerts': 'Alerts & Notifications'
            };
            return titles[window.Alpine.store('app').currentPage] || 'Dashboard';
        }

        async function syncAllData() {
            window.Alpine.store('app').loading = true;
            try {
                // Sync all cloud providers
                await axios.get('/api/cloud-providers/sync');
                // Sync VMs
                await axios.get('/api/virtual-machines/sync');
                // Refresh dashboard data
                await loadDashboardData();
            } catch (error) {
                console.error('Sync failed:', error);
            } finally {
                window.Alpine.store('app').loading = false;
            }
        }

        async function loadDashboardData() {
            try {
                const response = await axios.get('/api/dashboard/stats');
                // Update dashboard with real data
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        }

        function toggleNotifications() {
            // Toggle notifications dropdown
        }
    </script>
</body>
</html>
