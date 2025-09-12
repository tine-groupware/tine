<template>
  <tr v-if="dates && !!participant" :class="read_only ? 'inactive' : 'active'">
    <td class="table-info" v-if="participant.id || participant.user_id" @click="onClickParticipant(participant)">{{participant.contact_id?.n_fn || participant.name}}</td>
    <td v-else>
      <input type="text" name="name" v-model="participant.name" class="form-text" required="required" :placeholder="formatMessage('Name')" @blur="addParticipant"><br />
      <input type="email" name="email" v-model="participant.email" class="form-text" required="required" :placeholder="formatMessage('Email')" @blur="addParticipant">
    </td>
    <td v-for="date in dates" class="reply-field">
      <PollReplyField :date="date" :reply="getReply(date)" :participant_id="participant ? (participant.id || participant.user_id) : null" :read_only="fieldsReadonly" @reply="newReply" @saveReply="saveReply">
        <template #replyField="{ participant_id, date }" v-slot:replyField>
          <slot name="replyField" :participant_id="participant_id" :date="date"></slot>
        </template>
      </PollReplyField>
    </td>
  </tr>
</template>

<script>
import {defineComponent} from 'vue'
import PollReplyField from "./PollReplyField.vue";

export default defineComponent({
  name: "PollParticipant",
  components: {PollReplyField},
  props: {
    dates: Array,
    participant: Object,
    read_only: Boolean,
    loading: Boolean
  },
  emits: ['saveReply', 'clickedParticipant', 'addParticipant'],

  computed: {
    fieldsReadonly () {
      return this.read_only || this.loading || (!this.participant.id && !this.participant.user_id)
    }
  },

  methods: {
    getReply (date) {
      if (this.participant) {
        let correctReply = null;
        _.forEach(this.participant.poll_replies, (reply) => {
          let ref = reply.event_ref || reply.cal_event_id
          if (ref === date.id) {
            correctReply = reply
            return false
          }
        })
        return correctReply
      }
    },
    newReply(reply) {
      if (!this.participant.poll_replies) {
        this.participant.poll_replies = []
      }
      this.participant.poll_replies.push(reply)
      this.saveReply(reply)
    },
    addParticipant() {
      if (this.participant.name && this.participant.email) {
        this.$emit('addParticipant', this.participant)
      }
    },
    saveReply(reply) {
      this.$emit('saveReply', reply)
    },
    onClickParticipant(participant) {
      this.$emit('clickedParticipant', participant)
    }
  }
})
</script>

<style scoped>
.reply-field {
  min-width: 84px;
}
td {
  padding: 0;
  border: 1px solid #ccc;
  height: inherit;
}
tr {
  height: 4em;
}
tr.active {
  font-weight: bold;
  border: 2px solid #000;
}
tr.inactive {
  font-weight: normal;
}
td.table-info {
  padding: 5px;
}
input {
  margin: 0;
  border: none;
  padding: 0 5px 0 5px;
  box-sizing: border-box;
  width: 100%;
  height: 50%;
}
input:focus {
  outline: none;
  border: none;
}
input[type=text] {
  border-bottom: 1px solid #ccc;
  margin-bottom: -1px;
}
</style>
