<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { avatarText } from '@core/utils/formatters'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import EmployeeTabTranings from '@/views/hr/employee/view/EmployeeTabTranings.vue'
import EmployeeTabRichieste from '@/views/hr/employee/view/EmployeeTabRichieste.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Employee',
  },
})

const { t } = useI18n()
const dipendenteData = ref<any>(null)
const route = useRoute('hr-employee-view-id')
const router = useRouter()
const path = import.meta.env.VITE_BASE_URL_PORTALE || ''
const userTab = ref(null)
const isPersonalDetailsExpanded = ref(true)

const fetchUser = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/hr/dipendenti/view/${route.params.id}`))
  dipendenteData.value = resultData.value
}

fetchUser()

const tabs = [
  { icon: 'tabler-book', title: 'Formazioni' },
  { icon: 'tabler-calendar-event', title: 'Richieste' },
]

// 👉 Status variant resolver
const resolveEmployeeStatusVariant = (dimesso: any) => {
  const isDimesso = dimesso === true || dimesso === 1 || dimesso === '1'
  if (isDimesso)
    return { color: 'error', stato: 'Dimesso' }
  return { color: 'success', stato: 'In Forza' }
}

const resolveCompany = (company: string) => {
  if (company === 'metallurgica')
    return 'Metallurgica Bresciana'
  if (company === 'optotec')
    return 'Optotec'
  return company || '-'
}

const formatGender = (sesso: string) => {
  if (sesso === 'm') return 'Maschio'
  if (sesso === 'f') return 'Femmina'
  return sesso || '-'
}

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString('it-IT')
  } catch (e) {
    return dateStr
  }
}
</script>

<template>
  <div v-if="dipendenteData" class="workspace-container w-100 d-flex flex-column pa-4 gap-4">

    <!-- ==========================================
         HERO BANNER & PROFILE HEADER
         ========================================== -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg overflow-hidden position-relative">
      <!-- Decorative SaaS Background Banner -->
      <div class="profile-banner position-absolute w-100 top-0 left-0" style="height: 120px; z-index: 1;"></div>
      
      <VCardText class="position-relative pt-12 pb-5 px-6">
        <div class="d-flex flex-column flex-sm-row align-center align-sm-start gap-4 text-center text-sm-left">
          
          <!-- Avatar Overlapping -->
          <VAvatar
            rounded="circle"
            size="110"
            color="primary"
            variant="flat"
            class="avatar-overlapping me-1"
          >
            <VImg
              v-if="dipendenteData.avatar"
              :src="path + dipendenteData.avatar"
            />
            <span v-else class="text-h4 font-weight-semibold text-white">
              {{ avatarText(dipendenteData.nome_completo) }}
            </span>
          </VAvatar>

          <!-- Employee Quick Info -->
          <div class="flex-grow-1 d-flex flex-column gap-1 pt-sm-2" style="z-index: 2;">
            <div class="d-flex flex-wrap align-center justify-center justify-sm-start gap-2">
              <h1 class="text-h4 font-weight-semibold text-high-emphasis">
                {{ dipendenteData.nome_completo }}
              </h1>
              
              <!-- Dimesso/In forza status -->
              <VChip
                label
                size="small"
                :color="resolveEmployeeStatusVariant(dipendenteData.dimesso).color"
                class="font-weight-medium"
              >
                {{ resolveEmployeeStatusVariant(dipendenteData.dimesso).stato }}
              </VChip>
            </div>

            <!-- List of Job Roles as tags -->
            <div class="d-flex flex-wrap justify-center justify-sm-start gap-1 mt-1">
              <VChip
                v-for="role in (dipendenteData.roles || [])"
                :key="role.id"
                label
                size="small"
                color="secondary"
                variant="tonal"
                class="font-weight-medium text-capitalize"
              >
                <VIcon icon="tabler-id-badge" size="14" class="me-1" />
                {{ role.ruolo }}
              </VChip>
              <div v-if="!(dipendenteData.roles || []).length" class="text-caption text-disabled italic">
                Nessun ruolo assegnato
              </div>
            </div>

            <!-- Quick stats meta row -->
            <div class="d-flex flex-wrap align-center justify-center justify-sm-start gap-x-5 gap-y-1 mt-3 text-caption text-medium-emphasis">
              <span class="d-flex align-center gap-1">
                <VIcon icon="tabler-hash" size="16" color="primary" />
                <strong>Matricola:</strong> {{ dipendenteData.matricola }}
              </span>
              <span class="d-flex align-center gap-1">
                <VIcon icon="tabler-hierarchy-3" size="16" color="primary" />
                <strong>Reparto:</strong> {{ dipendenteData.department ? dipendenteData.department.reparto : '-' }}
              </span>
              <span class="d-flex align-center gap-1">
                <VIcon icon="tabler-building" size="16" color="primary" />
                <strong>Azienda:</strong> {{ resolveCompany(dipendenteData.company_id) }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="d-flex gap-2 align-self-center align-self-sm-end mt-4 mt-sm-0" style="z-index: 2;">
            <VBtn
              variant="outlined"
              color="secondary"
              density="comfortable"
              prepend-icon="tabler-arrow-left"
              :to="{ name: 'hr-employee-list' }"
            >
              Torna alla Lista
            </VBtn>
            <VBtn
              v-if="$can(DefineAbilities.employee_admin.action, DefineAbilities.employee_admin.subject)"
              variant="flat"
              color="primary"
              density="comfortable"
              prepend-icon="tabler-user-edit"
              :to="{ name: 'hr-employee-edit-id', params: { id: dipendenteData.id } }"
            >
              Modifica
            </VBtn>
          </div>

        </div>
      </VCardText>
    </VCard>

    <!-- ==========================================
         GRID LAYOUT: DETAILS & OPERATIONS
         ========================================== -->
    <VRow>

      <!-- LEFT COLUMN: Personal details card (Expanded State) -->
      <VCol v-if="isPersonalDetailsExpanded" cols="12" md="4" lg="3">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="pa-4 d-flex flex-column gap-4">
            <div class="d-flex align-center justify-space-between">
              <div>
                <h3 class="text-h6 font-weight-semibold text-high-emphasis">Dati Personali</h3>
                <p class="text-caption text-medium-emphasis mb-0">Informazioni e recapiti</p>
              </div>
              <IconBtn @click="isPersonalDetailsExpanded = false">
                <VIcon icon="tabler-chevron-left" />
                <VTooltip text="Riduci Dati Personali" activator="parent" location="top" />
              </IconBtn>
            </div>
            <VDivider />

            <!-- Personal Data List -->
            <div class="d-flex flex-column gap-3">
              <!-- Sesso -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis d-flex align-center gap-1">
                  <VIcon icon="tabler-gender-bigender" size="16" /> Sesso
                </span>
                <span class="font-weight-medium text-high-emphasis">{{ formatGender(dipendenteData.sesso) }}</span>
              </div>

              <!-- Data Nascita -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis d-flex align-center gap-1">
                  <VIcon icon="tabler-cake" size="16" /> Data di Nascita
                </span>
                <span class="font-weight-medium text-high-emphasis">{{ formatDate(dipendenteData.data_nascita) }}</span>
              </div>

              <!-- Data Assunzione -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis d-flex align-center gap-1">
                  <VIcon icon="tabler-calendar" size="16" /> Data Assunzione
                </span>
                <span class="font-weight-medium text-high-emphasis">{{ formatDate(dipendenteData.data_assunzione) }}</span>
              </div>

              <!-- Centro di Costo -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis d-flex align-center gap-1">
                  <VIcon icon="tabler-coin" size="16" /> Centro di Costo
                </span>
                <span class="font-weight-medium text-high-emphasis text-right" style="max-width: 60%">
                  {{ dipendenteData.center_cost ? dipendenteData.center_cost.centro_di_costo : '-' }}
                </span>
              </div>
            </div>

            <VDivider />

            <!-- Contacts Section -->
            <div class="d-flex flex-column gap-3">
              <!-- Email -->
              <div class="d-flex flex-column gap-1 text-body-2">
                <span class="text-medium-emphasis d-flex align-center gap-1">
                  <VIcon icon="tabler-mail" size="16" /> Email
                </span>
                <span class="font-weight-medium text-high-emphasis text-truncate">{{ dipendenteData.email || '-' }}</span>
              </div>

              <!-- Telefono Pers -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis d-flex align-center gap-1">
                  <VIcon icon="tabler-phone" size="16" /> Telefono
                </span>
                <span class="font-weight-medium text-high-emphasis">{{ dipendenteData.tel || '-' }}</span>
              </div>

              <!-- Telefono Az -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis d-flex align-center gap-1">
                  <VIcon icon="tabler-phone-call" size="16" /> Cell. Aziendale
                </span>
                <span class="font-weight-medium text-high-emphasis">{{ dipendenteData.tel_az || '-' }}</span>
              </div>
            </div>

            <VDivider />

            <!-- Medical Visits Section -->
            <div class="d-flex flex-column gap-3">
              <div>
                <h4 class="text-subtitle-2 font-weight-semibold text-high-emphasis mb-1">Idoneità Sanitaria</h4>
                <p class="text-caption text-medium-emphasis mb-0">Visite mediche periodiche</p>
              </div>

              <!-- Ultima Visita -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis">Ultima Visita</span>
                <span class="font-weight-medium text-high-emphasis">{{ formatDate(dipendenteData.data_ultima_visita) }}</span>
              </div>

              <!-- Frequenza Anni -->
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis">Periodicità</span>
                <span class="font-weight-medium text-high-emphasis">{{ dipendenteData.numero_anni_visita_medica }} Anni</span>
              </div>

              <!-- Scadenza Visita -->
              <div class="d-flex justify-space-between align-center text-body-2 mt-1">
                <span class="text-medium-emphasis">Scadenza Idoneità</span>
                <VChip
                  label
                  size="small"
                  color="success"
                  class="font-weight-medium"
                >
                  {{ formatDate(dipendenteData.data_scadenza_visita) }}
                </VChip>
              </div>
            </div>

          </VCardText>
        </VCard>
      </VCol>

      <!-- LEFT COLUMN (Collapsed State) -->
      <VCol v-else cols="12" md="1" class="d-flex justify-center">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg w-100 d-flex flex-column align-center py-4 gap-4" style="min-height: 450px;">
          <IconBtn @click="isPersonalDetailsExpanded = true" color="primary" variant="tonal">
            <VIcon icon="tabler-chevron-right" />
            <VTooltip text="Espandi Dati Personali" activator="parent" location="right" />
          </IconBtn>
          <VDivider class="w-100" />
          <VIcon icon="tabler-user" class="text-medium-emphasis mt-2" />
          <VIcon icon="tabler-mail" class="text-medium-emphasis" />
          <VIcon icon="tabler-phone" class="text-medium-emphasis" />
          <VIcon icon="tabler-heart-rate-monitor" class="text-medium-emphasis" />
        </VCard>
      </VCol>

      <!-- RIGHT COLUMN: Tabbed operations (trainings, etc.) -->
      <VCol cols="12" :md="isPersonalDetailsExpanded ? 8 : 11" :lg="isPersonalDetailsExpanded ? 9 : 11">

        <!-- Tabs Selector -->
        <VTabs v-model="userTab" class="v-tabs-pill mb-4">
          <VTab v-for="tab in tabs" :key="tab.icon">
            <VIcon :size="18" :icon="tab.icon" class="me-2" />
            <span class="font-weight-medium">{{ tab.title }}</span>
          </VTab>
        </VTabs>

        <!-- Tab Contents Wrapper -->
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg pa-4">
          <VWindow v-model="userTab" class="disable-tab-transition" :touch="false">
            <VWindowItem>
              <!-- Formazioni list component -->
              <EmployeeTabTranings :id="route.params.id" />
            </VWindowItem>
            <VWindowItem>
              <!-- Richieste (ferie, permessi, 104) -->
              <EmployeeTabRichieste :id="route.params.id" />
            </VWindowItem>
          </VWindow>
        </VCard>

      </VCol>

    </VRow>
  </div>
  <VCard v-else class="pa-12 text-center border-thin rounded-lg" variant="outlined">
    <VProgressCircular indeterminate color="primary" />
    <div class="mt-4 text-medium-emphasis">Caricamento scheda dipendente in corso...</div>
  </VCard>
</template>

<style lang="scss" scoped>
.profile-banner {
  background: linear-gradient(135deg, rgba(var(--v-theme-primary), 0.15) 0%, rgba(var(--v-theme-primary), 0.04) 100%);
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

.avatar-overlapping {
  border: 4px solid rgb(var(--v-theme-surface)) !important;
  margin-top: -45px;
  z-index: 2;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

@media (max-width: 599px) {
  .avatar-overlapping {
    margin-top: -55px;
  }
}
</style>
