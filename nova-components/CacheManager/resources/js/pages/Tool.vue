<template>
  <div>
    <Head title="Cache Manager" />

    <Heading class="mb-6">Cache Manager</Heading>

    <div class="mb-6">
      <Button
        type="button"
        variant="danger"
        @click="clearAll"
        :loading="clearingAll"
        :disabled="clearing"
      >
        Clear All Caches
      </Button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <Card
        v-for="cacheType in cacheTypes"
        :key="cacheType.type"
        class="p-6"
      >
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">{{ cacheType.label }}</h3>
          <Button
            type="button"
            variant="primary"
            size="sm"
            @click="clearCache(cacheType.type)"
            :loading="clearing === cacheType.type"
            :disabled="clearing && clearing !== cacheType.type"
          >
            Clear
          </Button>
        </div>

        <div v-if="timestamps[cacheType.type]" class="text-sm text-gray-600 dark:text-gray-400">
          <p class="font-medium mb-1">Last cleared:</p>
          <p>{{ formatTimestamp(timestamps[cacheType.type]) }}</p>
        </div>
        <div v-else class="text-sm text-gray-500 dark:text-gray-500">
          <p>Never cleared</p>
        </div>
      </Card>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'

export default {
  setup() {
    const cacheTypes = [
      { type: 'application', label: 'Application Cache' },
      { type: 'config', label: 'Config Cache' },
      { type: 'route', label: 'Route Cache' },
      { type: 'view', label: 'View Cache' },
      { type: 'event', label: 'Event Cache' },
      { type: 'optimize', label: 'Optimize Cache' },
    ]

    const timestamps = ref({})
    const clearing = ref(null)
    const clearingAll = ref(false)

    const loadTimestamps = async () => {
      try {
        const response = await Nova.request().get('/nova-vendor/cache-manager/timestamps')
        if (response.data.success) {
          timestamps.value = response.data.timestamps
        }
      } catch (error) {
        console.error('Failed to load timestamps:', error)
      }
    }

    const clearCache = async (type) => {
      clearing.value = type

      try {
        const response = await Nova.request().post(`/nova-vendor/cache-manager/clear/${type}`)

        if (response.data.success) {
          Nova.success(response.data.message)
          timestamps.value[type] = response.data.timestamp
        } else {
          Nova.error(response.data.message || 'Failed to clear cache')
        }
      } catch (error) {
        Nova.error(
          error.response?.data?.message || 'An error occurred while clearing the cache'
        )
      } finally {
        clearing.value = null
      }
    }

    const clearAll = async () => {
      if (!confirm('Are you sure you want to clear all caches?')) {
        return
      }

      clearingAll.value = true

      try {
        const response = await Nova.request().post('/nova-vendor/cache-manager/clear-all')

        if (response.data.success) {
          Nova.success(response.data.message)
          await loadTimestamps()
        } else {
          Nova.error(response.data.message || 'Failed to clear all caches')
        }
      } catch (error) {
        Nova.error(
          error.response?.data?.message || 'An error occurred while clearing all caches'
        )
      } finally {
        clearingAll.value = false
      }
    }

    const formatTimestamp = (timestamp) => {
      if (!timestamp) {
        return 'Never'
      }

      try {
        const date = new Date(timestamp)
        return date.toLocaleString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
        })
      } catch (error) {
        return timestamp
      }
    }

    onMounted(() => {
      loadTimestamps()
    })

    return {
      cacheTypes,
      timestamps,
      clearing,
      clearingAll,
      clearCache,
      clearAll,
      formatTimestamp,
    }
  },
}
</script>
