<template>
  <div class="row poll-title" v-if="poll" :style="{width: headWidth + 'px'}">
    <slot name="header">
      <div class="col-md-8 col-sm-12">
        <h1>{{formatMessage('Poll')}}</h1>
      </div>
    </slot>
    <div v-if="!!poll && closed" class="col-12 is-closed-message">
      <span class="text-danger">{{formatMessage('This poll is closed already')}}</span>
    </div>
  </div>
  <div v-if="poll">
    <div class="table-container" ref="table">
<!--      Because native HTML tables do not scroll themselves.-->
      <table class="poll" id="poll-table" ref="innerTable">
        <PollHeader :dates="events" :show_site="!poll.site">
          <template v-slot:top-left>
            <div class="group" v-if="poll.scheduling_role">
              <span class="group-dot" :style="poll.scheduling_role.color ? {backgroundColor: poll.scheduling_role.color} : {}">&nbsp;</span><span class="group-name">{{poll.scheduling_role.name}}</span>
            </div>
            <div class="deadline" v-if="additionalData !== null && additionalData.deadline_message">{{additionalData.deadline_message}}</div>
          </template>
        </PollHeader>
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
  </div>
</template>

<script>
import {defineComponent, ref} from 'vue'
import PollHeader from "./PollHeader.vue";
import PollParticipant from "./PollParticipant.vue";

export default defineComponent({
  name: "Poll",
  components: {
    PollHeader,
    PollParticipant
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
      participants: [],
      headWidth: 0
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
      this.$nextTick(function () {
        this.headWidth = this.getTableWidth()
      })
    },
  },
  mounted () {
    this.participants = this.poll?.participants ?? []
    window.addEventListener('resize', this.onResize)
    this.$nextTick(function () {
      this.headWidth = this.getTableWidth()
    })
  },
  computed: {
    closed: function () {
      return !!this.poll.id_closed
        || !!this.poll.closed
        || !!this.poll.is_closed
    }
  },
  methods: {
    onResize (e) {
      this.headWidth = this.getTableWidth()
    },
    saveReply (reply) {
      this.$emit('saveReply', reply)
    },
    clickedParticipant (participant) {
      this.$emit('swapParticipant', participant)
    },
    isReadonly (participant) {
      let participantId = participant.user_id || participant.id
      return this.userId === null
        || this.userId !== participantId
        || this.closed
    },
    addParticipant (participant) {
      this.$emit("addParticipant", participant)
    },
    getTableWidth () {
      const table = this.$refs.table
      if (!table) {
        return 0
      }
      const innerTable = this.$refs.innerTable

      // Bootstrap margins and paddings require the +6/+12 corrections
      return Math.min(table.offsetWidth + 6, innerTable.offsetWidth + 12)
    }
  },
})
</script>

<style scoped>
  .poll-title {
    margin-left: auto;
    margin-right: auto;
    display: table;
  }

  table {
    border: none;
    border-spacing: 5px;
    background-color: white;
    margin: 0 auto;
  }

  .table-container {
    overflow-x: auto;
  }

  .is-closed-message {
    margin: 20px 0 20px 0;
    font-size: 1.4rem;
    font-weight: bold;
    padding: 0 10px 0 10px;
  }
</style>

<style>
  table.poll {
    font-size: 0.9rem;
    border-collapse: separate;
    border-spacing: 6px;
  }

  table.poll th {
    background-color: #e8ecf1;

  }
  table.poll tr,
  table.poll thead,
  table.poll td {
    border: none;
  }

  table.poll tr td.table-info {
    background-color: #bec6d1;
  }

  div.alert {
    width: 100%;
  }
  th.top-left {
    padding: 5px;
  }
  div.group {
    text-align: left;
    overflow: visible;
    white-space: nowrap;
    width: 100%;
  }
  span.group-dot {
    width: 1rem;
    height: 1rem;
    line-height: 1rem;
    border-radius: 50%;
    display: inline-block;
    margin-right: .2rem;
  }
  span.group-name {
    font-size: 1rem;
    line-height: 1rem;
    font-variant: small-caps;
    font-weight: normal;
    text-overflow: ellipsis;
  }
  div.deadline {
    font-size: .8rem;
    font-weight: normal;
    color: #444;
    text-align: left;
  }
</style>
