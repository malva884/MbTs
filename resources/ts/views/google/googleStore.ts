import { defineStore } from 'pinia'
import { useRouter } from 'vue-router'


export const useGoogleStore = defineStore('user', () => {
  const router = useRouter()

  // Data
  const clientId = ref<string>('444092032667-b746itivq7o5c8o6uh5a1men8pip6m6s.apps.googleusercontent.com')
  const clientSecret = ref<string>('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')
  const redirect_uri = ref<string>('http://127.0.0.1:8000/api/reception/google-calendar/auth-callback')
  const userData = ref<object>({})

  const fetchUserDataFrom = async (code: any) => {
    try {
      localStorage.setItem('gCode', JSON.stringify(code))

      const { data } = await $api(`https://oauth2.googleapis.com/token`, {
        code,
        client_id: clientId.value,
        client_secret: clientSecret.value,
        redirect_uri: redirect_uri.value,
        grant_type: 'authorization_code',
      })


      if (data) {
        const accessToken = data.access_token

        // Fetch user details using the access token
        const userObj = await useApi<any>(createUrl('https://www.googleapis.com/calendar/v3/calendars', {
          query: {
            headers: {
              Authorization: `Bearer ${accessToken}`,
            },
          },
        },))


        if (userObj && userObj.data) {
          // save copy in storage
          localStorage.setItem('user', JSON.stringify(userObj.data))
          userData.value = userObj.data
        }
        else {
          // Handle the case where userResponse or userResponse.data is undefined
          console.error('Failed to fetch user data')
        }
      }
    }
    catch (e) {
      console.error('Failed to exchaange token', e)
    }
  }

  return {
    // Data
    clientId,
    clientSecret,
    userData,

    // Functions
    fetchUserDataFrom,
  }
})

export default useGoogleStore
