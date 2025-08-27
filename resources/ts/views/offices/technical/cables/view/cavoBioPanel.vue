<script setup lang="ts">
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Cavo } from '@/views/offices/technical/cables/type'
import CavoInfoEditDialog from "@/components/dialogs/CavoInfoEditDialog.vue";

interface Props {
  cavoData: Cavo
}

const props = defineProps<Props>()
const path = import.meta.env.VITE_BASE_URL_PORTALE
const isSnackbarScrollReverseVisible = ref(false)

const isCavoInfoEditDialogVisible = ref(false)
const message = ref('')
const color = ref('')

const editCavo = async (cavoData: object) => {
  const retuenData = await $api(`/to/cavi/update/${cavoData['id']}`, {
    method: 'POST',
    body: cavoData,
  })

  // eslint-disable-next-line vue/no-mutating-props
  props.cavoData.codice = retuenData.obj.codice
  // eslint-disable-next-line vue/no-mutating-props
  props.cavoData.descrizione = retuenData.obj.descrizione
  // eslint-disable-next-line vue/no-mutating-props
  props.cavoData.categoria = retuenData.obj.categoria
  // eslint-disable-next-line vue/no-mutating-props
  props.cavoData.categoria_id = retuenData.obj.categoria_id
  // eslint-disable-next-line vue/no-mutating-props
  props.cavoData.categoria_obj.legistrazione = retuenData.obj.categoria_obj.legistrazione

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.cavoData">
        <VSnackbar
          v-model="isSnackbarScrollReverseVisible"
          transition="scroll-y-reverse-transition"
          location="top central"
          :color="color"
        >
          {{ $t(message) }}
        </VSnackbar>
        <VCardText class="text-center pt-15">
          <!-- 👉Codice -->
          <h6 class="text-h4 mt-4">
            {{ props.cavoData.codice }}
          </h6>
        </VCardText>
        <VCardText class="text-center pt-15">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="100"
            variant="tonal"
          >
            <VImg
              :src="path+'images/custom/cavo.png'"
            />
          </VAvatar>

          <!-- 👉 User fullName -->
          <h6 class="text-h4 mt-4">
          </h6>
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
                  {{ $t('Label.Codice') }}:
                  <span class="text-body-1">
                    {{ props.cavoData.codice }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Descrizione') }}:
                  <span class="text-body-1">
                    {{ props.cavoData.descrizione }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Categoria') }}:
                  <span class="text-body-1">{{ props.cavoData.categoria }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Norma') }}:
                  <span class="text-body-1">{{ props.cavoData.norma}}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Data') }}:
                  <span class="text-body-1">{{ formatDate(props.cavoData.created_at) }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>

        <!-- 👉 Edit and Suspend button -->
        <VCardText class="d-flex justify-center" v-if="$can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)">

          <VBtn
              variant="elevated"
              class="me-4"
              @click="isCavoInfoEditDialogVisible = true"
              @cavo-data="editCavo"
          >
            {{ $t('Button.Edit') }}
          </VBtn>

        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

  </VRow>

  <!-- 👉 Edit user info dialog -->
  <CavoInfoEditDialog
    v-model:isDrawerOpen="isCavoInfoEditDialogVisible"
    :cavo-data="props.cavoData"
    @cavo-data="editCavo"
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
