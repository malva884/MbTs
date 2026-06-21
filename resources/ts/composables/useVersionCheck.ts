import { ref, onMounted, onUnmounted } from 'vue'
import { useApi } from '@/plugins/1.api/useApi'
import { createUrl } from '@/plugins/1.api/utils'

const CURRENT_VERSION_KEY = 'app_version'
const CHECK_INTERVAL = 60000 // Check every 60 seconds

export function useVersionCheck() {
  const showUpdateDialog = ref(false)
  const checkInterval = ref<number | null>(null)

  const getCurrentVersion = (): string => {
    return localStorage.getItem(CURRENT_VERSION_KEY) || '1.0.0'
  }

  const setCurrentVersion = (version: string) => {
    localStorage.setItem(CURRENT_VERSION_KEY, version)
  }

  const checkVersion = async () => {
    try {
      const { data: versionData } = await useApi<{ version: string }>(
        createUrl('/users/version')
      )

      if (versionData.value) {
        const serverVersion = versionData.value.version
        const localVersion = getCurrentVersion()

        if (serverVersion !== localVersion) {
          showUpdateDialog.value = true
        }
      }
    } catch (error) {
      console.error('Error checking version:', error)
    }
  }

  const refreshPage = () => {
    // Update local version before refresh
    checkVersion().then(() => {
      showUpdateDialog.value = false
      window.location.reload()
    })
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
