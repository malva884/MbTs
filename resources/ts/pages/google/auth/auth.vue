<script setup lang="ts">
import { googleSdkLoaded } from 'vue3-google-login'
import { useGoogleStore } from '@/views/google/googleStore'


definePage({
    meta: {
        action: 'all',
        subject: 'Administration',
    },
})

// Composables
const googleStore = useGoogleStore()

const { clientId } = storeToRefs(googleStore)

const redirect_uri = ref<string>('http://127.0.0.1:8000/api/reception/google-calendar/auth-callback')

const { fetchUserDataFrom } = googleStore

console.log(fetchUserDataFrom)
// Methods
const signInWithGoogle = () => {
    googleSdkLoaded(google => {
        google.accounts.oauth2
            .initCodeClient({
                client_id: clientId.value,
                scope: 'https://www.googleapis.com/auth/calendar',
                redirect_uri: redirect_uri.value,
                callback: response => {
                    if (response.code)
                        fetchUserDataFrom(response.code)
                },
            })
            .requestCode()
    })
}
</script>
<template>
  Welcome to {{ 'yourSiteName' }}
  <div @click="signInWithGoogle">
    Continue with google
  </div>
</template>
