@extends('layouts.app')

<!-- Security Content -->
<div x-data="securityData" x-init="loadSecurityData()">
    <!-- Header Actions -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Security Center</h2>
            <p class="mt-1 text-sm text-gray-600">Vulnerability scanning and security management</p>
        </div>
        <div class="flex space-x-3">
            <button @click="runSecurityScan()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-shield-alt mr-2"></i>
                Run Scan
            </button>
            <button @click="showScanSettings = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-cog mr-2"></i>
                Settings
            </button>
        </div>
    </div>

    <!-- Security Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Critical Vulnerabilities</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="security.criticalVulns">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <i class="fas fa-exclamation text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">High Vulnerabilities</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="security.highVulns">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">NCA Compliance</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="security.ncaCompliance + '%'">0%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Last Scan</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="security.lastScan">Never</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Scans -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Security Scans</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">VM Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scan Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Critical</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">High</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="scan in recentScans" :key="scan.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="scan.vm_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="scan.scan_type"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getScanStatusClass(scan.status)"
                                          x-text="scan.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="scan.critical_vulnerabilities"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="scan.high_vulnerabilities"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="scan.completed_at"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="viewScanDetails(scan)" class="text-blue-600 hover:text-blue-900">View</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Vulnerability Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Vulnerability Distribution</h3>
            <canvas id="vulnChart" width="400" height="300"></canvas>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Compliance Score</h3>
            <canvas id="complianceChart" width="400" height="300"></canvas>
        </div>
    </div>
</div>

<script>
function securityData() {
    return {
        security: {
            criticalVulns: 0,
            highVulns: 0,
            ncaCompliance: 0,
            lastScan: 'Never'
        },
        recentScans: [],
        
        async loadSecurityData() {
            try {
                const response = await axios.get('/api/security/scans');
                const data = response.data.data || {};
                
                this.security = data.summary || this.getSampleSecurity();
                this.recentScans = data.scans || this.getSampleScans();
                
                this.$nextTick(() => {
                    this.initCharts();
                });
            } catch (error) {
                console.error('Failed to load security data:', error);
                this.loadSampleData();
            }
        },
        
        loadSampleData() {
            this.security = this.getSampleSecurity();
            this.recentScans = this.getSampleScans();
            
            this.$nextTick(() => {
                this.initCharts();
            });
        },
        
        getSampleSecurity() {
            return {
                criticalVulns: 3,
                highVulns: 12,
                ncaCompliance: 78,
                lastScan: '2 hours ago'
            };
        },
        
        getSampleScans() {
            return [
                { id: 1, vm_name: 'web-server-01', scan_type: 'Vulnerability Scan', status: 'completed', critical_vulnerabilities: 2, high_vulnerabilities: 8, completed_at: '2026-05-03 10:30' },
                { id: 2, vm_name: 'database-01', scan_type: 'Penetration Test', status: 'completed', critical_vulnerabilities: 1, high_vulnerabilities: 4, completed_at: '2026-05-03 08:15' },
                { id: 3, vm_name: 'app-server-01', scan_type: 'Vulnerability Scan', status: 'running', critical_vulnerabilities: 0, high_vulnerabilities: 0, completed_at: 'In Progress' },
                { id: 4, vm_name: 'cache-server-01', scan_type: 'NCA Compliance', status: 'completed', critical_vulnerabilities: 0, high_vulnerabilities: 0, completed_at: '2026-05-02 16:45' },
                { id: 5, vm_name: 'backup-server-01', scan_type: 'Vulnerability Scan', status: 'failed', critical_vulnerabilities: 0, high_vulnerabilities: 0, completed_at: '2026-05-02 14:20' }
            ];
        },
        
        initCharts() {
            // Vulnerability Chart
            const vulnCtx = document.getElementById('vulnChart').getContext('2d');
            new Chart(vulnCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Critical', 'High', 'Medium', 'Low', 'Info'],
                    datasets: [{
                        data: [3, 12, 28, 45, 67],
                        backgroundColor: [
                            'rgb(239, 68, 68)',
                            'rgb(251, 191, 36)',
                            'rgb(250, 204, 21)',
                            'rgb(59, 130, 246)',
                            'rgb(156, 163, 175)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
            
            // Compliance Chart
            const complianceCtx = document.getElementById('complianceChart').getContext('2d');
            new Chart(complianceCtx, {
                type: 'radar',
                data: {
                    labels: ['Access Control', 'Encryption', 'Audit Logging', 'Network Security', 'Data Protection'],
                    datasets: [{
                        label: 'Compliance Score',
                        data: [85, 92, 78, 65, 88],
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.2)',
                        pointBackgroundColor: 'rgb(34, 197, 94)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(34, 197, 94)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        },
        
        getScanStatusClass(status) {
            const classes = {
                'completed': 'bg-green-100 text-green-800',
                'running': 'bg-blue-100 text-blue-800',
                'failed': 'bg-red-100 text-red-800',
                'pending': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        async runSecurityScan() {
            alert('Starting security scan on all VMs...');
        },
        
        viewScanDetails(scan) {
            console.log('View scan details:', scan);
        }
    }
}
</script>
