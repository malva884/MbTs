import { ofetch } from 'ofetch'
import { clearAuthAndRedirect } from '@/utils/auth'

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    Accept: 'application/json',
  },
  async onRequest({ options }) {
    // Non sovrascrivere l'header Authorization se già impostato manualmente
    const existingAuth = options.headers?.Authorization || options.headers?.authorization
    if (!existingAuth) {
      const accessToken = useCookie('accessToken').value
      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,
        }
      }
    }
  },
  async onResponseError({ response }) {
    // Handle 401/419 (Unauthorized / Authentication Timeout) - session expired
    if (response?.status === 401 || response?.status === 419)
      clearAuthAndRedirect(true)
  },
})
