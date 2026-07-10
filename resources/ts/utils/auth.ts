import { useCookie } from '@core/composable/useCookie'

let isRedirectingToLogin = false

export function clearAuthAndRedirect(force = false) {
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
