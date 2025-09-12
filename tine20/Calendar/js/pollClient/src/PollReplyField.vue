<template>
  <div v-if="reply" class="reply-field" :class="[reply.status.toLowerCase(), read_only ? '' : 'clickable']" @click="next(reply)">&nbsp;
    <slot name="replyField" :participant_id="participant_id" :date="date">
    </slot>
  </div>
  <div v-else class="reply-field needs-action" @click="newReply()">&nbsp;</div>
</template>

<script>
import {defineComponent} from 'vue'

const nextStatus = {
  'NEEDS-ACTION': 'ACCEPTED',
  'ACCEPTED': 'DECLINED',
  'DECLINED': 'TENTATIVE',
  'TENTATIVE': 'NEEDS-ACTION',
}

const baseUrl = window.location.origin

export default defineComponent({
  name: "PollReplyField",
  props: {
    date: Object,
    reply: Object,
    participant_id: String,
    read_only: Boolean
  },
  emits: [
    'reply', 'saveReply'
  ],
  data () {
    return {
      nextStatus: nextStatus
    }
  },

  methods: {
    next (reply) {
      if (this.read_only) { return }
      reply.status = this.nextStatus[reply.status] ?? 'NEEDS-ACTION'
      this.$emit('saveReply', reply)
    },
    newReply () {
      if (this.read_only) { return }
      let reply = {
        status: this.nextStatus['NEEDS-ACTION'],
        poll_participant_id: this.participant_id,
        event_ref: this.date.id
      }

      this.$emit('reply', reply)
    }
  }
})
</script>

<style scoped>
.needs-action {
  background-color: #A0A0A0;
  background-image: url('../../../../images/icon-set/icon_invite.svg');
}
.accepted {
  background-color: #90ff90;
  background-image: url('../../../../images/icon-set/icon_calendar_attendee_accepted.svg');
}
.declined {
  background-color: #ffa0a0;
  background-image: url('../../../../images/icon-set/icon_calendar_attendee_cancle.svg');
}
.tentative {
  background-color: #ffff90;
  background-image: url('../../../../images/icon-set/icon_calendar_attendee_tentative.svg');
}

.reply-field {
  height: 100%;
  width: 100%;
  background-repeat: no-repeat;
  background-position: center center;
  background-size: 24px 24px;
}

.clickable {
  cursor: pointer;
}
</style>
