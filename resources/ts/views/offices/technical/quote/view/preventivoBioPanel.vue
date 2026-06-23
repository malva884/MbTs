<script setup lang="ts">
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Preventivo } from '@/views/offices/technical/quote/type'
import CavoInfoEditDialog from "@/components/dialogs/CavoInfoEditDialog.vue";
import PreventivoInfoEditDialog from "@/components/dialogs/PreventivoInfoEditDialog.vue";

interface Props {
  preventivoData: Preventivo
}

interface Emit {
  (e: 'saved'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
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

  if (retuenData.success) {
    emit('saved')
  }
}
</script>

<template>
  <VRow>
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

        <VCardItem class="pb-2">
          <template #prepend>
            <VIcon icon="tabler-file-description" size="28" color="primary" />
          </template>
          <VCardTitle class="text-h6">Preventivo</VCardTitle>
          <template #append>
            <VChip size="small" color="primary" variant="tonal" class="font-weight-bold">
              {{ props.preventivoData.numero }}
            </VChip>
          </template>
        </VCardItem>

        <VDivider />

        <VCardText class="py-3">
          <div class="info-row">
            <VIcon icon="tabler-building" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Cliente</span>
            <span class="text-body-2 font-weight-medium ms-auto text-end">{{ props.preventivoData.cliente_obj?.ragione_sociale }}</span>
          </div>
          <VDivider class="my-2" />
          <div class="info-row">
            <VIcon icon="tabler-calendar" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Data</span>
            <span class="text-body-2 ms-auto">{{ formatDate(props.preventivoData.data_preventivo) }}</span>
          </div>
          <VDivider class="my-2" />
          <div class="info-row">
            <VIcon icon="tabler-currency-euro" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Base Cu</span>
            <VChip size="x-small" color="warning" variant="tonal" class="ms-auto">{{ props.preventivoData.cu }}</VChip>
          </div>
          <VDivider class="my-2" />
          <div class="info-row">
            <VIcon icon="tabler-adjustments" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Parametro</span>
            <span class="text-body-2 ms-auto">{{ props.preventivoData.parametro }}</span>
          </div>
          <VDivider class="my-2" />
          <div class="info-row">
            <VIcon icon="tabler-file-invoice" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">RDO</span>
            <span class="text-body-2 ms-auto">{{ props.preventivoData.rdo || '-' }}</span>
          </div>
          <template v-if="props.preventivoData.nota">
            <VDivider class="my-2" />
            <div class="info-row">
              <VIcon icon="tabler-note" size="16" class="text-disabled me-2" />
              <span class="text-caption text-disabled">Nota</span>
              <span class="text-body-2 ms-auto text-end" style="max-width:60%">{{ props.preventivoData.nota }}</span>
            </div>
          </template>
        </VCardText>

        <VDivider />
        <VCardText class="py-2" v-if="$can(DefineAbilities.preventivi_edit.action, DefineAbilities.preventivi_edit.subject)">
          <VBtn size="small" variant="tonal" color="primary" prepend-icon="tabler-edit" block @click="isPreventivoInfoEditDialogVisible = true">
            {{ $t('Button.Edit') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
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
.info-row {
  display: flex;
  align-items: center;
  min-height: 28px;
}
</style>
