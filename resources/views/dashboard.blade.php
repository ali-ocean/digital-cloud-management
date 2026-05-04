@extends('layouts.app')
<div x-data="dashboardData" x-init="loadDashboardData()">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Cloud Providers -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-cloud text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Cloud Providers</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="stats.cloudProviders || '0'">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-green-600 font-medium" x-text="stats.activeProviders || '0'">0</span>
                    <span class="text-gray-500"> active</span>
                </div>
            </div>
        </div>

        <!-- Total VMs -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-server text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total VMs</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="stats.totalVms || '0'">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-green-600 font-medium" x-text="stats.runningVms || '0'">0</span>
                    <span class="text-gray-500"> running</span>
                </div>
            </div>
        </div>

        <!-- Active Alerts -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Alerts</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="stats.activeAlerts || '0'">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-red-600 font-medium" x-text="stats.criticalAlerts || '0'">0</span>
                    <span class="text-gray-500"> critical</span>
                </div>
            </div>
        </div>

        <!-- Monthly Cost -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Monthly Cost</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="'$' + (stats.monthlyCost || '0')">$0</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-green-600 font-medium" x-text="stats.costSavings || '0'">$0</span>
                    <span class="text-gray-500"> savings</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Cost Trend Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Cost Trend</h3>
            <canvas id="costChart" width="400" height="200"></canvas>
        </div>

        <!-- VM Status Distribution -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">VM Status Distribution</h3>
            <canvas id="vmStatusChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Recent VMs Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Virtual Machines</h3>
                <button @click="window.Alpine.store('app').currentPage = 'vms'" 
                        class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost/Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="vm in recentVms" :key="vm.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="vm.name"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getStatusClass(vm.status)"
                                          x-text="vm.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="vm.provider"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="vm.region"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="'$' + vm.monthly_cost"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="manageVm(vm)" class="text-blue-600 hover:text-blue-900">Manage</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="recentVms.length === 0">
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                No virtual machines found. <button @click="window.Alpine.store('app').currentPage = 'providers'" class="text-blue-600 hover:text-blue-900">Add a cloud provider</button> to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardData() {
    return {
        stats: {
            cloudProviders: 0,
            activeProviders: 0,
            totalVms: 0,
            runningVms: 0,
            activeAlerts: 0,
            criticalAlerts: 0,
            monthlyCost: 0,
            costSavings: 0
        },
        recentVms: [],
        
        async loadDashboardData() {
            try {
                // Load real data from API
                const [providersResponse, vmsResponse, alertsResponse] = await Promise.all([
                    axios.get('/api/cloud-providers'),
                    axios.get('/api/virtual-machines'),
                    axios.get('/api/alerts')
                ]);
                
                // Update stats
                const providers = providersResponse.data.data || [];
                const vms = vmsResponse.data.data || [];
                const alerts = alertsResponse.data.data || [];
                
                this.stats.cloudProviders = providers.length;
                this.stats.activeProviders = providers.filter(p => p.is_active).length;
                this.stats.totalVms = vms.length;
                this.stats.runningVms = vms.filter(vm => vm.status === 'running').length;
                this.stats.activeAlerts = alerts.filter(a => a.status === 'active').length;
                this.stats.criticalAlerts = alerts.filter(a => a.category === 'critical').length;
                this.stats.monthlyCost = vms.reduce((sum, vm) => sum + (parseFloat(vm.monthly_cost) || 0), 0).toFixed(2);
                
                // Recent VMs (last 5)
                this.recentVms = vms.slice(0, 5).map(vm => ({
                    ...vm,
                    provider: vm.cloud_provider?.name || 'Unknown',
                    region: vm.region || 'Unknown'
                }));
                
                // Initialize charts after data loads
                this.$nextTick(() => {
                    this.initCharts();
                });
                
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
                // Load sample data for demonstration
                this.loadSampleData();
            }
        },
        
        loadSampleData() {
            this.stats = {
                cloudProviders: 3,
                activeProviders: 3,
                totalVms: 12,
                runningVms: 10,
                activeAlerts: 5,
                criticalAlerts: 2,
                monthlyCost: 1234.56,
                costSavings: 156.78
            };
            
            this.recentVms = [
                { id: 1, name: 'web-server-01', status: 'running', provider: 'GCP', region: 'us-central1', monthly_cost: 45.00 },
                { id: 2, name: 'database-01', status: 'running', provider: 'DigitalOcean', region: 'nyc3', monthly_cost: 32.00 },
                { id: 3, name: 'app-server-01', status: 'stopped', provider: 'Huawei', region: 'cn-north-1', monthly_cost: 28.00 },
                { id: 4, name: 'cache-server-01', status: 'running', provider: 'GCP', region: 'us-west1', monthly_cost: 22.00 },
                { id: 5, name: 'backup-server-01', status: 'running', provider: 'DigitalOcean', region: 'ams3', monthly_cost: 18.00 }
            ];
            
            this.$nextTick(() => {
                this.initCharts();
            });
        },
        
        initCharts() {
            // Cost Trend Chart
            const costCtx = document.getElementById('costChart').getContext('2d');
            new Chart(costCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Monthly Cost',
                        data: [980, 1050, 1120, 1180, 1234, 1250],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            
            // VM Status Chart
            const vmStatusCtx = document.getElementById('vmStatusChart').getContext('2d');
            new Chart(vmStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Running', 'Stopped', 'Pending'],
                    datasets: [{
                        data: [this.stats.runningVms, this.stats.totalVms - this.stats.runningVms, 0],
                        backgroundColor: [
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)',
                            'rgb(251, 191, 36)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        },
        
        getStatusClass(status) {
            const classes = {
                'running': 'bg-green-100 text-green-800',
                'stopped': 'bg-red-100 text-red-800',
                'pending': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        manageVm(vm) {
            // Navigate to VM management
            window.Alpine.store('app').currentPage = 'vms';
            // Could also open modal or navigate to specific VM
        }
    }
}
</script>
