<template>
  <div class="row" v-if="poll">
    <slot name="header">
      <div class="col-md-8 col-sm-12">
        <h1>{{formatMessage('Poll')}}</h1>
      </div>
    </slot>
  </div>
  <div v-if="poll">
    <div v-if="closed" class="col-12 is-closed-message">
      <span class="text-danger">{{formatMessage('This poll is closed already')}}</span>
    </div>

    <div class="table-container">
<!--      Because native HTML tables do not scroll themselves.-->
      <table>
        <PollHeader :dates="events" :show_site="!poll.site"></PollHeader>
        <PollParticipant v-for="participant in participants" :participant="participant"
                         :dates="events"
                         :read_only="isReadonly(participant)"
                         :loading="loading"
                         @saveReply="saveReply"
                         @clicked-participant="clickedParticipant">
          <template #replyField="{ participant_id, date }" v-slot:replyField>
            <slot name="replyField" :participant_id="participant_id" :date="date"></slot>
          </template>
        </PollParticipant>
        <PollParticipant v-if="allowJoin && userId === null && !poll.closed"
                         :participant="{name: '', email: '', id: null}"
                         :dates="events"
                         :loading="loading"
                         @saveReply="saveReply"
                         :read_only="false"
                         @addParticipant="addParticipant">
        </PollParticipant>
      </table>
    </div>
    <PollControls @resetPoll="resetPoll"></PollControls>
  </div>
</template>

<script>
import {defineComponent} from 'vue'
import PollHeader from "./PollHeader.vue";
import PollParticipant from "./PollParticipant.vue";
import PollControls from "./PollControls.vue";

export default defineComponent({
  name: "Poll",
  components: {
    PollHeader,
    PollParticipant,
    PollControls
  },
  props: {
    poll: Object,
    events: Array,
    userId: {type: String, default: null},
    loading: Boolean,
    allowJoin: {type: Boolean, default: true},
    additionalData: {type: Object, default: null}
  },
  emits: ['saveReply', 'resetPoll', 'swapParticipant', 'addParticipant'],
  data () {
    return {
      participants: []
    }
  },
  watch: {
    poll () {
      if (this.poll.participants) {
        this.participants = this.poll.participants
        this.participants.sort((a, b) => {
          if (a.contact_id?.n_fn < b.contact_id?.n_fn) return -1
          if (a.contact_id?.n_fn > b.contact_id?.n_fn) return 1
          return 0
        })
      }
    },
  },
  mounted () {
    this.participants = this.poll?.participants ?? []
  },
  computed: {
    closed: function () {
      return !!this.poll.id_closed
        || !!this.poll.closed
        || !!this.poll.is_closed
    }
  },
  methods: {
    saveReply (reply) {
      this.$emit('saveReply', reply)
    },
    clickedParticipant (participant) {
      this.$emit('swapParticipant', participant)
    },
    resetPoll () {
      this.$emit('resetPoll')
    },
    isReadonly (participant) {
      let participantId = participant.user_id || participant.id
      return this.userId === null
        || this.userId !== participantId
        || this.closed
    },
    addParticipant (participant) {
      this.$emit("addParticipant", participant)
    }
  }
})
</script>

<style scoped>
  table {
    border: 1px solid #ccc;
    border-collapse: collapse;
    margin: 0 auto;
    width: 100%;
  }

  .table-container {
    overflow-x: auto;
  }

  .is-closed-message {
    margin: 20px 0 20px 0;
    font-size: 1.4rem;
    font-weight: bold;
  }
</style>
