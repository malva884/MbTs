<script setup lang="ts">
import {VForm} from 'vuetify/components/VForm'

definePage({
  meta: {
    action: 'list',
    subject: 'Macchinari',
  },
})

const item = ref({
  idInstrument: '',
  serialNumber: '',
  inspector: '',
  issuingBody: '',
  frequency: '',
  months: '',
  from: '',
  due: '',
})

const isFormValid = ref(false)
const form = ref<VForm>()
const message = ref('')
const color = ref('')
const isSnackbarScrollReverseVisible = ref(false)

function new_defaultItem() {
  item.value = {
    idInstrument: '',
    serialNumber: '',
    inspector: '',
    issuingBody: '',
    frequency: '',
    months: '',
    from: '',
    due: '',
  }
}

const print = async () => {
  const { data } = await useApi<any>(createUrl('/template/quality/strumenti', {
    query: item.value,
  }))

  new_defaultItem()
  message.value = data.value.message
  color.value = data.value.color
  isSnackbarScrollReverseVisible.value = true
}
</script>

<template>
  <VRow>
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
    <VCol cols="10">
      <VCard
        :title="$t('Label.Template-Strumenti')"
        class="mb-10"
      >
        <VCardText>
          <VForm
            ref="refForm"
            v-model="isFormValid"
          >
            <VRow>
              <!-- 👉 Select idInstrument -->
              <VCol
                cols="12"
                sm="4"
              >
                <AppTextField
                  v-model="item.idInstrument"
                  :label="$t('Label.Id-Strumento')"
                  :placeholder="$t('Label.Id-Strumento')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <!-- 👉 Select Serial Number -->
              <VCol
                cols="12"
                sm="4"
              >
                <AppTextField
                  v-model="item.serialNumber"
                  :label="$t('Label.Serial-Number')"
                  :placeholder="$t('Label.Serial-Number')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <!-- 👉 Select Inspector -->
              <VCol
                cols="12"
                sm="4"
              >
                <AppTextField
                  v-model="item.inspector"
                  :label="$t('Label.Inspector')"
                  :placeholder="$t('Label.Inspector')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <!-- 👉 Select Issuing Body -->
              <VCol
                cols="12"
                sm="4"
              >
                <AppTextField
                  v-model="item.issuingBody"
                  :label="$t('Label.Issuing-Body')"
                  :placeholder="$t('Label.Issuing-Body')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <!-- 👉 Select Frequency -->
              <VCol
                cols="12"
                sm="4"
              >
                <AppTextField
                  v-model="item.frequency"
                  :label="$t('Label.Frequency')"
                  :placeholder="$t('Label.Frequency')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <!-- 👉 Select Months -->
              <VCol
                cols="12"
                sm="4"
              >
                <AppTextField
                  v-model="item.months"
                  :label="$t('Label.Months')"
                  :placeholder="$t('Label.Months')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <!-- 👉 From -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppDateTimePicker
                  v-model="item.from"
                  :label="$t('Label.From')"
                  :placeholder="$t('Label.From')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <!-- 👉 Due -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppDateTimePicker
                  v-model="item.due"
                  :label="$t('Label.Due')"
                  :placeholder="$t('Label.Due')"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VBtn
                  color="success"
                  @click="print"
                >
                  {{ $t('Label.Stampa') }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">

</style>
