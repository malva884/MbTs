import { createFetch } from '@vueuse/core'
import { destr } from 'destr'
import { clearAuthAndRedirect } from '@/utils/auth'

export const useApi = createFetch({
  baseUrl: import.meta.env.VITE_API_BASE_URL || '/api',
  fetchOptions: {
    headers: {
      Accept: 'application/json',
    },
  },
  options: {
    refetch: true,
    async beforeFetch({ options }) {
      const accessToken = useCookie('accessToken').value

      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,

        }
      }

      return { options }
    },
    afterFetch(ctx) {
      const { data, response } = ctx

      // Handle 401/419 (Unauthorized / Authentication Timeout) - session expired
      if (response?.status === 401 || response?.status === 419)
        clearAuthAndRedirect(true)

      // Parse data if it's JSON

      let parsedData = null
      try {
        parsedData = destr(data)
      }
      catch (error) {
        console.error(error)
      }

      return { data: parsedData, response }
    },
    onFetchError({ error, response }) {
      // Handle 401/419 (Unauthorized / Authentication Timeout) - session expired
      if (response?.status === 401 || response?.status === 419)
        clearAuthAndRedirect(true)

      return { error }
    },
  },
})
