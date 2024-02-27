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

const CLIENT_ID = storeToRefs(googleStore);
const API_KEY = 'xxxxx';
const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';
const SCOPES = 'https://www.googleapis.com/auth/calendar.readonly';

let tokenClient;
let gapiInited = false;
let gisInited = false;

function gapiLoaded() {
    window.gapi.load('client', initializeGapiClient);
}

async function initializeGapiClient() {
    await window.gapi.client.init({
        apiKey: API_KEY,
        discoveryDocs: [DISCOVERY_DOC],
    });
    gapiInited = true;
    maybeEnableButtons();
}

function gisLoaded() {
    console.log('gisLoaded')
    tokenClient = window.google.accounts.oauth2.initTokenClient({
        client_id: CLIENT_ID,
        scope: SCOPES,
        callback: '',
    });
    gisInited = true;
    maybeEnableButtons();
}

function maybeEnableButtons() {
    if (gapiInited && gisInited) {
        console.log('GAPI initialised')
    }
}
</script>
<template>
  Welcome to {{ 'yourSiteName' }}
  <div @click="signInWithGoogle">
    Continue with google
  </div>
</template>
