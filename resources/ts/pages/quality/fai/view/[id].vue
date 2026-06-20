<script setup lang="ts">
import { ref, onMounted } from 'vue'
import moment from 'moment'

// Stati della pagina
const loading = ref(true)
const isClosing = ref(false) // Stato di caricamento per la chiusura dell'esito
const faiMaster = ref<any>({})
const proveLista = ref<any[]>([])

// Recupero parametri URL per l'ID del FAI
const route = useRoute('quality-fai-view-id')
const faiId = route.params.id

// Recupero dei dati iniziali del FAI e delle relative prove
const fetchFaiDettaglio = async () => {
  loading.value = true
  try {
    const response = await $api(`/qt/fai/summary/${faiId}`)
    if (response) {
      faiMaster.value = response.fai
      proveLista.value = response.prove
    }
  } catch (error) {
    console.error("Errore nel recupero dati FAI:", error)
  } finally {
    loading.value = false
  }
}

// Funzione per chiudere il FAI con l'esito selezionato manualmente dall'utente
const chiudiFai = async (nuovoEsito: 'POSITIVO' | 'NEGATIVO') => {
  const messaggio = nuovoEsito === 'POSITIVO'
    ? 'Sei sicuro di voler chiudere questo FAI con esito Positivo?'
    : 'Sei sicuro di voler chiudere questo FAI con esito Negativo?'

  if (!confirm(messaggio)) return

  isClosing.value = true
  try {
    // Invia la richiesta PUT al backend per aggiornare l'esito sul DB
    await $api(`/qt/fai/update/${faiId}`, {
      method: 'PUT',
      body: { esito: nuovoEsito },
    })

    // Rinfresca immediatamente i dati a schermo per aggiornare lo stato e nascondere il menu
    await fetchFaiDettaglio()
  } catch (error) {
    console.error('Errore durante la chiusura del FAI:', error)
  } finally {
    isClosing.value = false
  }
}

// Mappatura colori per il Chip dello STATO GENERALE del FAI Master
const getFaiMasterColor = (esito: string) => {
  if (!esito) return 'grey'
  const clean = esito.toUpperCase()
  if (clean === 'IN_CORSO') return 'warning'
  if (clean === 'CONFORME') return 'success'
  if (clean === 'NON CONFORME') return 'error'
  return 'primary'
}

// Mappatura colori per gli esiti delle singole prove di laboratorio (tabella a destra)
const getEsitoColor = (esito: string) => {
  if (!esito) return 'grey'
  const clean = esito.toUpperCase()
  if (clean === 'MANCANTE') return 'grey-darken-1'
  if (clean === 'CONFORME' || clean === 'POSITIVO' || clean === 'PASSED') return 'success'
  if (clean === 'NON CONFORME' || clean === 'NEGATIVO' || clean === 'FAILED') return 'error'
  return 'warning'
}

// Funzione per aprire la cartella del FAI su Google Drive
function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

// Funzione per aprire il file singolo (la specifica tecnica) su Google Drive
function openDriveFile(fileId: string) {
  window.open(`https://drive.google.com/file/d/${fileId}/view`, '_blank')
}

onMounted(fetchFaiDettaglio)
</script>

<template>
  <VRow v-if="!loading">

    <VCol cols="12">
      <VCard class="border-s-xl border-dark pa-4 bg-surface" style="border-left-width: 8px !important;">
        <div class="d-flex justify-space-between align-center flex-wrap gap-4">
          <div>
            <h1 class="text-h4 font-weight-black text-slate-900 tracking-tight">
              {{ faiMaster.codice }}
            </h1>
            <p class="text-subtitle-2 text-uppercase text-medium-emphasis tracking-widest mt-1">
              Rapporto di Controllo FAI &bull; Scheda di Monitoraggio Prove
            </p>
          </div>

          <div class="d-flex align-center gap-3">
            <VMenu v-if="faiMaster.esito === 'IN_CORSO'">
              <template #activator="{ props }">
                <VBtn
                  color="primary"
                  variant="elevated"
                  prepend-icon="tabler-lock-toggle"
                  append-icon="tabler-chevron-down"
                  class="font-weight-bold text-uppercase"
                  :loading="isClosing"
                  v-bind="props"
                >
                  Imposta Esito / Chiudi
                </VBtn>
              </template>

              <VList density="compact" class="pa-1">
                <VListItem
                  prepend-icon="tabler-circle-check"
                  title="Chiudi come Positivo"
                  class="text-success font-weight-bold rounded mb-1"
                  @click="chiudiFai('POSITIVO')"
                />

                <VListItem
                  prepend-icon="tabler-circle-x"
                  title="Chiudi come Negativo"
                  class="text-error font-weight-bold rounded"
                  @click="chiudiFai('NEGATIVO')"
                />
              </VList>
            </VMenu>

            <VChip
              variant="flat"
              :color="getFaiMasterColor(faiMaster.esito)"
              class="text-uppercase font-weight-bold px-4"
              size="large"
            >
              {{ faiMaster.esito }}
            </VChip>
          </div>
        </div>
      </VCard>
    </VCol>

    <VCol cols="12" md="4">
      <VCard title="Dettaglio Fai" variant="flat" class="border">
        <VDivider/>
        <VCardText class="pa-0">
          <v-list lines="two" class="bg-transparent">
            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Cliente / Fornitore</span>
              </template>
              <span class="text-body-1 font-weight-medium">{{ faiMaster.soggetto }}</span>
            </v-list-item>
            <VDivider class="mx-4"/>

            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Articolo / Prodotto</span>
              </template>
              <span class="text-body-1 font-weight-bold text-primary">{{ faiMaster.articolo }}</span>
            </v-list-item>
            <VDivider class="mx-4"/>

            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Ordine di Lavoro (OL)</span>
              </template>
              <span class="text-body-1 font-weight-medium">{{ faiMaster.ol }}</span>
            </v-list-item>
            <VDivider class="mx-4"/>

            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Specifica Tecnica</span>
              </template>
              <span
                v-if="faiMaster.specifica && faiMaster.specifica_id"
                class="text-body-1 font-weight-bold text-success cursor-pointer link-specifica d-inline-block text-truncate"
                style="max-width: 100%;"
                title="Clicca per aprire il file della specifica su Google Drive"
                @click="openDriveFile(faiMaster.specifica_id)"
              >
                <VIcon icon="tabler-file-text" size="18" class="me-1 mb-0.5" />
                {{ faiMaster.specifica }}
              </span>
              <span v-else class="text-body-1 font-weight-medium text-muted">
                {{ faiMaster.specifica || 'N/D' }}
              </span>
            </v-list-item>
            <VDivider class="mx-4"/>

            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Data Apertura Flusso</span>
              </template>
              <span class="text-body-1 font-weight-medium">
                {{ moment(faiMaster.data_inizio).format('DD/MM/YYYY') }}
              </span>
            </v-list-item>
            <VDivider class="mx-4"/>

            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Esito Fattibilità</span>
              </template>
              <span class="text-body-1 font-weight-medium">{{ faiMaster.esito_fattibilita || '-' }}</span>
            </v-list-item>
            <VDivider class="mx-4"/>

            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Descrizione</span>
              </template>
              <span class="text-body-1 font-weight-medium">{{ faiMaster.descrizione || '-' }}</span>
            </v-list-item>
            <VDivider class="mx-4"/>

            <v-list-item>
              <template #subtitle><span class="text-caption font-weight-bold tracking-wide text-uppercase">Cartella Cloud FAI</span>
              </template>
              <div class="mt-1">
                <VBtn
                  v-if="faiMaster.drive_id"
                  size="small"
                  color="primary"
                  variant="tonal"
                  prepend-icon="tabler-brand-google-drive"
                  @click="openDrivePage(faiMaster.drive_id)"
                >
                  Apri Drive
                </VBtn>
                <span v-else class="text-body-1 font-weight-medium">-</span>
              </div>
            </v-list-item>
          </v-list>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" md="8">
      <VCard title="Registro Completo Prove di Laboratorio" variant="flat" class="border">
        <VCardText class="pt-2">

          <p class="text-body-2 text-muted mb-4">
            Il sistema mostra l'elenco completo di tutti i test eseguiti per questo protocollo. Sono incluse le
            ripetizioni cicliche dello stesso test e le prove estemporanee fuori piano originale.
          </p>

          <v-table density="comfortable" class="border rounded text-sm">
            <thead class="bg-slate-50">
            <tr>
              <th class="font-weight-bold text-uppercase text-caption text-dark" style="width: 220px;">Prova / Test</th>
              <th class="font-weight-bold text-uppercase text-caption text-dark">Data Esecuzione</th>
              <th class="font-weight-bold text-uppercase text-caption text-dark">Operatore Lab</th>
              <th class="font-weight-bold text-uppercase text-caption text-dark">Esito</th>
              <th class="font-weight-bold text-uppercase text-caption text-dark">Note</th>
              <th class="text-end font-weight-bold text-uppercase text-caption text-dark">Allegati</th>
            </tr>
            </thead>
            <tbody>
            <tr
              v-for="test in proveLista"
              :key="test.id"
              :class="{
                'riga-fuori-piano': !test.in_piano,
                'riga-mancante': test.mancante
              }"
            >
              <td>
                <div class="d-flex align-center">
                  <VIcon
                    :icon="test.mancante ? 'tabler-clock-bolt' : (test.in_piano ? 'tabler-circle-check-filled' : 'tabler-alert-square-filled')"
                    :color="test.mancante ? 'error' : (test.in_piano ? 'success' : 'amber-darken-2')"
                    class="me-3"
                    size="20"
                  />
                  <div>
                    <span
                      class="font-weight-bold"
                      :class="test.mancante ? 'text-error text-decoration-line-through' : (test.in_piano ? 'text-slate-900' : 'text-amber-darken-4')"
                      style="opacity: test.mancante ? 0.7 : 1;"
                    >
                      {{ test.nome_prova }}
                    </span>

                    <div class="mt-0.5">
                      <VChip
                        size="x-small"
                        variant="flat"
                        :color="test.mancante ? 'red-lighten-5' : (test.in_piano ? 'green-lighten-5' : 'amber-lighten-4')"
                        :class="test.mancante ? 'text-error' : (test.in_piano ? 'text-success' : 'text-amber-darken-4')"
                        class="font-weight-bold text-uppercase px-1 rounded"
                        style="font-size: 9px; height: 16px;"
                      >
                        {{ test.mancante ? 'Da Eseguire / Mancante' : (test.in_piano ? 'In Piano FAI' : 'Fuori Piano / Extra') }}
                      </VChip>
                    </div>
                  </div>
                </div>
              </td>

              <td class="font-weight-medium" :class="{'text-disabled italic': test.mancante}">
                {{ test.data_prova ? moment(test.data_prova).format('DD/MM/YYYY') : 'Da pianificare' }}
              </td>

              <td :class="{'text-disabled': test.mancante}">
                <div v-if="test.operatore" class="d-flex align-center text-medium-emphasis">
                  <VIcon icon="tabler-user" size="14" class="me-1 text-muted" />
                  <span>{{ test.operatore }}</span>
                </div>
                <span v-else class="text-caption italic text-muted">Non assegnato</span>
              </td>

              <td>
                <VChip
                  :variant="test.mancante ? 'outlined' : 'tonal'"
                  size="small"
                  :color="getEsitoColor(test.esito)"
                  class="font-weight-bold text-uppercase px-2"
                  :class="{'border-dashed': test.mancante}"
                >
                  {{ test.mancante ? 'DA CARICARE' : (test.esito || 'NON VALUTATO') }}
                </VChip>
              </td>

              <td class="text-muted text-truncate" style="max-width: 150px;" :title="test.note">
                <span :class="{'italic text-caption': test.mancante}">{{ test.note || '-' }}</span>
              </td>

              <td class="text-end">
                <VBtn
                  v-if="test.path_drive"
                  size="x-small"
                  color="primary"
                  variant="tonal"
                  prepend-icon="tabler-brand-google-drive"
                  @click="openDrivePage(test.path_drive)"
                >
                  Drive
                </VBtn>
                <VChip v-else-if="test.mancante" size="x-small" color="error" variant="text" prepend-icon="tabler-file-x">
                  Attesa File
                </VChip>
                <span v-else class="text-caption text-disabled">Nessuno</span>
              </td>
            </tr>
            </tbody>
          </v-table>

          <VAlert v-if="proveLista.length === 0" type="info" variant="tonal" class="mt-4">
            Nessun record di laboratorio trovato per questo protocollo FAI su database.
          </VAlert>

        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VRow v-else justify="center" align="center" style="min-height: 300px;">
    <VCol cols="auto" class="text-center">
      <VProgressCircular indeterminate color="primary" size="64"/>
      <div class="text-body-1 mt-4 text-muted font-weight-medium">
        Caricamento tracciabilità di laboratorio in corso...
      </div>
    </VCol>
  </VRow>
</template>

<style scoped>
.border-s-xl {
  border-left-style: solid !important;
}

.bg-slate-50 {
  background-color: #f8fafc !important;
}

/* Stile per l'effetto hover sul link interattivo della specifica */
.link-specifica:hover {
  text-decoration: underline !important;
  opacity: 0.85;
}
</style>
