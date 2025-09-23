<template>
  <div class="container">
    <Poll :poll="poll"
          :events="events"
          :userId="participantId"
          :loading="loading"
          :allow-join="false"
          @saveReply="saveReply"
          @resetPoll="loadPoll"
          @swapParticipant="swapParticipant"
    >
      <template v-slot:header>
        <div class="row" v-if="poll">
          <div class="col-md-8 col-sm-12">
            <h1>{{formatMessage('Availability Poll')}}</h1>
            <h2 class="poll-event">
              <span :style="poll.scheduling_role.color ? {color: poll.scheduling_role.color} : {}">{{poll.scheduling_role.name}}</span><span v-if="poll.site && poll.site.n_fn">, </span>
              <span  v-if="poll.site && poll.site.n_fn" :style="poll.site.color ? {color: '#'+poll.site.color} : {}">{{poll.site.n_fn}}</span>
            </h2>
          </div>
        </div>
      </template>
    </Poll>
  </div>
  <div v-if="!loading && !poll" class="row">
    <div v-if="errorStatus === 404" class="col-md-8 col-sm-12">
      <p>{{formatMessage('Invalid Link')}}</p>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Poll from 'Calendar/js/pollClient/src/Poll.vue';
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-next/dist/bootstrap-vue-next.css'

export default {
  components: {Poll},
  data () {
    return {
      pollId: null,
      participantId: null,
      loading: true,
      poll: null,
      events: [],
      errorStatus: null
    }
  },

  props: {
    pollCode: String,
    userCode: {type: String, default: null},
  },

  watch: {
    loading () {
      if (this.loading) {
        document.getElementsByClassName("tine-viewport-waitcycle")[0].style.display = "block";
      } else {
        document.getElementsByClassName("tine-viewport-waitcycle")[0].style.display = "none";
      }
    },
    pollId () {
      if (this.pollId !== null) {
        this.loadPoll()
      }
    }
  },

  mounted () {
    let waitIRef = null
    waitIRef = setInterval(() => {
      if (window.initialData) {
        if (window.initialData.pollId !== this.pollCode) {
          this.globalError = this.formatMessage('An unexpected error occurred.')
          return
        }
        if (this.userCode !== null && window.initialData.participantId !== this.userCode) {
          this.globalError = this.formatMessage('An unexpected error occurred.')
          return
        }
        this.pollId = window.initialData.pollId
        this.participantId = window.initialData.participantId
        clearInterval(waitIRef)
        this.loading = false
        document.getElementsByClassName("tine-viewport-poweredby")[0].style.position = "fixed";
      } else {
        window.location.reload()
      }
    }, 1000)
  },

  methods: {
    loadPoll () {
      this.loading = true
      let url = '/CrewScheduling/Poll/' + this.pollId
      if (this.participantId) {
        url += '/' + this.participantId
      }
      axios.get(url, {}).then(response => {
        if (typeof response.data === 'string') {
          this.globalError = this.formatMessage('An unexpected error occurred.')
          return
        }
        this.poll = response.data.poll
        let events = response.data.events
        events.sort((a, b) => {
          if (a.dtstart < b.dtstart) return -1
          if (a.dtstart > b.dtstart) return 1
          return 0
        })
        this.events = events
      }).catch(error => {
        this.errorStatus = error.response.status

        // redirect to login
        if (this.errorStatus === 403) {
          window.location.replace('/')
        }

        // redirect to correct URL for current user
        if (this.errorStatus === 303) {
          let url = '/CrewScheduling/view/Poll/' + this.pollId + '/' + error.response.data.participantId
          window.location.replace(url)
        }
      }).finally(() => {
        this.loading = false
      })
    },

    saveReply (reply) {
      if (reply.poll_participant_id !== this.participantId ) {
        this.loadPoll()
        return
      }
      this.loading = true

      let url = '/CrewScheduling/Poll/' + this.pollId + '/' + reply.poll_participant_id
      axios.post(url, reply, {}).then(response => {
        if (typeof response.data === 'string') {
          console.error(response)
          this.globalError = this.formatMessage('An unexpected error occurred.')
        }
      }).finally(() => {
        this.loading = false
        this.loadPoll()
      })
    },

    swapParticipant (participant) {
      if (!window.initialData.participantId) {
        this.participantId = participant.id
      }
    }
  }
}
</script>

<style>

</style>
