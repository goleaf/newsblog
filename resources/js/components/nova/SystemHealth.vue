<template>
    <div class="system-health-dashboard">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold">System Health</h1>
            <div class="flex items-center gap-4">
                <div v-if="loading" class="text-sm text-gray-500">Refreshing...</div>
                <div v-else class="text-sm text-gray-500">
                    Last updated: {{ lastUpdate }}
                </div>
                <button
                    @click="fetchHealthData"
                    :disabled="loading"
                    class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600 disabled:opacity-50"
                >
                    Refresh
                </button>
            </div>
        </div>

        <div v-if="error" class="mb-6 rounded-md bg-red-50 p-4 text-red-800">
            <p class="font-medium">Error loading health data</p>
            <p class="text-sm">{{ error }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Database Status -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-xl font-semibold">Database Connections</h2>
                <div class="space-y-3">
                    <div
                        v-for="(status, name) in healthData.databases"
                        :key="name"
                        class="flex items-center justify-between rounded-md border p-3"
                    >
                        <div class="flex items-center gap-3">
                            <span class="font-medium">{{ name }}</span>
                        </div>
                        <span
                            :class="{
                                'rounded-full px-3 py-1 text-xs font-medium': true,
                                'bg-green-100 text-green-800': status.status === 'connected',
                                'bg-red-100 text-red-800': status.status === 'failed',
                            }"
                        >
                            {{ status.status === 'connected' ? 'Connected' : 'Failed' }}
                        </span>
                        <div v-if="status.error" class="ml-2 text-xs text-red-600">
                            {{ status.error }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Status -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-xl font-semibold">Queue Status</h2>
                <div class="space-y-3">
                    <div
                        v-for="(status, name) in healthData.queues"
                        :key="name"
                        class="rounded-md border p-3"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <span class="font-medium">{{ name }}</span>
                            <span
                                :class="{
                                    'rounded-full px-3 py-1 text-xs font-medium': true,
                                    'bg-green-100 text-green-800': status.status === 'active',
                                    'bg-red-100 text-red-800': status.status === 'failed',
                                }"
                            >
                                {{ status.status === 'active' ? 'Active' : 'Failed' }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div>Driver: {{ status.driver }}</div>
                            <div>Queue Size: {{ status.size }}</div>
                            <div
                                :class="{
                                    'font-medium': true,
                                    'text-red-600': status.failed_jobs > 0,
                                }"
                            >
                                Failed Jobs: {{ status.failed_jobs }}
                            </div>
                        </div>
                        <div v-if="status.error" class="mt-2 text-xs text-red-600">
                            {{ status.error }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Storage Usage -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm md:col-span-2">
                <h2 class="mb-4 text-xl font-semibold">Storage Usage</h2>
                <div class="space-y-4">
                    <div
                        v-for="(disk, name) in healthData.storage"
                        :key="name"
                        class="rounded-md border p-4"
                    >
                        <div class="mb-2 flex items-center justify-between">
                            <div>
                                <span class="font-medium">{{ name }}</span>
                                <span class="ml-2 text-sm text-gray-500">({{ disk.driver }})</span>
                            </div>
                            <span
                                :class="{
                                    'rounded-full px-3 py-1 text-xs font-medium': true,
                                    'bg-green-100 text-green-800': disk.status === 'accessible',
                                    'bg-yellow-100 text-yellow-800': disk.status === 'inaccessible',
                                    'bg-red-100 text-red-800': disk.status === 'failed',
                                }"
                            >
                                {{ disk.status }}
                            </span>
                        </div>
                        <div v-if="disk.total !== null" class="mt-3">
                            <div class="mb-2 flex justify-between text-sm text-gray-600">
                                <span>Used: {{ formatBytes(disk.used) }}</span>
                                <span>Free: {{ formatBytes(disk.free) }}</span>
                                <span>Total: {{ formatBytes(disk.total) }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                                <div
                                    :class="{
                                        'h-full transition-all duration-300': true,
                                        'bg-green-500': disk.usage_percent < 70,
                                        'bg-yellow-500': disk.usage_percent >= 70 && disk.usage_percent < 90,
                                        'bg-red-500': disk.usage_percent >= 90,
                                    }"
                                    :style="{ width: disk.usage_percent + '%' }"
                                ></div>
                            </div>
                            <div class="mt-1 text-right text-xs text-gray-500">
                                {{ disk.usage_percent }}% used
                            </div>
                        </div>
                        <div v-else-if="disk.status === 'accessible'" class="text-sm text-gray-500">
                            Disk space information not available for this driver
                        </div>
                        <div v-if="disk.error" class="mt-2 text-xs text-red-600">
                            {{ disk.error }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Errors -->
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm md:col-span-2">
                <h2 class="mb-4 text-xl font-semibold">Recent Errors</h2>
                <div v-if="healthData.errors.length === 0" class="text-center text-gray-500">
                    No recent errors found
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="(error, index) in healthData.errors"
                        :key="index"
                        class="rounded-md border border-red-200 bg-red-50 p-4"
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span
                                :class="{
                                    'rounded-full px-2 py-1 text-xs font-medium': true,
                                    'bg-red-200 text-red-800': error.level === 'ERROR',
                                    'bg-red-300 text-red-900': error.level === 'CRITICAL',
                                    'bg-red-400 text-red-950': error.level === 'ALERT' || error.level === 'EMERGENCY',
                                }"
                            >
                                {{ error.level }}
                            </span>
                            <span class="text-xs text-gray-500">{{ error.timestamp }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-800">{{ error.message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const healthData = ref({
    databases: {},
    queues: {},
    storage: {},
    errors: [],
    timestamp: null,
})

const loading = ref(false)
const error = ref(null)
const lastUpdate = ref('Never')
let refreshInterval = null

const formatBytes = (bytes) => {
    if (bytes === null || bytes === undefined) {
        return 'N/A'
    }
    if (bytes === 0) {
        return '0 Bytes'
    }
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

const formatTimestamp = (timestamp) => {
    if (!timestamp) {
        return 'Never'
    }
    try {
        const date = new Date(timestamp)
        return date.toLocaleString()
    } catch (e) {
        return timestamp
    }
}

const fetchHealthData = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await Nova.request().get('/nova-api/system-health')
        healthData.value = response.data
        lastUpdate.value = formatTimestamp(response.data.timestamp)
    } catch (e) {
        error.value = e.response?.data?.message || e.message || 'Failed to fetch health data'
        console.error('Error fetching health data:', e)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchHealthData()
    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(() => {
        fetchHealthData()
    }, 30000)
})

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval)
    }
})
</script>

<style scoped>
.system-health-dashboard {
    padding: 1.5rem;
}
</style>

