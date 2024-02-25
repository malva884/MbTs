<script setup lang="ts">
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import UserInfoEdit from "@/views/administrations/user/view/UserInfoEdit.vue"

interface Props {
  userData: {
    id: number
    full_name: string
    nome: string
    cognome: string
    role: string
    mobile: string
    email: string
    interno: string
    stato: string
    avatar: string
    lingua: number
    sesso: string

  }
}

const props = defineProps<Props>()
const path = import.meta.env.VITE_BASE_URL
const isSnackbarScrollReverseVisible = ref(false)

const isUserInfoEditDialogVisible = ref(false)
const message = ref('')
const color = ref('')

// 👉 Status variant resolver
const resolveUserStatusVariant = (stat: number) => {
  const statLowerCase = stat

  // eslint-disable-next-line eqeqeq
  if (statLowerCase == 10)
    return { color: 'warning', stato: 'aa' }
  // eslint-disable-next-line eqeqeq
  if (statLowerCase == 1)
    return { color: 'success', stato: 'Attivo' }
  // eslint-disable-next-line eqeqeq
  if (statLowerCase == 0)
    return { color: 'secondary', stato: 'Disattivo' }

  return { color: 'primary', stato: '-' }
}

// 👉 Role variant resolver
const resolveUserRoleVariant = (role: string) => {
  if (role === 'super admin')
    return {color: 'warning', icon: 'tabler-brand-ubuntu'}
  if (role === 'admin')
    return {color: 'secondary', icon: 'tabler-device-laptop'}

  return {color: 'primary', icon: 'tabler-user'}
}

const resolveUserLanguageVariant = (lingua: string) => {
  if (lingua === 'ita')
    return 'Italiano'
  if (lingua === 'eng')
    return 'Inglese'
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

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.userData">
        <VSnackbar
          v-model="isSnackbarScrollReverseVisible"
          transition="scroll-y-reverse-transition"
          location="top central"
          :color="color"
        >
          {{ $t(message) }}
        </VSnackbar>
        <VCardText class="text-center pt-15">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="100"
            :color="!props.userData.avatar ? 'primary' : undefined"
            :variant="!props.userData.avatar ? 'tonal' : undefined"
          >
            <VImg
              v-if="props.userData.avatar"
              :src="path+props.userData.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(props.userData.full_name) }}
            </span>
          </VAvatar>

          <!-- 👉 User fullName -->
          <h6 class="text-h4 mt-4">
            {{ props.userData.full_name }}
          </h6>

          <!-- 👉 Role chip -->
          <VChip
            label
            :color="resolveUserRoleVariant(props.userData.role).color"
            size="small"
            class="text-capitalize mt-3"
          >
            {{ props.userData.role }}
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
                  {{ $t('Label.Nome') }}:
                  <span class="text-body-1">
                    {{ props.userData.nome }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Cognome') }}:
                  <span class="text-body-1">
                    {{ props.userData.cognome }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.E-mail') }}:
                  <span class="text-body-1">{{ props.userData.email }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Stato') }}:

                  <VChip
                    label
                    size="small"
                    :color="resolveUserStatusVariant(props.userData.stato).color"
                    class="text-capitalize"
                  >
                    {{ resolveUserStatusVariant(props.userData.stato).stato }}
                  </VChip>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Sesso') }}:
                  <span class="text-body-1" v-if="props.userData.sesso === 'm'">
                    Maschio
                  </span>
                  <span class="text-body-1" v-else>
                    Femmina
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Cell') }}:
                  <span class="text-body-1">
                    {{ props.userData.mobile }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Interno') }}:
                  <span class="text-body-1">{{ props.userData.interno }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Lingua') }}:
                  <span class="text-body-1">{{ resolveUserLanguageVariant(props.userData.lingua) }}</span>
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
              @click="isUserInfoEditDialogVisible = true"
              @user-data="editUser"
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
