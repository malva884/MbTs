<script setup lang="ts">
definePage({
  meta: {
    layout: 'blank',
  },
})

const route = useRoute('offices-technical-quote-print-print')

const { data: caviData} = await useApi<any>(createUrl('/to/preventivi/cable/', {
  query: {
    ids: JSON.stringify(route.query.ids),
  },
}))

const euro = new Intl.NumberFormat('it-IT', {
  maximumSignificantDigits: 4,
})

const print = () => {
  window.print()
}

onMounted(() => print())
</script>

<template>
  <section>
    <VRow>
      <VBtn
        class="d-print-none"
        icon="tabler-printer"
        variant="outlined"
        color="success"
        @click="print"
      />
      <VCol
        v-for="cavo in caviData"
        cols="12"
        md="12"
        class="h-100"
      >
        <VCard >
          <VCardText class="d-flex justify-space-between flex-wrap flex-column flex-sm-row print-row">
            <div class="ma-sm-4">
              <h6 class="text-base font-weight-light mb-1">
                Preventivo N°:
              </h6>
              <p class="mb-0" style="font-size: 10px">
                {{ cavo.preventivo }}
                Del: {{ cavo.data_preventivo }}
              </p>
            </div>
            <div class="ma-sm-4">
              <h6 class="text-base font-weight-medium mb-0">
                Descrizione:
              </h6>
              <p class="mb-0" style="font-size: 10px">
                {{ cavo.cavo.descrizione}}
              </p>
            </div>
            <div class="ma-sm-4">
              <h6 class="text-base font-weight-medium mb-0">
                Norme:
              </h6>
              <p class="mb-0">
              </p>
            </div>
            <div class="ma-sm-4">
              <h6 class="text-base font-weight-medium mb-0">
                Cliente:
              </h6>
              <p class="mb-0" style="font-size: 10px">
                {{cavo.cavo.ragione_sociale}}
              </p>
            </div>
            <div class="ma-sm-4">
              <h6 class="text-base font-weight-medium mb-0">
                Cavo:
              </h6>
              <p class="mb-0" style="font-size: 10px">
                {{ cavo.cavo.codice}}
              </p>
            </div>
          </VCardText>

          <!-- 👉 Table -->
          <VDivider />
          <VTable
            density="compact"
            class="invoice-preview-table table-wrap"
          >
            <thead>
            <tr>
              <th>
                Centro
              </th>
              <th>
                Cod. M. P.
              </th>
              <th>
                Descrizione
              </th>
              <th>
                Diametro <br>(mm)
              </th>
              <th>
                Peso <br>(Kg/Km)
              </th>
              <th>
                Costo M.P. <br>(€/mt)
              </th>
              <th>
                Costo Centro <br>(€/h)
              </th>
              <th>
                Produzione Oraria <br>(Km/h)
              </th>
              <th>
                Numero El.
              </th>
              <th>
                Costo Lavorazione <br>(€/mt)
              </th>
              <th>
                Note
              </th>
              <th>
                Ore Macchina
              </th>
            </tr>
            </thead>

            <tbody>
            <tr
              v-for="item in cavo.struttura"
              :key="item.id"
            >
              <td >
                {{ item.centro }}
              </td>
              <td >
                {{ item.materiale }}
              </td>
              <td >
                {{ item.descrizione }}
              </td>
              <td>
                {{ item.diametro == 0 ? '' : euro.format(item.diametro) }}
              </td>
              <td>
                {{ item.peso == 0 ? '' : euro.format(item.peso) }}
              </td>
              <td>
                {{ item.costo_materia_prima == 0 ? '' : euro.format(item.costo_materia_prima) }}
              </td>
              <td>
                {{ item.costo_centro == 0 ? '' : euro.format(item.costo_centro) }}
              </td>
              <td>
                {{ item.ordinata == 0 ? '' : euro.format(item.ordinata) }}
              </td>
              <td>
                {{ item.elementi == 0 ? '' : item.elementi }}
              </td>
              <td>
                {{ item.costo_lavorazione == 0 ? '' : euro.format(item.costo_lavorazione) }}
              </td>
              <td>
                {{ item.nota }}
              </td>
              <td>
                {{ item.ore_macchina == 0 ? '' : euro.format(item.ore_macchina) }}
              </td>
            </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped lang="scss">
.invoice-preview-table {
  --v-table-row-height: 44px !important;
}

@media print {
  .v-theme--dark {
    --v-theme-surface: 255, 255, 255;
    --v-theme-on-surface: 94, 86, 105;
  }

  body {
    background: none !important;
  }

  @page { margin: 0; size: auto; }

  .layout-page-content,
  .v-row,
  .v-col-md-9 {
    padding: 0;
    margin: 0;
  }

  .product-buy-now {
    display: none;
  }

  .v-navigation-drawer,
  .layout-vertical-nav,
  .app-customizer-toggler,
  .layout-footer,
  .layout-navbar,
  .layout-navbar-and-nav-container {
    display: none;
  }

  .v-card {
    box-shadow: none !important;
    max-width: 100vw;
    overflow-x: hidden;
    .print-row {
      flex-direction: row !important;
    }
  }

  .layout-content-wrapper {
    padding-inline-start: 0 !important;
  }

  .v-table__wrapper {
    overflow: hidden !important;
  }
}

.table-wrap {
  //height: 730px;
  max-width: 100vw;
  overflow-x: hidden;
  overflow-y: auto;
}

tr {
  td {
    max-width: 100vw;
    overflow-x: hidden;
    font-size: 10px!important;
  }
  th {
    max-width: 100vw;
    overflow-x: hidden;
    font-size: 10px!important;
  }
}

.v-card-text {
  max-width: 100vw;
  overflow-x: hidden;
  padding: 1px!important;
}
</style>
