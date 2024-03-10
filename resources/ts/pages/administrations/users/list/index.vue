<script setup lang="ts">
import { VDataTableServer } from 'vuetify/components/VDataTable'
import { useI18n } from 'vue-i18n'
import IsOnLine from '@/views/administrations/user/IsOnLine.vue'
import AddNewUserDrawer from '@/views/administrations/user/AddNewUserDrawer.vue'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

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
const selectedPlan = ref()
const selectedStatus = ref()

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const users = ref({})
let totalUsers = ref(0)
const message = ref('')
const color = ref('')
const isSnackbarScrollReverseVisible = ref(false)

const path = import.meta.env.VITE_BASE_URL

// Update data table options
const updateOptions = (options: any) => {
  page.value = options.page
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
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

const fetchUsers = async () => {
  const usersData = await useApi<any>(createUrl('/users/', {
    query: {
      q: searchQuery,
      status: selectedStatus,
      plan: selectedPlan,
      role: selectedRole,
      itemsPerPage,
      page,
      sortBy,
      orderBy,
    },
  }))

  users.value = usersData.data.value.data
  totalUsers = usersData.data.value.total
}

fetchUsers()

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
  { title: 'Author', value: 'author' },
  { title: 'Editor', value: 'editor' },
  { title: 'Maintainer', value: 'maintainer' },
  { title: 'Subscriber', value: 'subscriber' },
]

const plans = [
  { title: 'Basic', value: 'basic' },
  { title: 'Company', value: 'company' },
  { title: 'Enterprise', value: 'enterprise' },
  { title: 'Team', value: 'team' },
]

const status = [
  { title: 'Pending', value: 'pending' },
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' },
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
  fetchUsers()
  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}

// 👉 Delete user
const deleteUser = async (id: number) => {
  await $api(`/users/delete/${id}`, {
    method: 'POST',
  })

  // refetch User
  // TODO: Make this async
  fetchUsers()
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
            />
          </VCol>
          <!-- 👉 Select Plan -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="selectedPlan"
              label="Select Plan"
              placeholder="Select Plan"
              :items="plans"
              clearable
              clear-icon="tabler-x"
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
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VCard>
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <div class="me-3 d-flex gap-3">
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              { value: -1, title: 'All' },
            ]"
            style="inline-size: 6.25rem;"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />
        </div>
        <VSpacer />

        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Search  -->
          <div style="inline-size: 10rem;">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search"
              density="compact"
            />
          </div>

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
                  link
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

        <!-- pagination -->
        <template #bottom>
          <VDivider />
          <div class="d-flex align-center justify-sm-space-between justify-center flex-wrap gap-3 pa-5 pt-3">
            <p class="text-sm text-disabled mb-0" />

            <VPagination
              v-model="page"
              :length="Math.ceil(totalUsers / itemsPerPage)"
              :total-visible="$vuetify.display.xs ? 1 : Math.ceil(totalUsers / itemsPerPage)"
            >
              <template #prev="slotProps">
                <VBtn
                  variant="tonal"
                  color="default"
                  v-bind="slotProps"
                  :icon="false"
                >
                  Previous
                </VBtn>
              </template>

              <template #next="slotProps">
                <VBtn
                  variant="tonal"
                  color="default"
                  v-bind="slotProps"
                  :icon="false"
                >
                  Next
                </VBtn>
              </template>
            </VPagination>
          </div>
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
</template>
