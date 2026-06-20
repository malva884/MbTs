import { ofetch } from 'ofetch'

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value
    if (accessToken) {
      options.headers = {
        ...options.headers,
        Authorization: `Bearer ${accessToken}`,
      }
    }

    // Add CSRF token for POST requests
    if (options.method === 'POST' || options.method === 'PUT' || options.method === 'PATCH' || options.method === 'DELETE') {
      const csrfToken = useCookie('XSRF-TOKEN').value
      if (csrfToken) {
        options.headers = {
          ...options.headers,
          'X-XSRF-TOKEN': csrfToken,
        }
      }
    }
  },
})
