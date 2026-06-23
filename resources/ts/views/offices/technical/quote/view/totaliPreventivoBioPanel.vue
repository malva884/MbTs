<script setup lang="ts">
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import CavoInfoEditDialog from "@/components/dialogs/CavoInfoEditDialog.vue";
import { Cavo } from '@/views/offices/technical/cables/type'

interface Props {
  cavoData: Cavo
  preventivoData: Preventivo
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

const euro = new Intl.NumberFormat('it-IT', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 4,
})
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
            <VIcon icon="tabler-chart-bar" size="28" color="success" />
          </template>
          <VCardTitle class="text-h6">Riepilogo Costi</VCardTitle>
          <template #append>
            <VChip size="small" color="success" variant="tonal" class="font-weight-bold">
              {{ euro.format(props.cavoData.costo) }} €/m
            </VChip>
          </template>
        </VCardItem>

        <VDivider />

        <VCardText class="py-3">
          <!-- Riga 1: costi principali -->
          <VRow dense>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Manodopera</span>
                <span class="stat-value text-primary">{{ euro.format(props.cavoData.costo_manodopera) }} €</span>
              </div>
            </VCol>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Mat. Prima</span>
                <span class="stat-value text-success">{{ euro.format(props.cavoData.costo_materiali) }} €</span>
              </div>
            </VCol>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Scarto {{ props.cavoData.scarto }}%</span>
                <span class="stat-value text-warning">{{ euro.format(props.cavoData.costo_scarto) }} €</span>
              </div>
            </VCol>
            <VCol cols="6" sm="3">
              <div class="stat-tile stat-tile--highlight">
                <span class="stat-label">Costo Totale</span>
                <span class="stat-value text-success font-weight-bold">{{ euro.format(props.cavoData.costo) }} €</span>
              </div>
            </VCol>
          </VRow>

          <VDivider class="my-3" />

          <!-- Riga 2: quantità e peso -->
          <VRow dense>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Metri</span>
                <span class="stat-value">{{ euro.format(props.cavoData.metri) }} m</span>
              </div>
            </VCol>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Peso Mat.</span>
                <span class="stat-value">{{ euro.format(props.cavoData.peso_materie) }} kg</span>
              </div>
            </VCol>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Netto</span>
                <span class="stat-value">{{ euro.format(props.cavoData.netto) }} kg</span>
              </div>
            </VCol>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Lordo</span>
                <span class="stat-value">{{ euro.format(props.cavoData.lordo) }} kg</span>
              </div>
            </VCol>
          </VRow>

          <VDivider class="my-3" />

          <!-- Riga 3: bobine e rame -->
          <VRow dense>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Bobina</span>
                <span class="stat-value text-caption">{{ props.cavoData.bobina || '-' }}</span>
              </div>
            </VCol>
            <VCol cols="4" sm="2">
              <div class="stat-tile">
                <span class="stat-label">N° Bob.</span>
                <span class="stat-value">{{ props.cavoData.bobina_numero }}</span>
              </div>
            </VCol>
            <VCol cols="4" sm="2">
              <div class="stat-tile">
                <span class="stat-label">Costo Bob.</span>
                <span class="stat-value">{{ euro.format(props.cavoData.costo_bobina) }} €</span>
              </div>
            </VCol>
            <VCol cols="4" sm="2">
              <div class="stat-tile">
                <span class="stat-label">Var. Rame</span>
                <span class="stat-value text-warning">{{ euro.format(props.cavoData.variante_rame) }}</span>
              </div>
            </VCol>
            <VCol cols="6" sm="3">
              <div class="stat-tile">
                <span class="stat-label">Costo Cu</span>
                <span class="stat-value text-warning">{{ euro.format(parseFloat(props.cavoData.variante_rame) * parseFloat(props.preventivoData.cu)) }} €</span>
              </div>
            </VCol>
          </VRow>
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
.stat-tile {
  display: flex;
  flex-direction: column;
  padding: 8px 10px;
  border-radius: 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);

  &--highlight {
    background: rgba(var(--v-theme-success), 0.08);
    border: 1px solid rgba(var(--v-theme-success), 0.3);
  }

  .stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity));
    letter-spacing: 0.05em;
    white-space: nowrap;
  }

  .stat-value {
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 2px;
    white-space: nowrap;
  }
}
</style>
