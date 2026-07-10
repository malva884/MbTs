<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const router = useRouter()
const ability = useAbility()
const path = import.meta.env.VITE_BASE_URL_PORTALE

// TODO: Get type from backend
const userData = useCookie<any>('userData')

// Verifica se l'utente sta impersonando qualcuno
const isImpersonating = ref(localStorage.getItem('isImpersonating') === 'true')

const logout = async () => {
  // Remove "accessToken" from cookie
  useCookie('accessToken').value = null
  useCookie('expiredToken').value = null

  // Remove "userData" from cookie
  userData.value = null

  // Remove impersonation state from localStorage
  localStorage.removeItem('isImpersonating')
  localStorage.removeItem('originalToken')
  localStorage.removeItem('originalPermissions')

  // Redirect to login page
  await router.push('/login')

  // ℹ️ We had to remove abilities in then block because if we don't nav menu items mutation is visible while redirecting user to login page
  // Remove "userAbilities" from localStorage
  localStorage.removeItem('userAbilityRules')

  // Reset ability to initial ability
  ability.update([])
}

const leaveImpersonation = async () => {
  // Prima controlla se c'è un token originale salvato
  const originalToken = localStorage.getItem('originalToken')
  
  if (!originalToken) {
    // Se non c'è token originale, significa che non c'è una sessione di impersonazione valida
    localStorage.removeItem('isImpersonating')
    isImpersonating.value = false
    return
  }

  try {
    const res = await $api('/admin/leave-impersonation', {
      method: 'POST',
    })

    if (res.success) {
      // Ripristina il token originale salvato
      useCookie('accessToken').value = originalToken
      localStorage.removeItem('originalToken')

      // Rimuovi lo stato di impersonazione
      localStorage.removeItem('isImpersonating')
      isImpersonating.value = false

      // Aggiorna i dati utente con quelli dell'admin
      useCookie('userData').value = res.user

      // Ripristina i permessi originali dell'admin
      const originalPermissions = localStorage.getItem('originalPermissions')
      if (originalPermissions) {
        localStorage.setItem('userAbilityRules', originalPermissions)
        ability.update(JSON.parse(originalPermissions))
        localStorage.removeItem('originalPermissions')
      }

      // Ricarica la pagina per applicare i nuovi dati utente
      window.location.reload()
    }
  }
  catch (error) {
    // Se c'è un errore (es. 400 = nessuna sessione attiva), ripristina comunque il token originale
    useCookie('accessToken').value = originalToken
    localStorage.removeItem('originalToken')
    // Pulisci lo stato di impersonazione
    localStorage.removeItem('isImpersonating')
    isImpersonating.value = false
    // Ripristina i permessi originali dell'admin
    const originalPermissions = localStorage.getItem('originalPermissions')
    if (originalPermissions) {
      localStorage.setItem('userAbilityRules', originalPermissions)
      ability.update(JSON.parse(originalPermissions))
      localStorage.removeItem('originalPermissions')
    }
    // Ricarica la pagina per applicare il token originale
    window.location.reload()
  }
}

const userProfileList = computed(() => {
  const list = [
    { type: 'divider' },
    { type: 'navItem', icon: 'tabler-settings', title: 'Settings', to: { name: 'user-tab', params: { tab: 'account' } } },
  ]

  // Aggiungi pulsante per uscire dall'impersonazione se attivo
  if (isImpersonating.value) {
    list.push(
      { type: 'divider' },
      { type: 'navItem', icon: 'tabler-logout', title: 'Esci da Impersonazione', onClick: leaveImpersonation, color: 'warning' }
    )
  }

  list.push(
    { type: 'divider' },
    { type: 'navItem', icon: 'tabler-logout', title: 'Logout', onClick: logout }
  )

  return list
})
</script>

<template>
  <VBadge
      v-if="userData"
      dot
      bordered
      location="bottom right"
      offset-x="3"
      offset-y="3"
      :color="isImpersonating ? 'warning' : 'success'"
  >
    <VAvatar
        class="cursor-pointer"
        :color="!(userData && userData.avatar) ? 'primary' : undefined"
        :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
    >
      <VImg
          v-if="userData && userData.avatar"
          :src="path + userData.avatar"
      />
      <VIcon
          v-else
          icon="tabler-user"
      />

      <!-- SECTION Menu -->
      <VMenu
          activator="parent"
          width="230"
          location="bottom end"
          offset="14px"
      >
        <VList>
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                    dot
                    location="bottom right"
                    offset-x="3"
                    offset-y="3"
                    :color="isImpersonating ? 'warning' : 'success'"
                    bordered
                >
                  <VAvatar
                      :color="!(userData && userData.avatar) ? 'primary' : undefined"
                      :variant="!(userData && userData.avatar) ? 'tonal' : undefined"
                  >
                    <VImg
                        v-if="userData && userData.avatar"
                        :src="path + userData.avatar"
                    />
                    <VIcon
                        v-else
                        icon="tabler-user"
                    />
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            <VListItemTitle class="font-weight-medium">
              {{ userData.fullName || userData.username }}
            </VListItemTitle>
            <VListItemSubtitle>
              {{ userData.role }}
              <VChip
                  v-if="isImpersonating"
                  size="x-small"
                  color="warning"
                  class="ml-2"
              >
                Impersonation
              </VChip>
            </VListItemSubtitle>
          </VListItem>

          <PerfectScrollbar :options="{ wheelPropagation: false }">
            <template
                v-for="item in userProfileList"
                :key="item.title"
            >
              <VListItem
                  v-if="item.type === 'navItem'"
                  :to="item.to"
                  @click="item.onClick && item.onClick()"
              >
                <template #prepend>
                  <VIcon
                      class="me-2"
                      :icon="item.icon"
                      size="22"
                      :color="item.color"
                  />
                </template>

                <VListItemTitle>{{ item.title }}</VListItemTitle>

                <template
                    v-if="item.badgeProps"
                    #append
                >
                  <VBadge v-bind="item.badgeProps" />
                </template>
              </VListItem>

              <VDivider
                  v-else
                  class="my-2"
              />
            </template>
          </PerfectScrollbar>
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
</template>
