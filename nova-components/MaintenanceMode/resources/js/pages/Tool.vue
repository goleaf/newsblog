<template>
  <div>
    <Head title="Maintenance Mode" />

    <Heading class="mb-6">Maintenance Mode</Heading>

    <Card class="p-6 mb-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold mb-2">Status</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            <span v-if="enabled" class="font-semibold text-red-600 dark:text-red-400">
              Maintenance mode is currently ENABLED
            </span>
            <span v-else class="font-semibold text-green-600 dark:text-green-400">
              Maintenance mode is currently DISABLED
            </span>
          </p>
          <p v-if="enabled && enabledTime" class="text-xs text-gray-500 dark:text-gray-500 mt-1">
            Enabled: {{ formatTimestamp(enabledTime) }}
          </p>
        </div>
        <div class="flex items-center">
          <label class="flex items-center cursor-pointer">
            <input
              type="checkbox"
              :checked="enabled"
              @change="toggleMaintenance"
              :disabled="toggling"
              class="sr-only"
            />
            <div
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                enabled ? 'bg-red-600' : 'bg-gray-300 dark:bg-gray-600',
                toggling ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                  enabled ? 'translate-x-6' : 'translate-x-1',
                ]"
              />
            </div>
            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ enabled ? 'Enabled' : 'Disabled' }}
            </span>
          </label>
        </div>
      </div>
    </Card>

    <Card class="p-6 mb-6" v-if="enabled">
      <h3 class="text-lg font-semibold mb-4">Maintenance Message</h3>
      <textarea
        v-model="message"
        @blur="updateMessage"
        :disabled="updatingMessage"
        rows="4"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white"
        placeholder="Enter maintenance message..."
      />
      <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
        This message will be displayed to visitors when maintenance mode is enabled.
      </p>
    </Card>

    <Card class="p-6" v-if="enabled">
      <h3 class="text-lg font-semibold mb-4">IP Whitelist</h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        Add IP addresses that should be allowed to bypass maintenance mode. Enter one IP per line or separate with commas.
      </p>
      <textarea
        v-model="ipWhitelistText"
        @blur="updateIpWhitelist"
        :disabled="updatingIpWhitelist"
        rows="6"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white font-mono text-sm"
        placeholder="127.0.0.1&#10;192.168.1.1&#10;10.0.0.1"
      />
      <div v-if="allowedIps.length > 0" class="mt-4">
        <p class="text-sm font-medium mb-2">Currently whitelisted IPs:</p>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="ip in allowedIps"
            :key="ip"
            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200"
          >
            {{ ip }}
          </span>
        </div>
      </div>
      <p v-else class="text-xs text-gray-500 dark:text-gray-500 mt-2">
        No IP addresses are currently whitelisted.
      </p>
    </Card>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue'

export default {
  setup() {
    const enabled = ref(false)
    const message = ref('')
    const allowedIps = ref([])
    const enabledTime = ref(null)
    const toggling = ref(false)
    const updatingMessage = ref(false)
    const updatingIpWhitelist = ref(false)
    const ipWhitelistText = ref('')

    const loadStatus = async () => {
      try {
        const response = await Nova.request().get('/nova-vendor/maintenance-mode/status')
        if (response.data.success) {
          enabled.value = response.data.enabled
          message.value = response.data.message || ''
          allowedIps.value = response.data.allowed || []
          enabledTime.value = response.data.time
          ipWhitelistText.value = allowedIps.value.join('\n')
        }
      } catch (error) {
        console.error('Failed to load status:', error)
        Nova.error('Failed to load maintenance mode status')
      }
    }

    const toggleMaintenance = async () => {
      const newState = !enabled.value
      toggling.value = true

      try {
        const response = await Nova.request().post('/nova-vendor/maintenance-mode/toggle', {
          enabled: newState,
        })

        if (response.data.success) {
          Nova.success(response.data.message)
          enabled.value = response.data.enabled
          if (enabled.value) {
            await loadStatus()
          } else {
            message.value = ''
            allowedIps.value = []
            ipWhitelistText.value = ''
            enabledTime.value = null
          }
        } else {
          Nova.error(response.data.message || 'Failed to toggle maintenance mode')
        }
      } catch (error) {
        Nova.error(
          error.response?.data?.message || 'An error occurred while toggling maintenance mode'
        )
      } finally {
        toggling.value = false
      }
    }

    const updateMessage = async () => {
      if (!enabled.value) {
        return
      }

      updatingMessage.value = true

      try {
        const response = await Nova.request().post('/nova-vendor/maintenance-mode/message', {
          message: message.value,
        })

        if (response.data.success) {
          Nova.success(response.data.message)
          message.value = response.data.data.message
        } else {
          Nova.error(response.data.message || 'Failed to update maintenance message')
          await loadStatus()
        }
      } catch (error) {
        Nova.error(
          error.response?.data?.message || 'An error occurred while updating maintenance message'
        )
        await loadStatus()
      } finally {
        updatingMessage.value = false
      }
    }

    const updateIpWhitelist = async () => {
      if (!enabled.value) {
        return
      }

      // Parse IP addresses from textarea (support both comma-separated and line-separated)
      const ips = ipWhitelistText.value
        .split(/[,\n]/)
        .map((ip) => ip.trim())
        .filter((ip) => ip.length > 0)

      updatingIpWhitelist.value = true

      try {
        const response = await Nova.request().post('/nova-vendor/maintenance-mode/ip-whitelist', {
          ips: ips,
        })

        if (response.data.success) {
          Nova.success(response.data.message)
          allowedIps.value = response.data.data.allowed
          ipWhitelistText.value = allowedIps.value.join('\n')
        } else {
          Nova.error(response.data.message || 'Failed to update IP whitelist')
          await loadStatus()
        }
      } catch (error) {
        Nova.error(
          error.response?.data?.message || 'An error occurred while updating IP whitelist'
        )
        await loadStatus()
      } finally {
        updatingIpWhitelist.value = false
      }
    }

    const formatTimestamp = (timestamp) => {
      if (!timestamp) {
        return 'Unknown'
      }

      try {
        const date = new Date(timestamp * 1000)
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
      loadStatus()
    })

    watch(enabled, (newValue) => {
      if (!newValue) {
        ipWhitelistText.value = ''
      }
    })

    return {
      enabled,
      message,
      allowedIps,
      enabledTime,
      toggling,
      updatingMessage,
      updatingIpWhitelist,
      ipWhitelistText,
      toggleMaintenance,
      updateMessage,
      updateIpWhitelist,
      formatTimestamp,
    }
  },
}
</script>
