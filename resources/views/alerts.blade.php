@extends('layouts.app')

<!-- Alerts Content -->
<div x-data="alertsData" x-init="loadAlerts()">
    <!-- Header Actions -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Alerts & Notifications</h2>
            <p class="mt-1 text-sm text-gray-600">Manage system alerts and notification channels</p>
        </div>
        <div class="flex space-x-3">
            <button @click="markAllAsRead()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-check-double mr-2"></i>
                Mark All Read
            </button>
            <button @click="showAlertSettings = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-cog mr-2"></i>
                Settings
            </button>
        </div>
    </div>

    <!-- Alert Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Critical Alerts</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="alerts.criticalAlerts">0</dd>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Warning Alerts</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="alerts.warningAlerts">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-info-circle text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Info Alerts</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="alerts.infoAlerts">0</dd>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Unread</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="alerts.unreadCount">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Channels -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Channels</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-md font-medium text-gray-900">Email</h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="channels.email.enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <input type="email" x-model="channels.email.address" placeholder="admin@example.com" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-md font-medium text-gray-900">Slack</h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="channels.slack.enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <input type="text" x-model="channels.slack.webhook" placeholder="https://hooks.slack.com/..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-md font-medium text-gray-900">WhatsApp</h4>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="channels.whatsapp.enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <input type="text" x-model="channels.whatsapp.number" placeholder="+1234567890" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Alerts Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Alerts</h3>
                <div class="flex space-x-2">
                    <select x-model="filters.severity" @change="filterAlerts()" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Severities</option>
                        <option value="critical">Critical</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                    </select>
                    <select x-model="filters.status" @change="filterAlerts()" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">VM</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="alert in filteredAlerts" :key="alert.id">
                            <tr :class="alert.status === 'active' ? 'bg-red-50' : ''">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="alert.title"></div>
                                    <div class="text-sm text-gray-500" x-text="alert.message"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getSeverityClass(alert.severity)"
                                          x-text="alert.severity"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="alert.category"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="alert.virtual_machine?.name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="alert.created_at"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="alert.status === 'active' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                          x-text="alert.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="acknowledgeAlert(alert)" x-show="alert.status === 'active'" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-check"></i> Ack
                                        </button>
                                        <button @click="resolveAlert(alert)" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-check-circle"></i> Resolve
                                        </button>
                                        <button @click="deleteAlert(alert)" 
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
function alertsData() {
    return {
        alerts: {
            criticalAlerts: 0,
            warningAlerts: 0,
            infoAlerts: 0,
            unreadCount: 0
        },
        alertList: [],
        filteredAlerts: [],
        filters: {
            severity: '',
            status: ''
        },
        showAlertSettings: false,
        channels: {
            email: { enabled: true, address: 'admin@digitalcloud.com' },
            slack: { enabled: false, webhook: '' },
            whatsapp: { enabled: false, number: '' }
        },
        
        async loadAlerts() {
            try {
                const response = await axios.get('/api/alerts');
                const data = response.data.data || {};
                
                this.alerts = data.summary || this.getSampleAlerts();
                this.alertList = data.alerts || this.getSampleAlertList();
                this.filteredAlerts = this.alertList;
            } catch (error) {
                console.error('Failed to load alerts:', error);
                this.loadSampleData();
            }
        },
        
        loadSampleData() {
            this.alerts = this.getSampleAlerts();
            this.alertList = this.getSampleAlertList();
            this.filteredAlerts = this.alertList;
        },
        
        getSampleAlerts() {
            return {
                criticalAlerts: 3,
                warningAlerts: 8,
                infoAlerts: 15,
                unreadCount: 12
            };
        },
        
        getSampleAlertList() {
            return [
                { id: 1, title: 'VM CPU Usage High', message: 'web-server-01 CPU usage above 90% for 5 minutes', severity: 'critical', category: 'performance', status: 'active', created_at: '2026-05-03 10:30', virtual_machine: { name: 'web-server-01' } },
                { id: 2, title: 'Disk Space Low', message: 'database-01 disk usage at 85%', severity: 'warning', category: 'storage', status: 'active', created_at: '2026-05-03 09:45', virtual_machine: { name: 'database-01' } },
                { id: 3, title: 'Backup Completed', message: 'Weekly backup for app-server-01 completed successfully', severity: 'info', category: 'backup', status: 'resolved', created_at: '2026-05-03 02:00', virtual_machine: { name: 'app-server-01' } },
                { id: 4, title: 'Memory Usage Warning', message: 'cache-server-01 memory usage above 80%', severity: 'warning', category: 'performance', status: 'active', created_at: '2026-05-03 08:15', virtual_machine: { name: 'cache-server-01' } },
                { id: 5, title: 'Network Latency', message: 'backup-server-01 experiencing high network latency', severity: 'critical', category: 'network', status: 'active', created_at: '2026-05-03 11:20', virtual_machine: { name: 'backup-server-01' } }
            ];
        },
        
        getSeverityClass(severity) {
            const classes = {
                'critical': 'bg-red-100 text-red-800',
                'warning': 'bg-yellow-100 text-yellow-800',
                'info': 'bg-blue-100 text-blue-800'
            };
            return classes[severity] || 'bg-gray-100 text-gray-800';
        },
        
        filterAlerts() {
            this.filteredAlerts = this.alertList.filter(alert => {
                const matchesSeverity = !this.filters.severity || alert.severity === this.filters.severity;
                const matchesStatus = !this.filters.status || alert.status === this.filters.status;
                return matchesSeverity && matchesStatus;
            });
        },
        
        async acknowledgeAlert(alert) {
            try {
                await axios.post(`/api/alerts/${alert.id}/acknowledge`);
                alert.status = 'acknowledged';
            } catch (error) {
                console.error('Failed to acknowledge alert:', error);
            }
        },
        
        async resolveAlert(alert) {
            try {
                await axios.post(`/api/alerts/${alert.id}/resolve`);
                alert.status = 'resolved';
            } catch (error) {
                console.error('Failed to resolve alert:', error);
            }
        },
        
        async deleteAlert(alert) {
            if (confirm('Are you sure you want to delete this alert?')) {
                try {
                    await axios.delete(`/api/alerts/${alert.id}`);
                    this.alertList = this.alertList.filter(a => a.id !== alert.id);
                    this.filterAlerts();
                } catch (error) {
                    console.error('Failed to delete alert:', error);
                }
            }
        },
        
        async markAllAsRead() {
            try {
                await axios.post('/api/alerts/mark-all-read');
                this.alertList.forEach(alert => {
                    if (alert.status === 'active') {
                        alert.status = 'acknowledged';
                    }
                });
            } catch (error) {
                console.error('Failed to mark all as read:', error);
            }
        }
    }
}
</script>
