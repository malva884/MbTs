<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import LoadingStandBy from '@/components/LoadingStandBy.vue'
import type { QuestionarioQualita } from '@/views/quality/fornitore/certificazioni/view/type'

interface Props {
  certificato: object
}

interface Emit {
  (e: 'isSuccess', value: boolean): void
}

const props = defineProps<Props>()

const emit = defineEmits<Emit>()

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
  t_18: null,
  t_19: null,
  t_20: null,
  t_21: null,
  t_22: null,
  t_23: null,
  t_24: null,
  t_25: null,
  t_26: null,
  t_27: null,
  t_28: null,
  t_29: null,
  t_30: null,
  t_31: null,
  t_32: null,
  t_33: null,
  t_34: null,
  t_35: null,
  t_45: null,
  r_36: null,
  r_37: null,
  r_38: null,
  r_39: null,
  r_40: null,
  r_41: null,
  r_42: null,
  r_43: null,
  r_44: null,
  r_45: null,
})

const editedItem = ref<QuestionarioQualita>(defaultItem.value)
const editedIndex = ref(-1)

const { t } = useI18n()
const notificaVisible = ref(false)
const message = ref('')
const color = ref('')
const check = ref(0)
const loadingPage = ref(false)
const isDialogVisible = ref(false)

const get_questionario = async () => {
  const { data: questionarioObj } = await useApi<any>(createUrl(`qt/certification/getQuestionario/${props.certificato.certificato_id}`,{
    query: {
      fornitore_id: props.certificato.fornitore_id,
    },
  }))

  editedItem.value = { ...questionarioObj.value }
  editedItem.value.t_18 = editedItem.value.r_18
  editedItem.value.t_19 = editedItem.value.r_19
  editedItem.value.t_20 = editedItem.value.r_20
  editedItem.value.t_21 = editedItem.value.r_21
  editedItem.value.t_22 = editedItem.value.r_22
  editedItem.value.t_23 = editedItem.value.r_23
  editedItem.value.t_24 = editedItem.value.r_24
  editedItem.value.t_25 = editedItem.value.r_25
  editedItem.value.t_26 = editedItem.value.r_26
  editedItem.value.t_27 = editedItem.value.r_27
  editedItem.value.t_28 = editedItem.value.r_28
  editedItem.value.t_29 = editedItem.value.r_29
  editedItem.value.t_30 = editedItem.value.r_30
  editedItem.value.t_31 = editedItem.value.r_31
  editedItem.value.t_32 = editedItem.value.r_32
  editedItem.value.t_33 = editedItem.value.r_33
  editedItem.value.t_34 = editedItem.value.r_34
  editedItem.value.t_35 = editedItem.value.r_35
  editedItem.value.t_45 = editedItem.value.r_45
  editedItem.value.r_18 = null
  editedItem.value.r_19 = null
  editedItem.value.r_20 = null
  editedItem.value.r_21 = null
  editedItem.value.r_22 = null
  editedItem.value.r_23 = null
  editedItem.value.r_24 = null
  editedItem.value.r_25 = null
  editedItem.value.r_26 = null
  editedItem.value.r_27 = null
  editedItem.value.r_28 = null
  editedItem.value.r_29 = null
  editedItem.value.r_30 = null
  editedItem.value.r_31 = null
  editedItem.value.r_32 = null
  editedItem.value.r_33 = null
  editedItem.value.r_34 = null
  editedItem.value.r_35 = null
  editedItem.value.r_45 = null

  check.value = check.value + 1
}

const confirm = () => {
  isDialogVisible.value = true
}

get_questionario()
watch(props, () => {
  get_questionario()
})
</script>

<template>
  <LoadingStandBy v-model="loadingPage" />
  <VSnackbar
    v-model="notificaVisible"
    :color="color"
    location="top"
  >
    {{ t(message) }}
  </VSnackbar>
  <VCardTitle class="mb-5 text-primary text-center text-h3">
    {{ `${t('Label.Questionario')} : ${props.certificato.titolo}` }}
  </VCardTitle>
  <VCardText>
    <VForm @submit.prevent="() => {}">
      <VRow>
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
                for="q-36"
              >{{ t('Questionario-14001.d1') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_36"
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
                for="q-37"
              >{{ t('Questionario-14001.d2') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_37"
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
                for="q-38"
              >{{ t('Questionario-14001.d3') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_38"
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
                for="q-39"
              >{{ t('Questionario-14001.d4') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_39"
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
                for="q-40"
              >{{ t('Questionario-14001.d5') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_40"
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
                for="q-41"
              >{{ t('Questionario-14001.d6') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_41"
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
                for="q-42"
              >{{ t('Questionario-14001.d7') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_42"
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
                for="q-43"
              >{{ t('Questionario-14001.d8') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_43"
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
              md="2"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-45"
              >{{ t('Questionario-14001.d9') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="10"
            >
              <AppTextField
                v-model="editedItem.t_45"
                :readonly="!!editedItem.stato"
                type="string"
              />
            </VCol>
          </VRow>
        </VCol>
      </VRow>

      <VRow
        v-if="!certificato?.file_id"
        :key="check"
      >
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
              >{{ t('Questionario-14001.d10') }}</label>
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
                  value="4"
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
              >{{ t('Questionario-14001.d11') }}</label>
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
                  value="4"
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
              >{{ t('Questionario-14001.d12') }}</label>
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
                  value="7"
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
                class="v-label text-body-2 text-wrap text-wrap text-high-emphasis"
                for="q-4"
              >{{ t('Questionario-14001.d13') }}</label>
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
                  value="8"
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
                for="q-5"
              >{{ t('Questionario-14001.d14') }}</label>
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
                  value="7"
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
                for="q-6"
              >{{ t('Questionario-14001.d15') }}</label>
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
                  value="8"
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
              >{{ t('Questionario-14001.d16') }}</label>
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
                  value="5"
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
              >{{ t('Questionario-14001.d17') }}</label>
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
                  value="5"
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
              >{{ t('Questionario-14001.d18') }}</label>
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
                  value="5"
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
              >{{ t('Questionario-14001.d19') }}</label>
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
                  value="5"
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
              >{{ t('Questionario-14001.d20') }}</label>
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
                  value="7"
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
              >{{ t('Questionario-14001.d21') }}</label>
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
                  value="5"
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
              >{{ t('Questionario-14001.d22') }}</label>
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
                  value="4"
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
              >{{ t('Questionario-14001.d23') }}</label>
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
                  value="5"
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
              >{{ t('Questionario-14001.d24') }}</label>
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
                  value="6"
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
                for="q-16"
              >{{ t('Questionario-14001.d25') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_16"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="6"
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
                for="q-17"
              >{{ t('Questionario-14001.d26') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_17"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="9"
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
              md="5"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-18"
              >{{ t('Questionario-14001.d27') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_18"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.donne')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_19"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.uomini')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_20"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.no-italiani')"
                type="number"
              />
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="5"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-18"
              >{{ t('Questionario-14001.d28') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_21"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.donne')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_22"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.uomini')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_23"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.no-italiani')"
                type="number"
              />
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="5"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-20"
              >{{ t('Questionario-14001.d29') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_24"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.donne')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_25"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.uomini')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_26"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.no-italiani')"
                type="number"
              />
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="5"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-21"
              >{{ t('Questionario-14001.d30') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_27"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.donne')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_28"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.uomini')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_29"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.no-italiani')"
                type="number"
              />
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="5"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-21"
              >{{ t('Questionario-14001.d31') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_30"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.donne')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_31"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.uomini')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_32"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.no-italiani')"
                type="number"
              />
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12">
          <VRow no-gutters>
            <!-- 👉 First Name -->
            <VCol
              cols="12"
              md="5"
              class="d-flex align-items-center"
            >
              <label
                class="v-label text-body-2 text-wrap text-high-emphasis"
                for="q-21"
              >{{ t('Questionario-14001.d33') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_33"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.donne')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_34"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.uomini')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_35"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.no-italiani')"
                type="number"
              />
            </VCol>
          </VRow>
        </VCol>
      </VRow>
    </VForm>
  </VCardText>
</template>

<style scoped lang="scss">
.v-col-12{
  width: 100%;
  padding: 1px!important;
}
</style>
