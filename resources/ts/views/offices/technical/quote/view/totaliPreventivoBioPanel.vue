<script setup lang="ts">
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import CavoInfoEditDialog from "@/components/dialogs/CavoInfoEditDialog.vue";
import { Cavo } from '@/views/offices/technical/cables/type'
import type {Preventivo} from "@/views/offices/technical/quote/type";

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
  maximumSignificantDigits: 8,
})
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

        <VCardText>
          <p class="text-sm text-uppercase text-disabled">
            {{ $t('Label.Totali') }}
          </p>
        </VCardText>

        <VDivider />

        <!-- 👉 Details -->
        <VCardText>
          <VRow>
            <VCol cols="4">
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Costo-Manodopera') }} €:
                      <span class="text-body-3 text-success">
                    {{ euro.format(props.cavoData.costo_manodopera) }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Costo-Materia-Prima') }} €:
                      <span class="text-body-3 text-success">
                    {{ euro.format(props.cavoData.costo_materiali) }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Somma-Materia-Prima') }}:
                      <span class="text-body-3 text-success">{{ euro.format(props.cavoData.somma_materiali) }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Costo-Totale') }} €:
                      <span class="text-body-3 text-success">{{ euro.format(props.cavoData.costo) }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Bobina') }}:
                      <span class="text-body-3 text-success">{{ props.cavoData.bobina }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Costo-Bobina') }} €:
                      <span class="text-body-3 text-success">{{ euro.format(props.cavoData.costo_bobina) }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Numero-Bobbine') }}:
                      <span class="text-body-3 text-success">{{ props.cavoData.bobina_numero }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol cols="4">
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Metri') }}:
                      <span class="text-body-3 text-success">
                        {{ euro.format(props.cavoData.metri) }}
                      </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Scarto') }} %:
                      <span class="text-body-3 text-success">
                        {{ props.cavoData.scarto }}
                      </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Scarto') }} €:
                      <span class="text-body-3 text-success">
                        {{ euro.format(props.cavoData.costo_scarto) }}
                      </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Netto') }}:
                      <span class="text-body-3 text-success">{{ euro.format(props.cavoData.netto) }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Lordo') }}:
                      <span class="text-body-3 text-success">{{ euro.format(props.cavoData.lordo) }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.M3') }}:
                      <span class="text-body-3 text-success">{{ euro.format(props.cavoData.m3) }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Peso Materieli') }}:
                      <span class="text-body-3 text-success">{{ euro.format(props.cavoData.peso_materie) }}</span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol cols="4">
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Variante-Rame') }} :
                      <span class="text-body-3 text-success">
                        {{ euro.format(props.cavoData.variante_rame) }}
                      </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>

                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5 text-success">
                      {{ $t('Label.Costo-Cu') }} €:
                      <span class="text-body-3 text-success">
                        {{ euro.format( parseFloat( props.cavoData.variante_rame) * parseFloat(props.preventivoData.cu)) }}
                      </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <!-- 👉 User Details list -->
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
