<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import IsOnLine from '@/views/administrations/user/IsOnLine.vue'
import AddNewUserDrawer from '@/views/administrations/user/AddNewUserDrawer.vue'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import {VForm} from "vuetify/components/VForm";
import {ability} from "@/plugins/casl/ability";
import { requiredValidator } from '@core/utils/validators'
import { avatarText } from '@core/utils/formatters'

definePage({
  meta: {
    action: 'read',
    subject: 'Users',
  },
})

const { t } = useI18n()

// 👉 Store
const searchQuery = ref('')
const selectedRole = ref()
const selectedStatus = ref()
const userFilter = ref()

// Data table options
const itemsPerPage = ref(10)
const loading = ref(true)
const resetPasswordDialog = ref(false)
const userrestPassword = ref('')
const newPassword = ref('')
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const users = ref<any>([])
const totalUsers = ref(0)
const message = ref('')
const color = ref('')
const isSnackbarScrollReverseVisible = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const refForm = ref<VForm>()

// Update data table options
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

// Headers
const headers = [
  { title: t('Table.Utenti'), key: 'full_name' },
  { title: t('Table.Email'), key: 'email' },
  { title: t('Table.Acl'), key: 'role' },
  { title: t('Table.Stato'), key: 'stato' },
  { title: t('Table.Online'), key: 'online', sortable: false },
  { title: t('Table.Azzioni'), key: 'actions', sortable: false },
]

const loadItems = async () => {
  loading.value = true
  const {data: usersData} = await useApi<any>(createUrl('/users/', {
    query: {
      q: searchQuery,
      status: selectedStatus,
      user: userFilter.value,
      role: selectedRole.value,
      stato: selectedStatus.value,
      itemsPerPage,
      page,
      sortBy,
      orderBy,
    },
  }))

  if (usersData.value !== null) {
    users.value = usersData.value.data
    totalUsers.value = usersData.value.total
  }
  else {
    users.value = []
    totalUsers.value = 0
  }
  loading.value = false
}

// Fetch statistics data
const { data: usersOnline } = await useApi<any>(createUrl('/users/usersOnline'))
const totalUsersOnline = usersOnline.value?.online || 0

const { data: totalUsersResult } = await useApi<any>(createUrl('/users/totalUsers'))
const totalUsersSystem = totalUsersResult.value?.totalUsers || 0

const { data: totalUsersActivityResult } = await useApi<any>(createUrl('/users/totalUsers', {
  query: {
    activity: true,
  },
}))
const totalUsersActivitySystem = totalUsersActivityResult.value?.totalUsers || 0

// 👉 search filters
const roles = [
  { title: 'Admin', value: 'admin' },
  { title: 'User', value: 'user' },
  { title: 'Super-Admin', value: 'super admin' },
]

const status = [
  { title: 'Active', value: '1' },
  { title: 'Inactive', value: '0' },
]

const resolveUserRoleVariant = (role: string) => {
  const roleLowerCase = role.toLowerCase()

  if (roleLowerCase === 'super admin')
    return { color: 'warning', icon: 'tabler-brand-ubuntu' }
  if (roleLowerCase === 'admin')
    return { color: 'secondary', icon: 'tabler-device-laptop' }

  return { color: 'primary', icon: 'tabler-user' }
}

const resolveUserStatusVariant = (stat: string) => {
  const statLowerCase = stat

  if (statLowerCase === '10')
    return { color: 'warning', stato: 'aa' }
  if (statLowerCase === '1')
    return { color: 'success', stato: 'Attivo' }
  if (statLowerCase === '0')
    return { color: 'secondary', stato: 'Disattivo' }

  return { color: 'primary', stato: '-' }
}

const isAddNewUserDrawerVisible = ref(false)

// 👉 Add new user
const addNewUser = async (userData: object) => {
  const retuenData = await $api('/users/new', {
    method: 'POST',
    body: userData,
  })

  // refetch User
  loadItems()
  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}

// 👉 Delete user
const deleteUser = async (id: number) => {
  await $api(`/users/delete/${id}`, {
    method: 'POST',
  })

  // TODO: Make this async
  loadItems()
}

const openResetPasswordDialog = async (id: number) => {
  userrestPassword.value = id
  resetPasswordDialog.value = true

}

const closeResetPasswordDialog = async () => {
  userrestPassword.value = 0
  newPassword.value = ''
  resetPasswordDialog.value = false

}

const resetPassword = async () => {
  await $api(`/users/reset_password/${userrestPassword.value}`, {
    method: 'POST',
    body:{
      password:newPassword.value,
    }
  })
  resetPasswordDialog.value = false
}

const impersona = async (user: number) => {
  try {
    const rawResponse = await $api(`/admin/impersona/${user}`, {
      method: 'POST',
    })

    // Se la risposta è una stringa, puliscila
    let res = rawResponse
    if (typeof rawResponse === 'string') {
      const jsonStart = rawResponse.indexOf('{')
      const cleanResponse = rawResponse.substring(jsonStart)
      res = JSON.parse(cleanResponse)
    }

    if (res.success) {
      // Salva il token corrente prima di cambiarlo
      const currentToken = useCookie('accessToken').value
      
      if (currentToken) {
        localStorage.setItem('originalToken', currentToken as string)
        localStorage.setItem('isImpersonating', 'true')
      } else {
        return
      }

      // Salva i permessi originali dell'admin
      const originalPermissions = localStorage.getItem('userAbilityRules')
      if (originalPermissions) {
        localStorage.setItem('originalPermissions', originalPermissions)
      }

      // Aggiorna il token con quello dell'utente impersonato
      const accessTokenCookie = useCookie('accessToken')
      accessTokenCookie.value = res.token
      useCookie('userData').value = res.user
      
      // Forza la scrittura della cookie nel browser
      document.cookie = `accessToken=${encodeURIComponent(res.token)}; path=/`

      // Carica i permessi dell'utente impersonato usando fetch diretto
      const permissionsResponse = await fetch('/api/admin/permissions/user_permissions', {
        headers: {
          Authorization: `Bearer ${res.token}`,
          Accept: 'application/json',
        },
      }).then(r => r.json())
      
      if (permissionsResponse) {
        // Rimuovi i permessi correnti
        localStorage.removeItem('userAbilityRules')
        ability.update([])
        
        // Imposta i nuovi permessi
        localStorage.setItem('userAbilityRules', JSON.stringify(permissionsResponse))
        ability.update(permissionsResponse)
      }

      // Reindirizza alla dashboard per applicare i nuovi dati utente
      window.location.href = '/'
    }
    else {
      message.value = res.message || 'Errore durante impersonazione'
      color.value = 'error'
      isSnackbarScrollReverseVisible.value = true
    }
  }
  catch (error) {
    message.value = 'Errore durante impersonazione'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h5 class="text-h4 mb-6">
        {{ $t('Label.Utenti') }}
      </h5>
    </VCol>

    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex align-center justify-space-between flex-wrap gap-4">
          <!-- Statistics -->
          <div class="d-flex gap-4 flex-wrap">
            <div class="d-flex align-center gap-2">
              <VAvatar
                color="primary"
                variant="tonal"
                size="32"
              >
                <VIcon icon="tabler-users-group" size="20" />
              </VAvatar>
              <div>
                <span class="text-xs text-medium-emphasis">{{ $t('Label.Online') }}</span>
                <p class="text-sm font-weight-medium mb-0">{{ totalUsersOnline }} / {{ totalUsersActivitySystem }}</p>
              </div>
            </div>
            <div class="d-flex align-center gap-2">
              <VAvatar
                color="error"
                variant="tonal"
                size="32"
              >
                <VIcon icon="tabler-user-plus" size="20" />
              </VAvatar>
              <div>
                <span class="text-xs text-medium-emphasis">{{ $t('Label.Uenti-Sistema') }}</span>
                <p class="text-sm font-weight-medium mb-0">{{ totalUsersSystem }}</p>
              </div>
            </div>
            <div class="d-flex align-center gap-2">
              <VAvatar
                color="success"
                variant="tonal"
                size="32"
              >
                <VIcon icon="tabler-user-check" size="20" />
              </VAvatar>
              <div>
                <span class="text-xs text-medium-emphasis">{{ $t('Label.Uenti-Attivi') }}</span>
                <p class="text-sm font-weight-medium mb-0">{{ totalUsersActivitySystem }}</p>
              </div>
            </div>
          </div>

          <!-- Filters and Actions -->
          <div class="d-flex align-center gap-4 flex-wrap">
            <AppSelect
              :model-value="itemsPerPage"
              :items="[
                { value: 10, title: '10' },
                { value: 25, title: '25' },
                { value: 50, title: '50' },
                { value: 100, title: '100' },
                { value: -1, title: 'All' },
              ]"
              style="inline-size: 5rem;"
              @update:model-value="itemsPerPage = parseInt($event, 10)"
            />

            <AppTextField
              v-model="userFilter"
              :placeholder="$t('Label.Cerca')"
              density="compact"
              style="inline-size: 12.5rem;"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
            />
            <AppSelect
              v-model="selectedRole"
              :placeholder="$t('Label.Seleziona-Ruolo')"
              density="compact"
              :items="roles"
              clearable
              clear-icon="tabler-x"
              style="inline-size: 10rem;"
              @update:model-value="loadItems"
            />
            <AppSelect
              v-model="selectedStatus"
              :placeholder="$t('Label.Seleziona-Stato')"
              density="compact"
              :items="status"
              clearable
              clear-icon="tabler-x"
              style="inline-size: 10rem;"
              @update:model-value="loadItems"
            />
            <VBtn
              density="default"
              color="secondary"
              prepend-icon="tabler-screen-share"
            >
              {{ $t('Label.Esporta') }}
            </VBtn>
            <VBtn
              density="default"
              prepend-icon="tabler-plus"
              @click="isAddNewUserDrawerVisible = true"
              @user-data="addNewUser"
            >
              {{ $t('Label.Aggiungi-Nuovo-Utente') }}
            </VBtn>
          </div>
        </VCardText>

        <VDivider/>

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items="users"
          :items-length="totalUsers"
          :headers="headers"
          class="text-no-wrap"
          :loading="loading"
          @update:options="updateOptions"
        >
        <!-- User -->
        <template #item.full_name="{ item }">
          <div class="d-flex align-center">
            <VAvatar
              size="34"
              :variant="!item.avatar ? 'tonal' : undefined"
              :color="!item.avatar ? resolveUserRoleVariant(item.role).color : undefined"
              class="me-3"
            >
              <VImg
                v-if="item.avatar"
                :src="item.avatar"
              />

              <span v-else>{{ avatarText(item.full_name) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'administrations-users-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.full_name }}
                </RouterLink>
              </h6>
              <span class="text-sm text-medium-emphasis">{{ item.email }}</span>
            </div>
          </div>
        </template>

        <!-- 👉 Role -->
        <template #item.role="{ item }">
          <div class="d-flex align-center gap-4">
            <VAvatar
              :size="30"
              :color="resolveUserRoleVariant(item.role).color"
              variant="tonal"
            >
              <VIcon
                :size="20"
                :icon="resolveUserRoleVariant(item.role).icon"
              />
            </VAvatar>
            <span class="text-capitalize">{{ item.role }}</span>
          </div>
        </template>

        <!-- online -->
        <template #item.online="{ item }">
          <IsOnLine :id="item.id " />
        </template>

        <!-- Status -->
        <template #item.stato="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.stato).color"
            size="small"
            label
            class="text-capitalize"
          >
            {{ resolveUserStatusVariant(item.stato).stato }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn
            v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
            @click="deleteUser(item.id)"
          >
            <VIcon icon="tabler-trash" />
          </IconBtn>

          <IconBtn>
            <VIcon
              v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"
              icon="tabler-edit"
            />
          </IconBtn>

          <IconBtn>
            <VIcon
              v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"
              icon="tabler-key"
              @click="openResetPasswordDialog(item.id)"
            />
          </IconBtn>

          <IconBtn
            v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"
            @click="impersona(item.id)"
          >
            <VIcon icon="tabler-switch-3" />
          </IconBtn>

          <VBtn
            icon
            variant="text"
            size="small"
            color="medium-emphasis"
          >
            <VIcon
              size="24"
              icon="tabler-dots-vertical"
            />
            <VMenu activator="parent">
              <VList>
                <VListItem :to="{ name: 'apps-user-view-id', params: { id: item.id } }">
                  <template #prepend>
                    <VIcon icon="tabler-eye" />
                  </template>

                  <VListItemTitle>{{ $t('Label.Visualizza') }}</VListItemTitle>
                </VListItem>

                <VListItem
                  v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"

                >
                  <template #prepend>
                    <VIcon icon="tabler-pencil" />
                  </template>
                  <VListItemTitle>{{ $t('Label.Modifica') }}</VListItemTitle>
                </VListItem>

                <VListItem
                  v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                  @click="deleteUser(item.id)"
                >
                  <template #prepend>
                    <VIcon icon="tabler-trash" />
                  </template>
                  <VListItemTitle>{{ $t('Label.Elimina') }}</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </template>
      </VDataTableServer>
    </VCard>
    </VCol>

    <!-- 👉 Add New User -->
    <AddNewUserDrawer
      v-model:isDrawerOpen="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />
  </VRow>

  <!-- Snackbar -->
  <VSnackbar
    v-model="isSnackbarScrollReverseVisible"
    transition="scroll-y-reverse-transition"
    location="top central"
    :color="color"
  >
    {{ $t(message) }}
  </VSnackbar>

  <!-- Reset Password Dialog -->
  <VDialog
    v-model="resetPasswordDialog"
    max-width="500px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="$t('Label.Reimposta-Password')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <VRow>
                <!-- 👉 Password -->
                <VCol cols="12">
                  <AppTextField
                    v-model="newPassword"
                    type="password"
                    :rules="[requiredValidator]"
                    :label="$t('Label.New Password')"
                    :placeholder="$t('Label.New Password')"
                  />
                </VCol>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />

          <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click="closeResetPasswordDialog"
          >
            {{ $t('Label.Annulla') }}
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="resetPassword"
          >
            {{ $t('Label.Salva') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
