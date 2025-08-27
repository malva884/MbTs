<script setup lang="ts">
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Preventivo } from '@/views/offices/technical/quote/type'
import CavoInfoEditDialog from "@/components/dialogs/CavoInfoEditDialog.vue";
import PreventivoInfoEditDialog from "@/components/dialogs/PreventivoInfoEditDialog.vue";

interface Props {
  preventivoData: Preventivo
}

const props = defineProps<Props>()
const path = import.meta.env.VITE_BASE_URL_PORTALE
const isSnackbarScrollReverseVisible = ref(false)

const isPreventivoInfoEditDialogVisible = ref(false)
const message = ref('')
const color = ref('')

const editPreventivo = async (preventivoData: Preventivo) => {
  const retuenData = await $api(`/to/preventivi/update/${preventivoData.id}`, {
    method: 'POST',
    body: preventivoData,
  })

  // eslint-disable-next-line vue/no-mutating-props
  props.preventivoData.numero = retuenData.obj.numero
  // eslint-disable-next-line vue/no-mutating-props
  props.preventivoData.parametro = retuenData.obj.parametro
  // eslint-disable-next-line vue/no-mutating-props
  props.preventivoData.rdo = retuenData.obj.rdo
  // eslint-disable-next-line vue/no-mutating-props
  props.preventivoData.cliente_id = retuenData.obj.cliente_id
  // eslint-disable-next-line vue/no-mutating-props
  props.preventivoData.cu = retuenData.obj.cu
  // eslint-disable-next-line vue/no-mutating-props
  props.preventivoData.data_rdo = retuenData.obj.data_rdo

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}
</script>

<template>
  <VRow>
    <!-- SECTION User Details -->
    <VCol cols="12">
      <VCard v-if="props.preventivoData">
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
            {{ props.preventivoData.numero }}
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
              :src="path+'images/custom/preventivo.png'"
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
                  {{ $t('Label.Cliente') }}:
                  <span class="text-body-1">
                    {{ props.preventivoData.cliente_obj?.ragione_sociale }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Data-Creazione') }}:
                  <span class="text-body-1">
                    {{ formatDate(props.preventivoData.data_preventivo) }}
                  </span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Base-Cu') }}:
                  <span class="text-body-1">{{ props.preventivoData.cu }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Parametro') }}:
                  <span class="text-body-1">{{ props.preventivoData.parametro}}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Rdo') }}:
                  <span class="text-body-1">{{ props.preventivoData.rdo }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Del') }}:
                  <span class="text-body-1">{{ formatDate(props.preventivoData.data_rdo) }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>

            <VListItem>
              <VListItemTitle>
                <h6 class="text-h6">
                  {{ $t('Label.Nota') }}:
                  <span class="text-body-1">{{ props.preventivoData.nota }}</span>
                </h6>
              </VListItemTitle>
            </VListItem>
          </VList>
        </VCardText>

        <!-- 👉 Edit and Suspend button -->
        <VCardText class="d-flex justify-center" v-if="$can(DefineAbilities.preventivi_edit.action, DefineAbilities.preventivi_edit.subject)">

          <VBtn
            variant="elevated"
            class="me-4"
            @click="isPreventivoInfoEditDialogVisible = true"
            @cavo-data="editPreventivo"
          >
            {{ $t('Button.Edit') }}
          </VBtn>

        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

  </VRow>

  <!-- 👉 Edit user info dialog -->
  <PreventivoInfoEditDialog
    v-model:isDrawerOpen="isPreventivoInfoEditDialogVisible"
    :preventivo-data="props.preventivoData"
    @preventivo-data="editPreventivo"
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
