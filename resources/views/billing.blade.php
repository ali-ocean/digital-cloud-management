@extends('layouts.app')

<!-- Billing Content -->
<div x-data="billingData" x-init="loadBillingData()">
    <!-- Header Actions -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Billing & Costs</h2>
            <p class="mt-1 text-sm text-gray-600">Track and optimize your cloud spending</p>
        </div>
        <div class="flex space-x-3">
            <button @click="generateReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-file-download mr-2"></i>
                Generate Report
            </button>
            <button @click="showCostOptimization = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-chart-line mr-2"></i>
                Cost Analysis
            </button>
        </div>
    </div>

    <!-- Cost Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">This Month</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="'$' + billing.currentMonth">$0</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-green-600 font-medium" x-text="billing.monthOverMonth > 0 ? '+$' + billing.monthOverMonth : '-$' + Math.abs(billing.monthOverMonth)">$0</span>
                    <span class="text-gray-500"> vs last month</span>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-calendar text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">YTD Total</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="'$' + billing.ytdTotal">$0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-piggy-bank text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Projected Annual</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="'$' + billing.projectedAnnual">$0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Cost Alerts</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="billing.costAlerts">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Cost Trend</h3>
            <canvas id="costTrendChart" width="400" height="300"></canvas>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Cost by Provider</h3>
            <canvas id="costByProviderChart" width="400" height="300"></canvas>
        </div>
    </div>

    <!-- Billing Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Billing Records</h3>
                <div class="flex space-x-2">
                    <select x-model="filters.period" @change="filterBilling()" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="current">Current Month</option>
                        <option value="last">Last Month</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                    </select>
                    <select x-model="filters.provider" @change="filterBilling()" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Providers</option>
                        <option value="gcp">GCP</option>
                        <option value="digitalocean">DigitalOcean</option>
                        <option value="huawei">Huawei</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="record in filteredBilling" :key="record.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="record.date"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="record.provider"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="record.service"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="record.usage"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'$' + record.amount"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="record.status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                          x-text="record.status"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function billingData() {
    return {
        billing: {
            currentMonth: 0,
            monthOverMonth: 0,
            ytdTotal: 0,
            projectedAnnual: 0,
            costAlerts: 0
        },
        billingRecords: [],
        filteredBilling: [],
        filters: {
            period: 'current',
            provider: ''
        },
        
        async loadBillingData() {
            try {
                const response = await axios.get('/api/billing');
                const data = response.data.data || {};
                
                this.billing = data.summary || this.getSampleBilling();
                this.billingRecords = data.records || this.getSampleBillingRecords();
                this.filteredBilling = this.billingRecords;
                
                this.$nextTick(() => {
                    this.initCharts();
                });
            } catch (error) {
                console.error('Failed to load billing data:', error);
                this.loadSampleData();
            }
        },
        
        loadSampleData() {
            this.billing = this.getSampleBilling();
            this.billingRecords = this.getSampleBillingRecords();
            this.filteredBilling = this.billingRecords;
            
            this.$nextTick(() => {
                this.initCharts();
            });
        },
        
        getSampleBilling() {
            return {
                currentMonth: 1234.56,
                monthOverMonth: 45.67,
                ytdTotal: 8456.78,
                projectedAnnual: 14814.72,
                costAlerts: 3
            };
        },
        
        getSampleBillingRecords() {
            return [
                { id: 1, date: '2026-05-01', provider: 'GCP', service: 'Compute Engine', usage: '245 hours', amount: 45.00, status: 'paid' },
                { id: 2, date: '2026-05-01', provider: 'DigitalOcean', service: 'Droplets', usage: '720 hours', amount: 32.00, status: 'paid' },
                { id: 3, date: '2026-05-01', provider: 'Huawei', service: 'ECS', usage: '480 hours', amount: 28.00, status: 'paid' },
                { id: 4, date: '2026-05-02', provider: 'GCP', service: 'Cloud Storage', usage: '120 GB', amount: 18.50, status: 'paid' },
                { id: 5, date: '2026-05-03', provider: 'DigitalOcean', service: 'Load Balancer', usage: '720 hours', amount: 12.00, status: 'pending' }
            ];
        },
        
        initCharts() {
            // Cost Trend Chart
            const trendCtx = document.getElementById('costTrendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                    datasets: [{
                        label: 'Monthly Cost',
                        data: [980, 1050, 1120, 1188.89, 1234.56],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
            
            // Cost by Provider Chart
            const providerCtx = document.getElementById('costByProviderChart').getContext('2d');
            new Chart(providerCtx, {
                type: 'doughnut',
                data: {
                    labels: ['GCP', 'DigitalOcean', 'Huawei'],
                    datasets: [{
                        data: [645.50, 320.00, 269.06],
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        },
        
        filterBilling() {
            this.filteredBilling = this.billingRecords.filter(record => {
                const matchesProvider = !this.filters.provider || record.provider.toLowerCase().includes(this.filters.provider.toLowerCase());
                return matchesProvider;
            });
        },
        
        generateReport() {
            alert('Generating billing report...');
        },
        
        showCostOptimization() {
            alert('Opening cost optimization analysis...');
        }
    }
}
</script>
