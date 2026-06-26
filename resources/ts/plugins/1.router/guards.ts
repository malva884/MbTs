import type { Router } from 'vue-router'
import { canNavigate } from '@layouts/plugins/casl'

export const setupGuards = (router: Router) => {
  // 👉 router.beforeEach
  // Docs: https://router.vuejs.org/guide/advanced/navigation-guards.html#global-before-guards
  router.beforeEach(to => {
    /*
     * If it's a public route, continue navigation. This kind of pages are allowed to visited by login & non-login users. Basically, without any restrictions.
     * Examples of public routes are, 404, under maintenance, etc.
     */
    if (to.meta.public)
      return

    /**
     * Check if user is logged in by checking if token & user data exists in local storage
     * Feel free to update this logic to suit your needs
     */
    const isLoggedIn = !!(useCookie('userData').value && useCookie('accessToken').value)

    const expiredToken = useCookie('expiredToken').value
    const isTokenExpired = expiredToken ? new Date(expiredToken) < new Date() : false

    if (isLoggedIn && isTokenExpired) {
      const userData = useCookie<any>('userData')
      // Remove "accessToken" from cookie
      useCookie('accessToken').value = null
      useCookie('expiredToken').value = null

      // Remove "userData" from cookie
      userData.value = null

      if (to.name === 'login')
        return undefined

      return { name: 'login' }
    }


    const userData = useCookie<Record<string, unknown> | null | undefined>('userData')

    if (userData.value?.passwordExpired)
      return { name: 'change-password' }

    /*
      If user is logged in and is trying to access login like page, redirect to home
      else allow visiting the page
      (WARN: Don't allow executing further by return statement because next code will check for permissions)
     */
    if (to.meta.unauthenticatedOnly) {
      if (isLoggedIn)
        return '/'
      else
        return undefined
    }

    if (!canNavigate(to)) {
      /* eslint-disable indent */
      return isLoggedIn
        ? { name: 'not-authorized' }
        : {
            name: 'login',
            query: {
              ...to.query,
              to: to.fullPath !== '/' ? to.path : undefined,
            },
          }
      /* eslint-enable indent */
    }
  })
}
