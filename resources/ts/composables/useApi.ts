import { createFetch } from '@vueuse/core'
import { destr } from 'destr'

let isRedirectingToLogin = false

function clearAuthAndRedirect(force = false) {
  if (typeof window === 'undefined')
    return

  if (isRedirectingToLogin)
    return

  if (window.location.pathname === '/login')
    return

  const accessToken = useCookie('accessToken').value
  const userData = useCookie('userData').value

  if (!force && !accessToken && !userData)
    return

  isRedirectingToLogin = true

  useCookie('accessToken').value = null
  useCookie('expiredToken').value = null
  useCookie('userData').value = null

  window.location.href = '/login'
}

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

      if (response?.status === 401)
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
      if (response?.status === 401)
        clearAuthAndRedirect(true)

      return { error }
    },
  },
})
