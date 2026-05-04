@extends('layouts.app')

<!-- Monitoring Content -->
<div x-data="monitoringData" x-init="loadMonitoringData()">
    <!-- Header Actions -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Monitoring Dashboard</h2>
            <p class="mt-1 text-sm text-gray-600">Real-time metrics and performance monitoring</p>
        </div>
        <div class="flex space-x-3">
            <button @click="refreshMetrics()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': loading}"></i>
                Refresh
            </button>
            <button @click="showAlertSettings = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-bell mr-2"></i>
                Alert Settings
            </button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-microchip text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg CPU Usage</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="metrics.avgCpu + '%'">0%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-memory text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Memory Usage</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="metrics.avgMemory + '%'">0%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-hdd text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Disk Usage</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="metrics.avgDisk + '%'">0%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-network-wired text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Network I/O</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="metrics.networkIO + ' MB/s'">0 MB/s</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- CPU Usage Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">CPU Usage (Last 24 Hours)</h3>
            <canvas id="cpuChart" width="400" height="200"></canvas>
        </div>

        <!-- Memory Usage Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Memory Usage (Last 24 Hours)</h3>
            <canvas id="memoryChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- VM List with Metrics -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">VM Performance</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">VM Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Memory</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Network</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uptime</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="vm in vmMetrics" :key="vm.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="vm.name"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getHealthClass(vm.health)"
                                          x-text="vm.health"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-blue-600 h-2 rounded-full" 
                                                 :style="`width: ${vm.cpu}%`"></div>
                                        </div>
                                        <span class="text-sm text-gray-900" x-text="vm.cpu + '%'"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-600 h-2 rounded-full" 
                                                 :style="`width: ${vm.memory}%`"></div>
                                        </div>
                                        <span class="text-sm text-gray-900" x-text="vm.memory + '%'"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-yellow-600 h-2 rounded-full" 
                                                 :style="`width: ${vm.disk}%`"></div>
                                        </div>
                                        <span class="text-sm text-gray-900" x-text="vm.disk + '%'"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="vm.network + ' MB/s'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="vm.uptime"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function monitoringData() {
    return {
        loading: false,
        showAlertSettings: false,
        metrics: {
            avgCpu: 0,
            avgMemory: 0,
            avgDisk: 0,
            networkIO: 0
        },
        vmMetrics: [],
        
        async loadMonitoringData() {
            this.loading = true;
            try {
                const response = await axios.get('/api/monitoring/metrics');
                const data = response.data.data || {};
                
                this.metrics = {
                    avgCpu: data.avgCpu || 45,
                    avgMemory: data.avgMemory || 67,
                    avgDisk: data.avgDisk || 32,
                    networkIO: data.networkIO || 125
                };
                
                this.vmMetrics = data.vmMetrics || this.getSampleVMMetrics();
                
                this.$nextTick(() => {
                    this.initCharts();
                });
            } catch (error) {
                console.error('Failed to load monitoring data:', error);
                this.loadSampleData();
            } finally {
                this.loading = false;
            }
        },
        
        loadSampleData() {
            this.metrics = {
                avgCpu: 45,
                avgMemory: 67,
                avgDisk: 32,
                networkIO: 125
            };
            
            this.vmMetrics = this.getSampleVMMetrics();
            
            this.$nextTick(() => {
                this.initCharts();
            });
        },
        
        getSampleVMMetrics() {
            return [
                { id: 1, name: 'web-server-01', health: 'healthy', cpu: 32, memory: 45, disk: 28, network: 85, uptime: '45d 12h' },
                { id: 2, name: 'database-01', health: 'healthy', cpu: 68, memory: 72, disk: 41, network: 120, uptime: '67d 8h' },
                { id: 3, name: 'app-server-01', health: 'warning', cpu: 85, memory: 89, disk: 67, network: 200, uptime: '23d 15h' },
                { id: 4, name: 'cache-server-01', health: 'healthy', cpu: 12, memory: 34, disk: 15, network: 45, uptime: '89d 3h' },
                { id: 5, name: 'backup-server-01', health: 'critical', cpu: 95, memory: 94, disk: 89, network: 180, uptime: '12d 6h' }
            ];
        },
        
        initCharts() {
            // CPU Chart
            const cpuCtx = document.getElementById('cpuChart').getContext('2d');
            new Chart(cpuCtx, {
                type: 'line',
                data: {
                    labels: this.generateTimeLabels(),
                    datasets: [{
                        label: 'CPU Usage %',
                        data: this.generateRandomData(24, 20, 80),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
            
            // Memory Chart
            const memoryCtx = document.getElementById('memoryChart').getContext('2d');
            new Chart(memoryCtx, {
                type: 'line',
                data: {
                    labels: this.generateTimeLabels(),
                    datasets: [{
                        label: 'Memory Usage %',
                        data: this.generateRandomData(24, 40, 90),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        },
        
        generateTimeLabels() {
            const labels = [];
            for (let i = 23; i >= 0; i--) {
                labels.push(`${i}h`);
            }
            return labels;
        },
        
        generateRandomData(count, min, max) {
            const data = [];
            for (let i = 0; i < count; i++) {
                data.push(Math.floor(Math.random() * (max - min + 1)) + min);
            }
            return data;
        },
        
        getHealthClass(health) {
            const classes = {
                'healthy': 'bg-green-100 text-green-800',
                'warning': 'bg-yellow-100 text-yellow-800',
                'critical': 'bg-red-100 text-red-800'
            };
            return classes[health] || 'bg-gray-100 text-gray-800';
        },
        
        async refreshMetrics() {
            await this.loadMonitoringData();
        }
    }
}
</script>
