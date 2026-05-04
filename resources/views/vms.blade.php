@extends('layouts.app')

<!-- Virtual Machines Content -->
<div x-data="vmsData" x-init="loadVms()">
    <!-- Header Actions -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Virtual Machines</h2>
            <p class="mt-1 text-sm text-gray-600">Manage your cloud virtual machines across all providers</p>
        </div>
        <div class="flex space-x-3">
            <button @click="syncAllVms()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': loading}"></i>
                Sync All
            </button>
            <button @click="showAddVmModal = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-plus mr-2"></i>
                New VM
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" x-model="filters.search" @input="filterVms()" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Search VMs...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select x-model="filters.status" @change="filterVms()" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="running">Running</option>
                    <option value="stopped">Stopped</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                <select x-model="filters.provider" @change="filterVms()" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Providers</option>
                    <template x-for="provider in providers" :key="provider.id">
                        <option :value="provider.id" x-text="provider.name"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                <select x-model="filters.region" @change="filterVms()" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Regions</option>
                    <option value="us-central1">US Central</option>
                    <option value="us-west1">US West</option>
                    <option value="europe-west1">Europe West</option>
                    <option value="asia-east1">Asia East</option>
                </select>
            </div>
        </div>
    </div>

    <!-- VMs Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" @change="toggleSelectAll" class="rounded">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instance Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost/Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="vm in filteredVms" :key="vm.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" :value="vm.id" x-model="selectedVms" class="rounded">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="vm.name"></div>
                                <div class="text-sm text-gray-500" x-text="vm.public_ip"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="getStatusClass(vm.status)"
                                      x-text="vm.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="vm.cloud_provider?.name"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="vm.instance_type"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="vm.region"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="'$' + vm.monthly_cost"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button @click="startVm(vm)" x-show="vm.status === 'stopped'" 
                                            class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <button @click="stopVm(vm)" x-show="vm.status === 'running'" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                    <button @click="restartVm(vm)" x-show="vm.status === 'running'" 
                                            class="text-yellow-600 hover:text-yellow-900">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <button @click="viewVmDetails(vm)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredVms.length === 0">
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                            No virtual machines found matching your criteria.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div x-show="selectedVms.length > 0" class="mt-4 bg-white p-4 rounded-lg shadow">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-700">
                <span x-text="selectedVms.length"></span> VMs selected
            </span>
            <div class="flex space-x-2">
                <button @click="bulkStart()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                    Start All
                </button>
                <button @click="bulkStop()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                    Stop All
                </button>
                <button @click="bulkDelete()" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm">
                    Delete All
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function vmsData() {
    return {
        vms: [],
        providers: [],
        filteredVms: [],
        selectedVms: [],
        loading: false,
        showAddVmModal: false,
        filters: {
            search: '',
            status: '',
            provider: '',
            region: ''
        },
        
        async loadVms() {
            this.loading = true;
            try {
                const [vmsResponse, providersResponse] = await Promise.all([
                    axios.get('/api/virtual-machines'),
                    axios.get('/api/cloud-providers')
                ]);
                
                this.vms = vmsResponse.data.data || [];
                this.providers = providersResponse.data.data || [];
                this.filteredVms = this.vms;
            } catch (error) {
                console.error('Failed to load VMs:', error);
                // Load sample data
                this.loadSampleData();
            } finally {
                this.loading = false;
            }
        },
        
        loadSampleData() {
            this.vms = [
                { id: 1, name: 'web-server-01', status: 'running', public_ip: '192.168.1.10', instance_type: 'e2-medium', region: 'us-central1', monthly_cost: 45.00, cloud_provider: { name: 'GCP' } },
                { id: 2, name: 'database-01', status: 'running', public_ip: '192.168.1.11', instance_type: 'db.t3.medium', region: 'nyc3', monthly_cost: 32.00, cloud_provider: { name: 'DigitalOcean' } },
                { id: 3, name: 'app-server-01', status: 'stopped', public_ip: '192.168.1.12', instance_type: 's3.large', region: 'cn-north-1', monthly_cost: 28.00, cloud_provider: { name: 'Huawei' } },
                { id: 4, name: 'cache-server-01', status: 'running', public_ip: '192.168.1.13', instance_type: 'e2-small', region: 'us-west1', monthly_cost: 22.00, cloud_provider: { name: 'GCP' } },
                { id: 5, name: 'backup-server-01', status: 'running', public_ip: '192.168.1.14', instance_type: 's2.small', region: 'ams3', monthly_cost: 18.00, cloud_provider: { name: 'DigitalOcean' } }
            ];
            this.providers = [
                { id: 1, name: 'GCP' },
                { id: 2, name: 'DigitalOcean' },
                { id: 3, name: 'Huawei' }
            ];
            this.filteredVms = this.vms;
        },
        
        filterVms() {
            this.filteredVms = this.vms.filter(vm => {
                const matchesSearch = !this.filters.search || 
                    vm.name.toLowerCase().includes(this.filters.search.toLowerCase()) ||
                    vm.public_ip?.includes(this.filters.search);
                const matchesStatus = !this.filters.status || vm.status === this.filters.status;
                const matchesProvider = !this.filters.provider || vm.cloud_provider?.name === this.filters.provider;
                const matchesRegion = !this.filters.region || vm.region === this.filters.region;
                
                return matchesSearch && matchesStatus && matchesProvider && matchesRegion;
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
        
        async startVm(vm) {
            try {
                await axios.post(`/api/virtual-machines/${vm.id}/start`);
                vm.status = 'pending';
                setTimeout(() => vm.status = 'running', 2000);
            } catch (error) {
                console.error('Failed to start VM:', error);
            }
        },
        
        async stopVm(vm) {
            try {
                await axios.post(`/api/virtual-machines/${vm.id}/stop`);
                vm.status = 'pending';
                setTimeout(() => vm.status = 'stopped', 2000);
            } catch (error) {
                console.error('Failed to stop VM:', error);
            }
        },
        
        async restartVm(vm) {
            try {
                await axios.post(`/api/virtual-machines/${vm.id}/restart`);
                vm.status = 'pending';
                setTimeout(() => vm.status = 'running', 3000);
            } catch (error) {
                console.error('Failed to restart VM:', error);
            }
        },
        
        async syncAllVms() {
            this.loading = true;
            try {
                await axios.get('/api/virtual-machines/sync');
                await this.loadVms();
            } catch (error) {
                console.error('Failed to sync VMs:', error);
            } finally {
                this.loading = false;
            }
        },
        
        toggleSelectAll() {
            if (event.target.checked) {
                this.selectedVms = this.filteredVms.map(vm => vm.id);
            } else {
                this.selectedVms = [];
            }
        },
        
        viewVmDetails(vm) {
            // Navigate to VM details or open modal
            console.log('View VM details:', vm);
        }
    }
}
</script>
