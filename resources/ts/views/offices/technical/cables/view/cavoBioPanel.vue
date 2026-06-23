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

        <VCardItem class="pb-2">
          <template #prepend>
            <VIcon icon="tabler-timeline" size="28" color="secondary" />
          </template>
          <VCardTitle class="text-h6">Cavo</VCardTitle>
          <template #append>
            <VChip size="small" color="secondary" variant="tonal" class="font-weight-bold font-monospace">
              {{ props.cavoData.codice }}
            </VChip>
          </template>
        </VCardItem>

        <VDivider />

        <VCardText class="py-3">
          <div class="info-row">
            <VIcon icon="tabler-text-size" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Descrizione</span>
            <span class="text-body-2 ms-auto text-end" style="max-width:65%">{{ props.cavoData.descrizione }}</span>
          </div>
          <VDivider class="my-2" />
          <div class="info-row">
            <VIcon icon="tabler-tag" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Categoria</span>
            <VChip size="x-small" color="info" variant="tonal" class="ms-auto">{{ props.cavoData.categoria }}</VChip>
          </div>
          <VDivider class="my-2" />
          <div class="info-row">
            <VIcon icon="tabler-certificate" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Norma</span>
            <span class="text-body-2 ms-auto">{{ props.cavoData.norma || '-' }}</span>
          </div>
          <VDivider class="my-2" />
          <div class="info-row">
            <VIcon icon="tabler-calendar" size="16" class="text-disabled me-2" />
            <span class="text-caption text-disabled">Creato il</span>
            <span class="text-body-2 ms-auto">{{ formatDate(props.cavoData.created_at) }}</span>
          </div>
        </VCardText>

        <VDivider />
        <VCardText class="py-2" v-if="$can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)">
          <VBtn size="small" variant="tonal" color="secondary" prepend-icon="tabler-edit" block @click="isCavoInfoEditDialogVisible = true">
            {{ $t('Button.Edit') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
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
.info-row {
  display: flex;
  align-items: center;
  min-height: 28px;
}
</style>
