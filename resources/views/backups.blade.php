@extends('layouts.app')

<!-- Backups Content -->
<div x-data="backupsData" x-init="loadBackups()">
    <!-- Header Actions -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Backup Management</h2>
            <p class="mt-1 text-sm text-gray-600">Automated backup and disaster recovery</p>
        </div>
        <div class="flex space-x-3">
            <button @click="runBackup()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-play mr-2"></i>
                Run Backup
            </button>
            <button @click="showBackupSettings = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-cog mr-2"></i>
                Settings
            </button>
        </div>
    </div>

    <!-- Backup Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <i class="fas fa-database text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Backups</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="backups.totalBackups">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Successful</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="backups.successfulBackups">0</dd>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Storage Used</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="backups.storageUsed + ' GB'">0 GB</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Last Backup</dt>
                            <dd class="text-lg font-semibold text-gray-900" x-text="backups.lastBackup">Never</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Schedule -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Backup Schedule</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-3">Daily Backups</h4>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="schedule.daily.enabled" class="rounded mr-2">
                        <span class="text-sm">Enable daily backups</span>
                    </label>
                    <div x-show="schedule.daily.enabled">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" x-model="schedule.daily.time" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            <div>
                <h4 class="text-md font-medium text-gray-700 mb-3">Weekly Backups</h4>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="schedule.weekly.enabled" class="rounded mr-2">
                        <span class="text-sm">Enable weekly backups</span>
                    </label>
                    <div x-show="schedule.weekly.enabled">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                        <select x-model="schedule.weekly.day" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="sunday">Sunday</option>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Backup History</h3>
                <div class="flex space-x-2">
                    <select x-model="filters.status" @change="filterBackups()" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="running">Running</option>
                        <option value="failed">Failed</option>
                    </select>
                    <select x-model="filters.provider" @change="filterBackups()" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Providers</option>
                        <option value="aws">AWS S3</option>
                        <option value="gcp">Google Cloud Storage</option>
                        <option value="azure">Azure Blob</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">VM</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="backup in filteredBackups" :key="backup.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="backup.name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="backup.virtual_machine?.name"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="backup.backup_type"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="backup.size_gb + ' GB'"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getBackupStatusClass(backup.status)"
                                          x-text="backup.status"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="backup.created_at"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="restoreBackup(backup)" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                        <button @click="downloadBackup(backup)" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-download"></i> Download
                                        </button>
                                        <button @click="deleteBackup(backup)" 
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
function backupsData() {
    return {
        backups: {
            totalBackups: 0,
            successfulBackups: 0,
            storageUsed: 0,
            lastBackup: 'Never'
        },
        backupList: [],
        filteredBackups: [],
        filters: {
            status: '',
            provider: ''
        },
        schedule: {
            daily: { enabled: true, time: '02:00' },
            weekly: { enabled: true, day: 'sunday' }
        },
        showBackupSettings: false,
        
        async loadBackups() {
            try {
                const response = await axios.get('/api/backups');
                const data = response.data.data || {};
                
                this.backups = data.summary || this.getSampleBackups();
                this.backupList = data.backups || this.getSampleBackupList();
                this.filteredBackups = this.backupList;
            } catch (error) {
                console.error('Failed to load backups:', error);
                this.loadSampleData();
            }
        },
        
        loadSampleData() {
            this.backups = this.getSampleBackups();
            this.backupList = this.getSampleBackupList();
            this.filteredBackups = this.backupList;
        },
        
        getSampleBackups() {
            return {
                totalBackups: 156,
                successfulBackups: 148,
                storageUsed: 892.5,
                lastBackup: '2 hours ago'
            };
        },
        
        getSampleBackupList() {
            return [
                { id: 1, name: 'web-server-01-daily', backup_type: 'Daily', size_gb: 12.5, status: 'completed', created_at: '2026-05-03 02:00', virtual_machine: { name: 'web-server-01' } },
                { id: 2, name: 'database-01-weekly', backup_type: 'Weekly', size_gb: 45.2, status: 'completed', created_at: '2026-05-02 02:00', virtual_machine: { name: 'database-01' } },
                { id: 3, name: 'app-server-01-manual', backup_type: 'Manual', size_gb: 28.7, status: 'running', created_at: '2026-05-03 10:30', virtual_machine: { name: 'app-server-01' } },
                { id: 4, name: 'cache-server-01-daily', backup_type: 'Daily', size_gb: 8.3, status: 'failed', created_at: '2026-05-03 02:00', virtual_machine: { name: 'cache-server-01' } },
                { id: 5, name: 'backup-server-01-weekly', backup_type: 'Weekly', size_gb: 156.8, status: 'completed', created_at: '2026-05-02 02:00', virtual_machine: { name: 'backup-server-01' } }
            ];
        },
        
        getBackupStatusClass(status) {
            const classes = {
                'completed': 'bg-green-100 text-green-800',
                'running': 'bg-blue-100 text-blue-800',
                'failed': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        filterBackups() {
            this.filteredBackups = this.backupList.filter(backup => {
                const matchesStatus = !this.filters.status || backup.status === this.filters.status;
                return matchesStatus;
            });
        },
        
        async runBackup() {
            alert('Starting backup on all VMs...');
        },
        
        restoreBackup(backup) {
            if (confirm(`Are you sure you want to restore from ${backup.name}?`)) {
                alert(`Initiating restore from ${backup.name}...`);
            }
        },
        
        downloadBackup(backup) {
            alert(`Downloading ${backup.name}...`);
        },
        
        deleteBackup(backup) {
            if (confirm(`Are you sure you want to delete ${backup.name}?`)) {
                this.backupList = this.backupList.filter(b => b.id !== backup.id);
                this.filterBackups();
            }
        }
    }
}
</script>
