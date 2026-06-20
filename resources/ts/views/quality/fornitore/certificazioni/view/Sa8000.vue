<script setup lang="ts">
import {VForm} from "vuetify/components/VForm"
import {QuestionarioQualita} from "@/pages/certificazioni/view/type"
import LoadingStandBy from "@/components/LoadingStandBy.vue";

interface Props {
  certificato: object
}

interface Emit {
  (e: 'isSuccess', value: boolean): void
}

const defaultItem = ref<QuestionarioQualita>({
  id: '',
  certificato_id: '',
  supplier_id: '',
  stato: true,
  r_1: null,
  r_2: null,
  r_3: null,
  r_4: null,
  r_5: null,
  r_6: null,
  r_7: null,
  r_8: null,
  r_9: null,
  r_10: null,
  r_11: null,
  r_12: null,
  r_13: null,
  r_14: null,
  r_15: null,
  r_16: null,
  r_17: null,
  r_18: null,
  r_19: null,
  r_20: null,
  r_21: null,
})

const editedItem = ref<QuestionarioQualita>(defaultItem.value)
const editedIndex = ref(-1)

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const {t} = useI18n()
const notificaVisible =ref(false)
const message = ref('')
const color = ref('')
const check = ref(0)
const loadingPage = ref(false)
const isDialogVisible = ref(false)


const confirm = () => {
  isDialogVisible.value = true
}

// eslint-disable-next-line camelcase
const get_questionario = async () => {
  const { data: questionarioObj } = await useApi<any>(createUrl(`qt/certification/getQuestionario/${props.certificato.certificato_id}`,{
    query: {
      fornitore_id: props.certificato.fornitore_id,
    },
  }))

  editedItem.value = { ...questionarioObj.value }
  check.value = check.value + 1
}

const stored = async () => {
  loadingPage.value = true
  editedItem.value.certificato_id = props.certificato.certificato_id

  const retuenData = await $api(`certificates/storedQuestionario`, {
    method: 'POST',
    body: editedItem.value,
  })

  loadingPage.value = false
  message.value = retuenData.message
  color.value = retuenData.color
  notificaVisible.value = true
  emit('isSuccess', true)

}

const send = async () => {
  isDialogVisible.value = false
  loadingPage.value = true
  editedItem.value.certificato_id = props.certificato.certificato_id
  const retuenData = await $api(`certificates/validateQuestionario`, {
    method: 'POST',
    body: editedItem.value,
  })

  loadingPage.value = false
  message.value = retuenData.message
  color.value = retuenData.color
  notificaVisible.value = true
  emit('isSuccess', true)

}

get_questionario()
watch(props, () => {
  get_questionario()
})
</script>

<template>
  <LoadingStandBy v-model="loadingPage"/>
  <VSnackbar
    v-model="notificaVisible"
    :color="color"
    location="top"
  >
    {{ t(message) }}
  </VSnackbar>
  <VCardTitle class="mb-5 text-primary text-center text-h3">
    {{ t('Label.Questionario') + ' : ' + props.certificato.titolo}}
  </VCardTitle>
  <VCardText>
    <VForm @submit.prevent="() => {}">
      <VRow :key="check">
        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-1"
              >{{ t('Questionario-sa8000.d1') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_1"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-2"
              >{{ t('Questionario-sa8000.d2') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_2"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-3"
              >{{ t('Questionario-sa8000.d3') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_3"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-4"
              >{{ t('Questionario-sa8000.d4') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_4"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="si"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="no"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-5"
              >{{ t('Questionario-sa8000.d5') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_5"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="si"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="no"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-6"
              >{{ t('Questionario-sa8000.d6') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_6"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="-10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-7"
              >{{ t('Questionario-sa8000.d7') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_7"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-1"
              >{{ t('Questionario-sa8000.d8') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_8"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-9"
              >{{ t('Questionario-sa8000.d9') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_9"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-10"
              >{{ t('Questionario-sa8000.d10') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_10"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="-10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-11"
              >{{ t('Questionario-sa8000.d11') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_11"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="-10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-12"
              >{{ t('Questionario-sa8000.d12') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_12"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-13"
              >{{ t('Questionario-sa8000.d13') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_13"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-14"
              >{{ t('Questionario-sa8000.d14') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_14"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="10"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-15"
              >{{ t('Questionario-sa8000.d15') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_15"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="10"
                  density="compact"
                />
                <VRadio
                  :label="t('Label.No')"
                  value="-"
                  density="compact"
                />
              </VRadioGroup>
            </VCol>
          </VRow>
        </VCol>

        <!-- 👉 submit and reset button -->
        <VCol cols="12" v-if="editedItem.stato == false || editedItem.stato == null" class="mt-5">
          <VRow no-gutters>
            <VCol
              cols="12"
              md="3"
            />
            <VCol
              cols="12"
              md="9"
            >
              <VBtn
                :onclick="stored"
                class="me-4"
              >
                {{ t('Button.Salva') }}
              </VBtn>
              <VBtn
                :onclick="confirm"
                color="success"
              >
                {{ t('Button.Salva-Invia') }}
              </VBtn>
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </VForm>
  </VCardText>

  <VDialog
    v-model="isDialogVisible"
    persistent
    class="v-dialog-sm"
  >

    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

    <!-- Dialog Content -->
    <VCard :title="t('Label.Avviso-Importante')">
      <VCardText>
        {{ t('Messaggio.Avviso-Invio-Questionario') }}
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="isDialogVisible = false"
        >
          {{ t('Button.Chiudi') }}
        </VBtn>
        <VBtn @click="send">
          {{ t('Button.Invia') }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">
.v-col-12{
  width: 100%;
  padding: 1px!important;
}
</style>
