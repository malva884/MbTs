import { ref, onMounted, onUnmounted } from 'vue'
import { useApi } from '@/composables/useApi'

const CURRENT_VERSION_KEY = 'app_version'
const CHECK_INTERVAL = 60000 // Check every 60 seconds

export function useVersionCheck() {
  const showUpdateDialog = ref(false)
  const checkInterval = ref<number | null>(null)

  const getCurrentVersion = (): string => {
    const version = localStorage.getItem(CURRENT_VERSION_KEY)
    // Return default if version is null, undefined, or the string 'undefined'
    if (!version || version === 'undefined') {
      return '1.0.0'
    }
    return version
  }

  const setCurrentVersion = (version: string) => {
    localStorage.setItem(CURRENT_VERSION_KEY, version)
  }

  const checkVersion = async () => {
    try {
      const { data: versionData, error } = await useApi<{ version: string }>('/users/version')

      if (error.value) {
        console.error('API error occurred:', error.value)
        return
      }

      if (versionData.value && versionData.value.version) {
        const serverVersion = versionData.value.version
        const localVersion = getCurrentVersion()

        // Initialize local version if not set
        if (!localStorage.getItem(CURRENT_VERSION_KEY) || localStorage.getItem(CURRENT_VERSION_KEY) === 'undefined') {
          setCurrentVersion(serverVersion)
          return
        }

        if (serverVersion !== localVersion) {
          showUpdateDialog.value = true
        }
      }
    } catch (error) {
      console.error('Error checking version:', error)
    }
  }

  const refreshPage = async () => {
    try {
      // Get the latest version from server
      const { data: versionData } = await useApi<{ version: string }>('/users/version')

      if (versionData.value && versionData.value.version) {
        // Update local version to match server version
        setCurrentVersion(versionData.value.version)
      }

      showUpdateDialog.value = false
      window.location.reload()
    } catch (error) {
      console.error('Error updating version before refresh:', error)
      // Still reload even if there's an error
      window.location.reload()
    }
  }

  const startVersionCheck = () => {
    // Check immediately on mount
    checkVersion()

    // Then check periodically
    checkInterval.value = window.setInterval(() => {
      checkVersion()
    }, CHECK_INTERVAL)
  }

  const stopVersionCheck = () => {
    if (checkInterval.value) {
      clearInterval(checkInterval.value)
      checkInterval.value = null
    }
  }

  onMounted(() => {
    startVersionCheck()
  })

  onUnmounted(() => {
    stopVersionCheck()
  })

  return {
    showUpdateDialog,
    refreshPage,
  }
}
