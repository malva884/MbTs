<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface PermissionUser {
  id: string
  full_name: string
  username: string
  source: 'direct' | 'admin'
}

interface PermissionDetail {
  name: string
  exists: boolean
  users: PermissionUser[]
  has_admin: boolean
  user_count: number
}

interface ModulePermissions {
  module: string
  module_name: string
  permissions: Record<string, PermissionDetail>
}

definePage({
  meta: {
    action: 'read',
    subject: 'Permessi',
  },
})

const loading = ref(true)
const modules = ref<ModulePermissions[]>([])
const search = ref('')
const selectedModule = ref('')
const selectedPermission = ref('')

const permissionTypes = ['admin', 'list', 'create', 'edit', 'read', 'import', 'sing', 'report', 'deleted']

const fetchPermissionsOverview = async () => {
  loading.value = true
  try {
    const { data } = await useApi<{ data: ModulePermissions[] }>('/admin/permissions/overview')
    modules.value = data.value?.data ?? []
  } catch (error) {
    console.error('Error fetching permissions overview:', error)
  } finally {
    loading.value = false
  }
}

const filteredModules = computed(() => {
  let result = modules.value

  if (selectedModule.value) {
    result = result.filter(m => m.module === selectedModule.value)
  }

  if (search.value) {
    const searchLower = search.value.toLowerCase()
    result = result.map(module => {
      const filteredPermissions: Record<string, PermissionDetail> = {}
      for (const [key, permission] of Object.entries(module.permissions)) {
        const usersMatch = permission.users.some(u => 
          u.full_name.toLowerCase().includes(searchLower) ||
          u.username.toLowerCase().includes(searchLower)
        )
        if (usersMatch || permission.name.toLowerCase().includes(searchLower)) {
          filteredPermissions[key] = permission
        }
      }
      return {
        ...module,
        permissions: filteredPermissions
      }
    }).filter(m => Object.keys(m.permissions).length > 0)
  }

  return result
})

const moduleOptions = computed(() => {
  return modules.value.map(m => ({
    title: m.module,
    value: m.module
  }))
})

const getSourceColor = (source: string) => {
  return source === 'admin' ? 'warning' : 'success'
}

const getSourceText = (source: string) => {
  return source === 'admin' ? 'Admin' : 'Diretto'
}

onMounted(fetchPermissionsOverview)
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-shield-check" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ t('Label.Permessi-Overview') }}</div>
            <div class="text-caption text-medium-emphasis">Visualizzazione permessi utenti per modulo</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <VTextField
              v-model="search"
              :label="t('Label.Cerca')"
              prepend-inner-icon="tabler-search"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <VSelect
              v-model="selectedModule"
              :label="t('Label.Modulo')"
              :items="moduleOptions"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VProgressLinear v-if="loading" indeterminate color="primary" />

    <VCard
      v-for="module in filteredModules"
      :key="module.module"
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="pa-4">
        <div class="d-flex align-center justify-space-between mb-4">
          <div class="text-h6 font-weight-medium">{{ module.module }}</div>
          <VChip size="small" color="primary">
            {{ Object.keys(module.permissions).length }} permessi
          </VChip>
        </div>

        <VRow>
          <VCol
            v-for="(permission, permKey) in module.permissions"
            :key="permKey"
            cols="12"
            sm="6"
            md="4"
          >
            <VCard variant="outlined" class="border-thin">
              <VCardText class="pa-3">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="text-subtitle-2 font-weight-medium">
                    {{ permKey }}
                  </div>
                  <VChip
                    v-if="permission.has_admin"
                    size="x-small"
                    color="warning"
                  >
                    Admin presente
                  </VChip>
                </div>

                <div class="d-flex align-center gap-2 mb-2">
                  <VIcon
                    :icon="permission.exists ? 'tabler-check' : 'tabler-x'"
                    :color="permission.exists ? 'success' : 'error'"
                    size="16"
                  />
                  <span class="text-caption text-medium-emphasis">
                    {{ permission.exists ? 'Esistente' : 'Mancante' }}
                  </span>
                </div>

                <div class="text-caption mb-2">
                  <strong>{{ permission.user_count }}</strong> utenti
                </div>

                <div v-if="permission.users.length > 0" class="d-flex flex-wrap gap-1">
                  <VChip
                    v-for="user in permission.users"
                    :key="user.id"
                    size="x-small"
                    :color="getSourceColor(user.source)"
                  >
                    {{ user.full_name }}
                    <VIcon
                      v-if="user.source === 'admin'"
                      icon="tabler-crown"
                      size="12"
                      class="ml-1"
                    />
                  </VChip>
                </div>
                <div v-else class="text-caption text-medium-emphasis">
                  Nessun utente
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
