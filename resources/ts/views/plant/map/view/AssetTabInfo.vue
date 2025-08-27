<script setup lang="ts">

import avatar2 from "@images/avatars/avatar-2.png";

interface Props {
  id: string
}

const props = defineProps<Props>()
const info = ref({})

const loadInfo = async () => {
  const { data: infoData } = await useApi<any>(createUrl(`/pl/asset/view/${props.id}`))

  info.value = infoData.value
}

loadInfo()
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard >
        <VCardText>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Nome') }}:
                      <span class="text-body-1 text-success">
                    {{ info.utente }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Numero-Seriale') }}:
                      <span class="text-body-1 text-success">
                    {{ info.numero_seriale }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Data-Allocazione') }}:
                      <span class="text-body-1 text-success">
                    {{ info.data_allocazione }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Codice-Asset') }}:
                      <span class="text-body-1 text-success">
                    {{ info.codice_asset }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Cpu') }}:
                      <span class="text-body-1 text-success">
                    {{ info.cpu }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Data-Dismesso') }}:
                      <span class="text-body-1 text-success">
                    {{ info.data_dismesso }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Email') }}:
                      <span class="text-body-1 text-success">
                    {{ info.email }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Hard-Disk-Numero') }}:
                      <span class="text-body-1 text-success">
                    {{ info.hdd_numero }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Motivazione') }}:
                      <span class="text-body-1 text-success">
                    {{ info.motivazione_dismesso }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Host-Name') }}:
                      <span class="text-body-1 text-success">
                    {{ info.hostName }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Hard-Disk-Capacita') }}:
                      <span class="text-body-1 text-success">
                    {{ info.hdd_capienza }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Data-Fattura') }}:
                      <span class="text-body-1 text-success">
                    {{ info.fattura_dt }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Tipo-Asset') }}:
                      <span class="text-body-1 text-success">
                    {{ info.tipo_asset }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Ram-Numero') }}:
                      <span class="text-body-1 text-success">
                    {{ info.ram_numero }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Fattura-Numero') }}:
                      <span class="text-body-1 text-success">
                    {{ info.fattura_numero }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Ip-Address') }}:
                      <span class="text-body-1 text-success">
                    {{ info.ip_address }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Ram-Capacita') }}:
                      <span class="text-body-1 text-success">
                    {{ info.ram_memoria }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Fine-Garanzia') }}:
                      <span class="text-body-1 text-success">
                    {{ info.fine_garanzia }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Condizioni') }}:
                      <span class="text-body-1 text-success">
                    {{ info.condizione_asset }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Marca') }}:
                      <span class="text-body-1 text-success">
                    {{ info.marca }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Stato') }}:
                      <span class="text-body-1 text-success">
                    {{ info.stato }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Nome') }}:
                      <span class="text-body-1 text-success">
                    {{ info.utente }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h5">
                      {{ $t('Label.Modello') }}:
                      <span class="text-body-1 text-success">
                    {{ info.modello }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Nome') }}:
                      <span class="text-body-1 text-success">
                    {{ info.utente }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>
          <VRow no-gutters>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Nome') }}:
                      <span class="text-body-1 text-success">
                    {{ info.utente }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              xl="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Nome') }}:
                      <span class="text-body-1 text-success">
                    {{ info.utente }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
            <VCol
              cols="12"
              md="4"
            >
              <VList class="card-list mt-2">
                <VListItem>
                  <VListItemTitle>
                    <h6 class="text-h6">
                      {{ $t('Label.Nome') }}:
                      <span class="text-body-1 text-success">
                    {{ info.utente }}
                  </span>
                    </h6>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VCol>
          </VRow>


          <VSpacer/>
        </VCardText>
      </VCard>

    </VCol>

  </VRow>
</template>

<style scoped lang="scss">

</style>
