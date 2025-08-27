<script lang="ts" setup>
import { useNotStore } from '@/views/task/view/useNoteStore'
import type { ChatOut } from '@/views/task/note/type'

const store = useNotStore()

//console.log(store.activeChat)

interface MessageGroup {
  senderId: ChatOut['messages'][number]['senderId']
  messages: Omit<ChatOut['messages'][number], 'senderId'>[]
}

const contact = computed(() => ({
  id: store.activeChat?.contact.id,
  avatar: store.activeChat?.contact.avatar,
}))

const msgGroups = computed(() => {
  let messages: ChatOut['messages'] = []
/*
    let messages: ChatOut['messages'] = [
      {
        message: 'Hi',
        time: 'Mon Dec 10 2018 07:45:00 GMT+0000 (GMT)',
        senderId: 121,
        feedback: {
          isSent: true,
          isDelivered: true,
          isSeen: true,
        },
      },
      {
        message: 'Hello. How can I help You?',
        time: 'Mon Dec 11 2018 07:45:15 GMT+0000 (GMT)',
        senderId: 2,
        feedback: {
          isSent: true,
          isDelivered: true,
          isSeen: true,
        },
      },
      {
        message: 'Can I get details of my last transaction I made last month? 🤔',
        time: 'Mon Dec 11 2018 07:46:10 GMT+0000 (GMT)',
        senderId: 121,
        feedback: {
          isSent: true,
          isDelivered: true,
          isSeen: true,
        },
      },
      {
        message: 'We need to check if we can provide you such information.',
        time: 'Mon Dec 11 2018 07:45:15 GMT+0000 (GMT)',
        senderId: 2,
        feedback: {
          isSent: true,
          isDelivered: true,
          isSeen: true,
        },
      },
      {
        message: 'I will inform you as I get update on this.',
        time: 'Mon Dec 11 2018 07:46:15 GMT+0000 (GMT)',
        senderId: 2,
        feedback: {
          isSent: true,
          isDelivered: true,
          isSeen: true,
        },
      },
      {
        message: 'If it takes long you can mail me at my mail address.',
        time: String(dayBeforePreviousDay),
        senderId: 121,
        feedback: {
          isSent: true,
          isDelivered: false,
          isSeen: false,
        },
      },
      {
        message: 'If it takes long you can mail me at my mail address.',
        time: String(dayBeforePreviousDay),
        senderId: 120,
        feedback: {
          isSent: true,
          isDelivered: false,
          isSeen: false,
        },
      },
      {
        message: 'If it takes long you can mail me at my mail address.',
        time: String(dayBeforePreviousDay),
        senderId: 5,
        feedback: {
          isSent: true,
          isDelivered: false,
          isSeen: false,
        },
      },
    ]
*/
  const _msgGroups: MessageGroup[] = []

  if (store.activeChat!.chat) {
    //messages = store.activeChat!.chat.messages

    let msgSenderId = messages[0].senderId

    let msgGroup: MessageGroup = {
      senderId: msgSenderId,
      messages: [],
    }

    messages.forEach((msg, index) => {
      if (msgSenderId === msg.senderId) {
        msgGroup.messages.push({
          message: msg.message,
          time: msg.time,
          feedback: msg.feedback,
        })
      }
      else {
        msgSenderId = msg.senderId
        _msgGroups.push(msgGroup)
        msgGroup = {
          senderId: msg.senderId,
          messages: [
            {
              message: msg.message,
              time: msg.time,
              feedback: msg.feedback,
            },
          ],
        }
      }

      if (index === messages.length - 1)
        _msgGroups.push(msgGroup)
    })
  }

  return _msgGroups
})
</script>

<template>
  <div class="chat-log pa-1">
    <div
      v-for="(msgGrp, index) in msgGroups"
      :key="msgGrp.senderId + String(index)"
      class="chat-group d-flex align-start"
      :class="[{
        'flex-row-reverse': Number(msgGrp.senderId) === contact.id,
        'mb-1': msgGroups.length - 1 === index,
      }]"
    >
      <div
        class="chat-avatar"
        :class="Number(msgGrp.senderId) !== contact.id ? 'ms-4' : 'me-4'"
      >
        <VAvatar size="32">
          IMGG
        </VAvatar>
      </div>
      <div
        class="chat-body d-inline-flex flex-column"
        :class="Number(msgGrp.senderId) === contact.id ? 'align-end' : 'align-start'"
      >
        <p
          v-for="(msgData, msgIndex) in msgGrp.messages"
          :key="msgData.time"
          class="chat-content py-1 px-1 elevation-1"
          style="background-color: rgb(var(--v-theme-surface));"
          :class="[
            Number(msgGrp.senderId) !== contact.id ? 'chat-left' : 'bg-primary text-white chat-right',
            msgGrp.messages.length - 1 !== msgIndex ? 'mb-0' : 'mb-1',
          ]"
          v-html="msgData.message"
        >
        </p>
        <div :class="{ 'text-right': Number(msgGrp.senderId) !== contact.id }">
          <VIcon
            v-if="Number(msgGrp.senderId) !== contact.id"
            size="18"
            color="success"
          >
            ICON
          </VIcon>
          <span class="text-sm ms-1 text-disabled">{{ formatDate(msgGrp.messages[msgGrp.messages.length - 1].time, { year: 'numeric', month: '2-digit', day: 'numeric', hour: 'numeric', minute: 'numeric' }) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang=scss>
.chat-log {
  .chat-content {
    border-end-end-radius: 6px;
    border-end-start-radius: 6px;

    &.chat-left {
      border-start-end-radius: 6px;
    }

    &.chat-right {
      border-start-start-radius: 6px;
    }
  }
}
</style>
