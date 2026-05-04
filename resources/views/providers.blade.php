@extends('layouts.app')

<!-- Cloud Providers Content -->
<div x-data="providersData" x-init="loadProviders()">
    <!-- Header Actions -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Cloud Providers</h2>
            <p class="mt-1 text-sm text-gray-600">Manage your cloud provider connections and credentials</p>
        </div>
        <div class="flex space-x-3">
            <button @click="testAllConnections()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-plug mr-2"></i>
                Test All
            </button>
            <button @click="showAddProviderModal = true" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Provider
            </button>
        </div>
    </div>

    <!-- Providers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="provider in providers" :key="provider.id">
            <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-lg flex items-center justify-center"
                                 :class="getProviderIconClass(provider.type)">
                                <i class="fas fa-cloud text-white text-xl"></i>
                            </div>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                              :class="provider.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                              x-text="provider.is_active ? 'Active' : 'Inactive'"></span>
                    </div>
                    
                    <h3 class="text-lg font-medium text-gray-900 mb-2" x-text="provider.name"></h3>
                    <p class="text-sm text-gray-600 mb-4" x-text="provider.description || 'No description'"></p>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Type:</span>
                            <span class="font-medium" x-text="provider.type"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Region:</span>
                            <span class="font-medium" x-text="provider.region || 'Not set'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">VMs:</span>
                            <span class="font-medium" x-text="provider.vm_count || 0"></span>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex space-x-2">
                            <button @click="testConnection(provider)" 
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium">
                                Test
                            </button>
                            <button @click="editProvider(provider)" 
                                    class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded text-sm font-medium">
                                Edit
                            </button>
                            <button @click="deleteProvider(provider)" 
                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-medium">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Add Provider Card -->
        <div @click="showAddProviderModal = true" 
             class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-gray-400 cursor-pointer transition-colors">
            <div class="text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center mb-4">
                    <i class="fas fa-plus text-gray-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Add Provider</h3>
                <p class="text-sm text-gray-600">Connect a new cloud provider</p>
            </div>
        </div>
    </div>

    <!-- Add Provider Modal -->
    <div x-show="showAddProviderModal" x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <button @click="showAddProviderModal = false" 
                        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
                
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Cloud Provider</h3>
                
                <form @submit.prevent="addProvider()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" x-model="newProvider.name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select x-model="newProvider.type" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Type</option>
                                <option value="gcp">Google Cloud Platform</option>
                                <option value="digitalocean">DigitalOcean</option>
                                <option value="huawei">Huawei Cloud</option>
                                <option value="aws">Amazon Web Services</option>
                                <option value="azure">Microsoft Azure</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                            <input type="text" x-model="newProvider.region" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                            <input type="password" x-model="newProvider.api_key" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                            <input type="password" x-model="newProvider.secret_key" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea x-model="newProvider.description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex space-x-3">
                        <button type="button" @click="showAddProviderModal = false"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md font-medium">
                            Cancel
                        </button>
                        <button type="submit" :disabled="loading"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">
                            <span x-show="!loading">Add Provider</span>
                            <span x-show="loading">Adding...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function providersData() {
    return {
        providers: [],
        loading: false,
        showAddProviderModal: false,
        newProvider: {
            name: '',
            type: '',
            region: '',
            api_key: '',
            secret_key: '',
            description: ''
        },
        
        async loadProviders() {
            try {
                const response = await axios.get('/api/cloud-providers');
                this.providers = response.data.data || [];
            } catch (error) {
                console.error('Failed to load providers:', error);
                this.loadSampleData();
            }
        },
        
        loadSampleData() {
            this.providers = [
                {
                    id: 1,
                    name: 'GCP Production',
                    type: 'gcp',
                    region: 'us-central1',
                    is_active: true,
                    description: 'Main GCP account for production workloads',
                    vm_count: 8
                },
                {
                    id: 2,
                    name: 'DigitalOcean NYC',
                    type: 'digitalocean',
                    region: 'nyc3',
                    is_active: true,
                    description: 'DigitalOcean account for NYC region',
                    vm_count: 4
                },
                {
                    id: 3,
                    name: 'Huawei Cloud',
                    type: 'huawei',
                    region: 'cn-north-1',
                    is_active: false,
                    description: 'Huawei Cloud for China region',
                    vm_count: 0
                }
            ];
        },
        
        getProviderIconClass(type) {
            const classes = {
                'gcp': 'bg-blue-500',
                'digitalocean': 'bg-green-500',
                'huawei': 'bg-red-500',
                'aws': 'bg-orange-500',
                'azure': 'bg-purple-500'
            };
            return classes[type] || 'bg-gray-500';
        },
        
        async testConnection(provider) {
            try {
                await axios.post(`/api/cloud-providers/${provider.id}/test-connection`);
                alert('Connection test successful!');
            } catch (error) {
                alert('Connection test failed: ' + error.message);
            }
        },
        
        async testAllConnections() {
            for (const provider of this.providers) {
                await this.testConnection(provider);
            }
        },
        
        editProvider(provider) {
            // Navigate to edit page or open modal
            console.log('Edit provider:', provider);
        },
        
        async deleteProvider(provider) {
            if (confirm(`Are you sure you want to delete ${provider.name}?`)) {
                try {
                    await axios.delete(`/api/cloud-providers/${provider.id}`);
                    this.providers = this.providers.filter(p => p.id !== provider.id);
                } catch (error) {
                    alert('Failed to delete provider: ' + error.message);
                }
            }
        },
        
        async addProvider() {
            this.loading = true;
            try {
                const response = await axios.post('/api/cloud-providers', this.newProvider);
                this.providers.push(response.data.data);
                this.showAddProviderModal = false;
                this.newProvider = {
                    name: '',
                    type: '',
                    region: '',
                    api_key: '',
                    secret_key: '',
                    description: ''
                };
            } catch (error) {
                alert('Failed to add provider: ' + error.message);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
