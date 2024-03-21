<script setup lang="ts">
import welcome from '@images/custom/welcome.png'
import uscita from '@suoni/uscita.mp3'
import exit from '@images/custom/exit.png'
import {themeConfig} from "@themeConfig"
import {VNodeRenderer} from "@layouts/components/VNodeRenderer"
import {RpRegisterLog} from "@/views/reception/type"
import moment from "moment/moment";

interface Props {
  visitatoreData: RpRegisterLog
}

const props = defineProps<Props>()


const pricingPlans = [

  {
    name: 'Entrata',
    logo: welcome,
    entrata: true,
    stampaTessera: true,
  },
  {
    name: 'Uscita',
    logo: exit,
    entrata: false,
    stampaTessera: false,
  },
]

const submit = async (azione: number) => {
  props.visitatoreData.entrata = azione
  console.log(props.visitatoreData)
  const retuenData = await $api('/reception/storeRegister/' + props.visitatoreData.id, {
    method: 'POST',
    body: props.visitatoreData,
  })

  if(props.visitatoreData.stampa && retuenData.success === true){
    print_this(props.visitatoreData.nome, props.visitatoreData.azienda, retuenData.code, props.visitatoreData.username_wifi, props.visitatoreData.password_wifi, props.visitatoreData.data_scadenza)
  }
  var data = { soundurl : 'http://soundbible.com/mp3/analog-watch-alarm_daniel-simion.mp3'}
  var audio = new Audio(data.soundurl)
  audio.play();
}

function print_this(nome: string, azienda: string, code: string, wifi_user: string, wifi_pass: string, scadenza: string )
{
  let zpl = "^XA~TA000~JSN^LT0^MNW^MTD^PON^PMN^LH0,0^JMA^PR3,3~SD10^JUS^LRN^CI0^XZ\n" +
    "^XA\n" +
    "^MMT\n" +
    "^PW575\n" +
    "^LL0406\n" +
    "^LS0\n" +
    "^FO0,0^GFA,29952,29952,00072,:Z64:\n" +
    "eJzt3TFOw0AQBdC1UmyHWwqUXIMiUq6VgsJH81F8BA4QYfDaQXSRmBEK0fvFjKsna7ffX4qIyAPkMEcyXpkaYuZ52JxT0HnfnO+PX57KZd3dJcSU8rGuOgWdw9BWPwSdOrZ1CjJlN7X1FnXKek/nsHP+oUVyXEY3hZ19c8aw87SMXdzpl1GHsNOIGmbKbnH6BGcs2yHF0mU6+wRn+hovcadwbjvHJOeVw+FwOBwOh8PhcDgcDofD4XA4HA6Hw+FwOBwOh8PhcDgcDofD4XA4HA6Hw+FwOBwOh8PhcDgcDofD4XA4HA6Hw+FwOBwOh8PhcDgcDofD+YfOc5Jzb++QP6qT9R7+vb3zn9VfUBOcoeT1O2T1TXRxp11VVh9HVj9IWl9JVn9KVp9LVr9MnYLO1neT1b9TgrVC8/U/svqJsvqSRERE/iqf6PARug==:3476\n" +
    "^FO0,256^GFA,08704,08704,00068,:Z64:\n" +
    "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:42E2\n" +
    "^FT39,76^A0N,39,38^FH\\^FD" + nome + "^FS\n" +
    "^FT136,109^A0N,20,19^FH\\^FD" + azienda + "^FS\n" +
    "^FT39,156^A0N,20,19^FH\\^FDWifi-Username:^FS\n" +
    "^FT39,192^A0N,20,19^FH\\^FDWifi-password:^FS\n" +
    "^FT189,156^A0N,20,19^FH\\^FD" + wifi_user + "^FS\n" +
    "^FT189,192^A0N,20,19^FH\\^FD" + wifi_pass +"^FS\n" +
    "^FT39,240^A0N,20,19^FH\\^FDValido fino al:^FS\n" +
    "^FT189,240^A0N,20,19^FH\\^FD" + moment(String(scadenza)).format('MM/DD/YYYY') + "^FS\n" +
    "^FT361,281^BQN,2,6\n" +
    "^FH\\^FDLA," + code + "^FS\n" +
    "^FT92,378^A0N,20,19^FH\\^FD" + code + "^FS\n" +
    "^PQ1,0,1,Y^XZ";
  var ip_addr = '10.141.3.125'
  var url = "http://"+ip_addr+"/pstprnt";
  var method = "POST";
  var async = true;
  var request = new XMLHttpRequest();

  request.onload = function () {
    var status = request.status; // HTTP response status, e.g., 200 for "200 OK"
    var data = request.responseText; // Returned data, e.g., an HTML document.
  }

  request.open(method, url, async);
  request.setRequestHeader("Content-Length", zpl.length);

  // Actually sends the request to the server.
  request.send(zpl);
}
</script>

<template>
  <!-- 👉 Title and subtitle -->
  <VCardItem class="justify-center">
    <template #prepend>
      <div class="d-flex">
        <VNodeRenderer :nodes="themeConfig.app.logo" />
      </div>
    </template>

    <VCardTitle class="font-weight-bold text-capitalize text-h3 py-1">
      {{ themeConfig.app.title }}
    </VCardTitle>
  </VCardItem>
  <div class="text-center mb-4">
    <h4>Benvenuto</h4><h3>{{props.visitatoreData.nome}}</h3>

  </div>

  <!-- SECTION pricing plans -->
  <VRow>
    <VCol
      v-for="plan in pricingPlans"
      :key="plan.logo"
      v-bind="{md: 6}"
      cols="12"
    >
      <!-- 👉  Card -->
      <VCard
        flat
        border
      >

        <!-- 👉 Plan logo -->
        <VCardText>
          <VImg
            :height="140"
            :src="plan.logo"
            class="mx-auto mb-5"
          />

          <!-- 👉 Plan name -->
          <h3 class="text-h3 mb-1 text-center">
            {{ plan.name }}
          </h3>

          <!-- 👉 Plan actions -->
          <VBtn
            block
            :color="plan.entrata ? 'success' : 'error'"
            variant="tonal"
            @click="submit(plan.entrata)"
          >
            Clicca Qui
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
  <!-- !SECTION  -->
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.75rem;
}

.save-upto-chip {
  inset-block-start: -2rem;
  inset-inline-end: -7rem;
}

.annual-price-text{
  inset-block-end: 10%;
  inset-inline-start: 50%;
  transform: translateX(-50%);
}
</style>
