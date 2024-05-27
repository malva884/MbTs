<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import IsOnLine from '@/views/administrations/user/IsOnLine.vue'
import AddNewUserDrawer from '@/views/administrations/user/AddNewUserDrawer.vue'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import {VForm} from "vuetify/components/VForm";

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
let totalUsers = ref(0)
const message = ref('')
const color = ref('')
const isSnackbarScrollReverseVisible = ref(false)

const path = import.meta.env.VITE_BASE_URL_PORTALE

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
    totalUsers = usersData.value.total
  }
  else {
    users.value = []
    totalUsers = 0
  }
  loading.value = false
}


const { data: usersOnline } = await useApi<any>(createUrl('/users/usersOnline'))

const totalUsersOnline = usersOnline.value.online

const { data: totalUsersResult } = await useApi<any>(createUrl('/users/totalUsers'))
const totalUsersSystem = totalUsersResult.value.totalUsers

const { data: totalUsersActivityResult } = await useApi<any>(createUrl('/users/totalUsers', {
  query: {
    activity: true,
  },
}))

const totalUsersActivitySystem = totalUsersActivityResult.value.totalUsers

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

const widgetData = ref([
  { title: t('Label.Online'), value: `${totalUsersOnline} / ${totalUsersActivitySystem}`, desc: 'Total Users on-line', icon: 'tabler-users-group', iconColor: 'primary' },
  {
    title: t('Label.Uenti-Sistema'),
    value: totalUsersSystem,
    desc: 'Total Users System',
    icon: 'tabler-user-plus',
    iconColor: 'error',
  },
  {
    title: t('Label.Uenti-Attivi'),
    value: totalUsersActivitySystem,
    change: -14,
    desc: 'Total Users Activity System',
    icon: 'tabler-user-check',
    iconColor: 'success',
  },
  {
    title: 'Pending Users',
    value: '237',
    change: 42,
    desc: 'Last Week Analytics',
    icon: 'tabler-user-exclamation',
    iconColor: 'warning',
  },
])
</script>

<template>
  <section>
    <!-- 👉 Widgets -->
    <div class="d-flex mb-6">
      <VRow>
        <template
          v-for="(data, id) in widgetData"
          :key="id"
        >
          <VCol
            cols="12"
            md="3"
            sm="6"
          >
            <VCard>
              <VSnackbar
                v-model="isSnackbarScrollReverseVisible"
                transition="scroll-y-reverse-transition"
                location="top central"
                :color="color"
              >
                {{ $t(message) }}
              </VSnackbar>
              <VCardText>
                <div class="d-flex justify-space-between">
                  <div class="d-flex flex-column gap-y-1">
                    <span class="text-body-1 text-medium-emphasis">{{ data.title }}</span>
                    <div>
                      <h4 class="text-h4">
                        {{ data.value }}
                        <!--
                          span
                          class="text-base "
                          :class="data.change > 0 ? 'text-success' : 'text-error'"
                          >({{ prefixWithPlus(data.change) }}%)</span
                        -->
                      </h4>
                    </div>
                    <span class="text-sm">{{ data.desc }}</span>
                  </div>
                  <VAvatar
                    :color="data.iconColor"
                    variant="tonal"
                    rounded
                    size="38"
                  >
                    <VIcon
                      :icon="data.icon"
                      size="26"
                    />
                  </VAvatar>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </template>
      </VRow>
    </div>

    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Full Name -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="userFilter"
              :label="$t('Label.User')"
              :placeholder="$t('Label.User')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Select Role -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="selectedRole"
              :label="$t('Label.Seleziona Ruolo')"
              placeholder="Select Role"
              :items="roles"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Select Status -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="selectedStatus"
              :label="$t('Label.Seleziona Stato')"
              placeholder="Select Status"
              :items="status"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VCard>
      <VCardText class="d-flex flex-wrap py-4 gap-4">


        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Export button -->
          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-screen-share"
          >
            Export
          </VBtn>

          <!-- 👉 Add user button -->
          <VBtn
            prepend-icon="tabler-plus"
            @click="isAddNewUserDrawerVisible = true"
            @user-data="addNewUser"
          >
            Add New User
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- SECTION datatable -->
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
                :src="path + item.avatar"
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

                  <VListItemTitle>View</VListItemTitle>
                </VListItem>

                <VListItem
                  v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"

                >
                  <template #prepend>
                    <VIcon icon="tabler-pencil" />
                  </template>
                  <VListItemTitle>Edit</VListItemTitle>
                </VListItem>

                <VListItem
                  v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                  @click="deleteUser(item.id)"
                >
                  <template #prepend>
                    <VIcon icon="tabler-trash" />
                  </template>
                  <VListItemTitle>Delete</VListItemTitle>
                </VListItem>
              </VList>
            </VMenu>
          </VBtn>
        </template>
      </VDataTableServer>
      <!-- SECTION -->
    </VCard>
    <!-- 👉 Add New User -->
    <AddNewUserDrawer
      v-model:isDrawerOpen="isAddNewUserDrawerVisible"
      @user-data="addNewUser"
    />
  </section>

  <VDialog
    v-model="resetPasswordDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      title="Reset Password"
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
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="resetPassword"
          >
            Save
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
