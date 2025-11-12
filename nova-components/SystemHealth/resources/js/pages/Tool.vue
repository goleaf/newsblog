<template>
  <div>
    <Head title="System Health" />

    <Heading class="mb-6">System Health</Heading>

    <div v-if="loading" class="flex items-center justify-center py-12">
      <Loader />
    </div>

    <div v-else class="space-y-6">
      <!-- Database Status -->
      <Card class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
            </svg>
            Database Connection
          </h3>
          <Badge :variant="data.database.connected ? 'success' : 'danger'">
            {{ data.database.connected ? 'Connected' : 'Disconnected' }}
          </Badge>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
          <p><span class="font-medium">Driver:</span> {{ data.database.driver }}</p>
          <p><span class="font-medium">Status:</span> {{ data.database.message }}</p>
        </div>
      </Card>

      <!-- Queue Status -->
      <Card class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            Queue Status
          </h3>
          <Badge :variant="getQueueBadgeVariant()">
            {{ data.queue.status }}
          </Badge>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="font-medium text-gray-700 dark:text-gray-300">Driver</p>
            <p class="text-gray-600 dark:text-gray-400">{{ data.queue.driver }}</p>
          </div>
          <div>
            <p class="font-medium text-gray-700 dark:text-gray-300">Failed Jobs</p>
            <p class="text-gray-600 dark:text-gray-400">{{ data.queue.failed_jobs }}</p>
          </div>
          <div>
            <p class="font-medium text-gray-700 dark:text-gray-300">Pending Jobs</p>
            <p class="text-gray-600 dark:text-gray-400">{{ data.queue.pending_jobs }}</p>
          </div>
        </div>
      </Card>

      <!-- Storage Status -->
      <Card class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
            </svg>
            Storage Usage
          </h3>
          <Badge :variant="getStorageBadgeVariant()">
            {{ data.storage.status }}
          </Badge>
        </div>
        <div class="space-y-3">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <p class="font-medium text-gray-700 dark:text-gray-300">Total</p>
              <p class="text-gray-600 dark:text-gray-400">{{ data.storage.total }}</p>
            </div>
            <div>
              <p class="font-medium text-gray-700 dark:text-gray-300">Used</p>
              <p class="text-gray-600 dark:text-gray-400">{{ data.storage.used }}</p>
            </div>
            <div>
              <p class="font-medium text-gray-700 dark:text-gray-300">Free</p>
              <p class="text-gray-600 dark:text-gray-400">{{ data.storage.free }}</p>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-gray-600 dark:text-gray-400">Usage</span>
              <span class="font-medium">{{ data.storage.used_percentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div 
                class="h-2 rounded-full transition-all"
                :class="getStorageBarClass()"
                :style="{ width: data.storage.used_percentage + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </Card>

      <!-- Recent Errors -->
      <Card class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Recent Errors
          </h3>
          <Badge :variant="data.errors.count > 0 ? 'danger' : 'success'">
            {{ data.errors.count }} {{ data.errors.count === 1 ? 'Error' : 'Errors' }}
          </Badge>
        </div>
        <div v-if="data.errors.count === 0" class="text-sm text-gray-600 dark:text-gray-400">
          <p>No recent errors found.</p>
        </div>
        <div v-else class="space-y-3">
          <div 
            v-for="(error, index) in data.errors.errors" 
            :key="index"
            class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
          >
            <div class="flex items-start justify-between mb-1">
              <Badge variant="danger" size="sm">{{ error.level }}</Badge>
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ error.timestamp }}</span>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-mono">{{ error.message }}</p>
          </div>
        </div>
      </Card>

      <!-- Auto-refresh indicator -->
      <div class="text-center text-sm text-gray-500 dark:text-gray-400">
        <p>Auto-refreshing every 30 seconds</p>
        <p class="text-xs mt-1">Last updated: {{ lastUpdated }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'

export default {
  setup() {
    const data = ref({
      database: {
        connected: false,
        message: '',
        driver: '',
      },
      queue: {
        driver: '',
        failed_jobs: 0,
        pending_jobs: 0,
        status: 'unknown',
      },
      storage: {
        total: '',
        used: '',
        free: '',
        used_percentage: 0,
        status: 'unknown',
      },
      errors: {
        count: 0,
        errors: [],
      },
    })

    const loading = ref(true)
    const lastUpdated = ref('')
    let refreshInterval = null

    const loadStatus = async () => {
      try {
        const response = await Nova.request().get('/nova-vendor/system-health/status')

        if (response.data.success) {
          data.value = response.data.data
          lastUpdated.value = new Date().toLocaleTimeString()
        } else {
          Nova.error(response.data.message || 'Failed to load system health')
        }
      } catch (error) {
        Nova.error(
          error.response?.data?.message || 'An error occurred while loading system health'
        )
      } finally {
        loading.value = false
      }
    }

    const getQueueBadgeVariant = () => {
      if (data.value.queue.status === 'healthy') return 'success'
      if (data.value.queue.status === 'warning') return 'warning'
      return 'danger'
    }

    const getStorageBadgeVariant = () => {
      if (data.value.storage.status === 'healthy') return 'success'
      if (data.value.storage.status === 'warning') return 'warning'
      if (data.value.storage.status === 'critical') return 'danger'
      return 'info'
    }

    const getStorageBarClass = () => {
      const percentage = data.value.storage.used_percentage
      if (percentage > 90) return 'bg-red-600'
      if (percentage > 75) return 'bg-yellow-500'
      return 'bg-green-600'
    }

    onMounted(() => {
      loadStatus()
      
      // Auto-refresh every 30 seconds
      refreshInterval = setInterval(() => {
        loadStatus()
      }, 30000)
    })

    onUnmounted(() => {
      if (refreshInterval) {
        clearInterval(refreshInterval)
      }
    })

    return {
      data,
      loading,
      lastUpdated,
      getQueueBadgeVariant,
      getStorageBadgeVariant,
      getStorageBarClass,
    }
  },
}
</script>
