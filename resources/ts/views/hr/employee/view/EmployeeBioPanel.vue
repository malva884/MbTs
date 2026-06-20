<script setup lang="ts">
import DefineAbilities from '@/plugins/casl/DefineAbilities'

interface Props {
  employeeData: {
    id: string
    nome: string
    cognome: string
    nome_completo: string
    email: string
    data_assunzione: string
    data_nascita: string
    data_ultima_visita: string
    numero_anni_visita_medica: number
    tel: string
    tel_az: string
    dimesso: boolean
    valutatore: boolean
    ruolo_id: nnumber
    reparto_id: number
    centro_id: string
    matricola: string
    sesso: string
    company_id: string
    data_scadenza_visita: sreing
  }
}

interface Emit {
  (e: 'update:hidden', value: boolean): void
}

const emit = defineEmits<Emit>()
const props = defineProps<Props>()
const path = import.meta.env.VITE_BASE_URL_PORTALE
const isSnackbarScrollReverseVisible = ref(false)

const isUserInfoEditDialogVisible = ref(false)
const message = ref('')
const color = ref('')

// 👉 Status variant resolver
const resolveEmploueeStatusVariant = (stat: number) => {
  const statLowerCase = stat

  // eslint-disable-next-line eqeqeq
  if (statLowerCase == 1)
    return { color: 'error', stato: 'Si' }
  // eslint-disable-next-line eqeqeq
  if (statLowerCase == 0)
    return { color: 'success', stato: 'No' }

  return { color: 'primary', stato: '-' }
}

const resolveCompany = (compaby: string) => {
  if (compaby === 'metallurgica')
    return 'Metallurgica Brasciana'
  if (compaby === 'optotec')
    return 'Optotec'
}

const editUser = async (userData: object) => {
  const retuenData = await $api(`/users/edit/${userData['id']}`, {
    method: 'POST',
    body: userData,
  })

  // eslint-disable-next-line vue/no-mutating-props
  props.userData.nome = userData['nome']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.cognome = userData['cognome']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.full_name = userData['nome'] +' '+userData['cognome']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.stato = userData['stato']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.email = userData['email']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.sesso = userData['sesso']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.role = userData['role']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.mobile = userData['mobile']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.interno = userData['interno']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.lingua = userData['lingua']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.username = userData['username']
  // eslint-disable-next-line vue/no-mutating-props
  props.userData.company_id = userData['company_id']

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}

const hiddenEmploee = () => {
  emit('update:hidden', true)
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.employeeData">
        <VSnackbar
          v-model="isSnackbarScrollReverseVisible"
          transition="scroll-y-reverse-transition"
          location="top central"
          :color="color"
        >
          {{ $t(message) }}
        </VSnackbar>
        <VBtn
          variant="text"
          icon="tabler-user-off"
          @click="hiddenEmploee"
        />
        <VCardText class="text-center pt-5">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="50"
            :color="!props.employeeData.avatar ? 'primary' : undefined"
            :variant="!props.employeeData.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="props.employeeData.avatar"
              :src="path+props.employeeData.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.employeeData.nome_completo) }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h6 class="text-h5 mt-2">
            {{ props.employeeData.nome_completo }}
          </h6>

          <!-- 👉 Role chip -->
          <VChip
            label
            size="small"
            color="warning"
            class="text-capitalize"
          >
            {{ resolveCompany(props.employeeData.company_id) }}
          </VChip>
        </VCardText>

        <VDivider />

        <!-- 👉 Details -->
        <VCardText>
          <p class="text-sm text-uppercase text-disabled">
            {{ $t('Label.Dettaglio') }}
          </p>

          <!-- 👉 User Details list -->
          <VList class="card-list mt-2">
            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Data-Assunzione') }}:
                  <span class="text-body-1">{{ props.employeeData.data_assunzione }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Nome') }}:
                  <span class="text-body-1">
                    {{ props.employeeData.nome }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Cognome') }}:
                  <span class="text-body-1">
                    {{ props.employeeData.cognome }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Matricola') }}:
                  <span class="text-body-1">
                    {{ props.employeeData.matricola }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Data-Di-Nascita') }}:
                  <span class="text-body-1">
                    {{ props.employeeData.data_nascita }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.E-mail') }}:
                  <span class="text-body-1">{{ props.employeeData.email }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Reparto') }}:
                  <span class="text-body-1">{{ props.employeeData.reparto_id }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Ruolo') }}:
                  <span class="text-body-1">
                    {{ props.employeeData.ruolo_id }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Telefono') }}:
                  <span class="text-body-1">
                    {{ props.employeeData.tel }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Validita-Visita-Medica') }}:
                  <span class="text-body-1">
                    {{ props.employeeData.data_scadenza_visita }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Dimesso') }}:

                  <VChip
                    label
                    size="small"
                    :color="resolveEmploueeStatusVariant(props.employeeData.dimesso).color"
                    class="text-capitalize"
                  >
                    {{ resolveEmploueeStatusVariant(props.employeeData.dimesso).stato }}
                  </VChip>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>

        <!-- 👉 Edit and Suspend button -->
        <VCardText class="d-flex justify-center" v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)">

          <VBtn
            variant="elevated"
            class="me-4"
            :to="{ name: 'hr-employee-edit-id', params: { id: props.employeeData.id } }"
          >
            {{ $t('Button.Edit') }}
          </VBtn>

        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

  </VRow>

  <!-- 👉 Edit user info dialog -->
  <UserInfoEditDialog
    v-model:isDrawerOpen="isUserInfoEditDialogVisible"
    :user-data="props.userData"
    @user-data="editUser"
  />

  <!-- 👉 Upgrade plan dialog -->
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.75rem;
}

.text-capitalize {
  text-transform: capitalize !important;
}
</style>
