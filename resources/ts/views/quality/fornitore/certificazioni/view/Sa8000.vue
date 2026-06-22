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

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const {t} = useI18n()
const refForm = ref<VForm>()
const notificaVisible =ref(false)
const message = ref('')
const color = ref('')
const check = ref(0)
const loadingPage = ref(false)
const isDialogVisible = ref(false)


const confirm = () => {
  isDialogVisible.value = true
}

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
  editedItem.value.t_5 = editedItem.value.r_5
  editedItem.value.r_5 = null
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
  check.value = check.value + 1
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
    <VForm
      ref="refForm"
      @submit.prevent="() => {}"
    >
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
                :rules="[requiredValidator]"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="15"
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
                :rules="[requiredValidator]"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="15"
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
                for="q-4"
              >{{ t('Questionario-sa8000.d4') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_4"
                :rules="[requiredValidator]"
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
              >{{ t('Questionario-sa8000.d5') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="10"
            >
              <AppTextField
                v-model="editedItem.t_5"
                :readonly="!!editedItem.stato"
                type="string"
              />
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="15"
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
                :rules="[requiredValidator]"
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
                for="q-9"
              >{{ t('Questionario-sa8000.d9') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_9"
                :rules="[requiredValidator]"
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
                for="q-10"
              >{{ t('Questionario-sa8000.d10') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <VRadioGroup
                v-model="editedItem.r_10"
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="15"
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
                :rules="[requiredValidator]"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="15"
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
                :rules="[requiredValidator]"
                :readonly="!!editedItem.stato"
                inline
              >
                <VRadio
                  :label="t('Label.Si')"
                  value="15"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
              >{{ t('Questionario-14001.d32') }}</label>
            </VCol>

            <VCol
              cols="12"
              md="2"
            >
              <AppTextField
                v-model="editedItem.t_33"
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
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
                :rules="[requiredValidator]"
                :readonly="!!editedItem.stato"
                :label="t('Questionario-14001.no-italiani')"
                type="number"
              />
            </VCol>
          </VRow>
        </VCol>

        <VCol cols="12" md="12">
          <VRow no-gutters>

              <div class="text-center border border-success mt-6">
                <p class="mt-6 mb-0">
                  {{ t('Messaggio.Lettera-Impegno') }}
                </p>
                <div class="d-flex font-weight-medium text-body-1 align-center justify-center mx-auto mt-6 mb-6">
                  <VCheckbox
                    v-model="editedItem.r_36"
                    :label="t('Label.Confermo')"
                    color="success"
                    value="confermo"
                  />
                </div>
              </div>

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
</template>

<style scoped lang="scss">
.v-col-12{
  width: 100%;
  padding: 1px!important;
}
</style>
