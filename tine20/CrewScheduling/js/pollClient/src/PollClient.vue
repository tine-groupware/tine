<template>
  <div class="container">
    <Poll :poll="poll"
          :events="events"
          :userId="participantId"
          :loading="loading"
          :allow-join="false"
          :additional-data="additionalData"
          @saveReply="saveReply"
          @resetPoll="loadPoll"
          @swapParticipant="swapParticipant"
    >
      <template v-slot:header>
        <div class="row" v-if="poll">
          <div class="col-md-8 col-sm-12">
            <h1>{{formatMessage('Availability Poll')}}</h1>
            <h3 class="poll-event" v-if="additionalData !== null && additionalData.period_message">{{additionalData.period_message}}</h3>
            <h3 class="poll-event">
              <span :style="poll.scheduling_role.color ? {color: poll.scheduling_role.color} : {}">{{poll.scheduling_role.name}}</span><span v-if="poll.site && poll.site.n_fn">, </span>
              <span v-if="poll.sites && poll.sites.length > 0"> (<span v-for="(pollSite, i) in poll.sites" :style="pollSite.site_id.color ? {color: '#'+pollSite.site_id.color} : {}">{{pollSite.site_id.n_fn}}{{ i < poll.sites.length -1 ? ', ': '' }}</span>)</span>
              <span v-if="additionalData !== null && additionalData.deadline_message">&nbsp;{{additionalData.deadline_message}}</span>
            </h3>
            <BAlert variant="warning" v-model="poll.account_grants.managePollGrant">
              <h4 class="alert-heading">{{ formatMessage('You are in admin mode') }}</h4>
              <p>{{ formatMessage('Click on the name of a participant to modify their responses.') }}</p>
            </BAlert>
          </div>
        </div>
      </template>
    </Poll>
  </div>
  <div v-if="!loading && !poll" class="row">
    <BAlert variant="danger" v-model="showError">{{formatMessage('Invalid Link')}}</BAlert>
  </div>
</template>

<script>
import axios from 'axios'
import { Poll } from 'Calendar/js/pollClient/src';
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-next/dist/bootstrap-vue-next.css'
import { BAlert } from 'bootstrap-vue-next'
import { format_date } from 'Tinebase/js/util/datetimeformat'

export default {
  components: {Poll, BAlert},
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

  computed: {
    additionalData () {
      if (this.poll === null) {
        return null
      }
      let data = {}
      if (!!this.poll.deadline || !this.poll.is_closed) {
        data.deadline_message = this.getDeadlineMessage()
      }

      if (!!this.poll.from && !!this.poll.until) {
        data.period_message = this.getPeriodMessage()
      }

      return data
    },
    showError: function () {
      return this.errorStatus !== null
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
      if (this.poll.account_grants.managePollGrant) {
        this.participantId = participant.id
      }
    },

    getDeadlineMessage () {
      let deadline = new Date(this.poll.deadline)
      let now = new Date()
      let diffDays = Math.floor((deadline - now) / (1000 * 60 * 60 * 24))

      if (diffDays <= 0) {
        return this.formatMessage('closed')
      }

      if (diffDays > 10) {
        let deadlineString = format_date(deadline, 'numeric')
        return this.formatMessage('Answers possible until {deadline}', {deadline: deadlineString})
      } else {
        return this.formatMessage('Answers possible for {diffDays} more days', {diffDays: diffDays})
      }
    },

    getPeriodMessage() {
      let from = format_date(this.poll.from, 'numeric')
      let until = format_date(this.poll.until, 'numeric')

      let events = this.formatMessage('events')
      if (this.poll.event_types && this.poll.event_types.length > 0) {
        let eventNames = []
        this.poll.event_types.every(function (type) {
          eventNames.push(type.event_type_id.name)
        })
        events = eventNames.join(', ')
      }

      return this.formatMessage('For {events} between {from} and {until}', {from: from, until: until, events: events})
    }
  }
}
</script>

<style>
  h3.poll-event {
    font-size: 1.25rem;
    font-weight: normal;
  }
  h2.poll-event {
    font-size: 1.5rem;
    font-weight: normal;
  }
</style>
