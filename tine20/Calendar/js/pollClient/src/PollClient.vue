<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jan Evers <j.evers@metaways.de>
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<template>
  <div id="root">
    <div class="container">
      <b-alert class="global-error" variant="danger" v-model="showGlobalError">{{globalError}}</b-alert>
      <p v-if="publicUrl.length > 0"><a :href="publicUrl">{{formatMessage('Switch to public poll')}}</a></p>
      <template v-if="!transferingPoll && !askPassword && showPoll">
        <Poll :poll="{participants: participants, closed: poll.closed}" :events="poll.alternative_dates" :userId="activeAttendee.user_id" :loading="transferingPoll"
              @saveReply="onApplyChanges" @resetPoll="loadPoll" @addParticipant="addParticipant">

          <template v-slot:header>
            <div class="col-md-8 col-sm-12">
              <h1 class="poll-event">{{poll.event_summary}}</h1>
              <h2 class="poll-name" v-if="poll.name && poll.name.length > 0">{{poll.name}}</h2>
            </div>
            <div class="col-md-4 col-sm-12 text-end">
              <a :href="poll.config.brandingWeburl">
                <img style="max-width: 300px; max-height: 80px" :src="`${window.location.origin}/${poll.config.installLogo}`" :alt="poll.config.brandingTitle"/>
              </a>
            </div>
            <div class="row greetings">
              <div class="col-md-6 col-sm-12 greetings-text">
                <p>
                  <span v-if="activeAttendee.id !== null">{{formatMessage('Welcome {name}', {name: activeAttendee.name})}}</span>
                  <span v-if="poll.config.is_anonymous && poll.locked === '1'"><br />{{formatMessage('This poll is closed. No attendees can be added.')}}</span>
                  <span v-if="poll.closed === '1'"><br />{{formatMessage('This poll is already closed.')}}</span>
                </p>
              </div>
              <div class="col-md-6 col-sm-12 text-end">
                <b-btn v-if="activeAttendee.id !== null" @click="onOtherUser" variant="primary">{{formatMessage('I am not {name}', {name: activeAttendee.name})}}</b-btn>
                <b-btn v-else @click="redirectToTine" variant="primary">{{formatMessage('Login')}}</b-btn>
              </div>
            </div>
          </template>

          <template #replyField="{ participant_id, date }" v-slot:replyField>
            <span @click.stop="showCalendar(date, participant_id)" class="calendar-symbol"
              v-if="!poll.config.is_anonymous && activeAttendee.user_id !== null && activeAttendee.user_id === participant_id">
              <img :src="getCalendarIcon(date)" alt="formatMessage('Calendar')" />
            </span>
          </template>
        </Poll>
        <div class="row footer" v-if="!hidegtcmessage">
          <div class="col-md-12">
            <p>
              <a href="#" @click.prevent="showGtc = true">{{formatMessage('By using this service you agree to our terms and conditions')}}</a>.
            </p>
          </div>
        </div>
      </template>
      <div>
        <b-modal ref="loadMask" v-model="transferingPoll" hide-header hide-footer no-fade no-close-on-esc no-close-on-backdrop centered>
          <div class="col-xs-1 text-center">
            <b-spinner></b-spinner>
            <br>
            <span>{{ formatMessage('Please wait...') }}</span>
            <!-- <spinner size="medium" :message="formatMessage('Please wait...')"></spinner> -->
          </div>
        </b-modal>
      </div>
      <div>
        <b-modal ref="gtc" hide-footer centered :title="formatMessage('General terms and conditions')" v-model="showGtc">
          {{gtcText}}
        </b-modal>
      </div>
      <div>
        <b-modal ref="linkInfo" hide-footer centered :title="formatMessage('Use your personal link please.')" v-model="usePersonalLink" @hide="usePersonalLink">
          <p>{{formatMessage('Use your personal link please.')}}</p>
          <p>{{formatMessage('We have sent it to your email address again.')}}</p>
          <p>{{formatMessage('If you did not receive the link, please contact the organiser.')}}</p>
        </b-modal>
      </div>
      <div>
        <b-modal ref="password" v-model="askPassword" :title="formatMessage('Password Protected Poll')" hide-footer centered no-close-on-esc no-close-on-backdrop>
          <form>
            <p>{{formatMessage('Please enter the survey password to continue.')}}</p>
            <label for="password">{{formatMessage('Password')}}<input id="password" type="password" class="form-control" v-model="password" /></label>
            <b-btn variant="primary" @click.prevent="submitPassword" type="submit">{{formatMessage('Submit')}}</b-btn>
            <b-alert variant="danger" :show="wrongPassword">
              {{formatMessage('Wrong password!')}}
            </b-alert>
          </form>
        </b-modal>
      </div>
      <div>
        <!-- formatMessage('Calendar of {name}', {name: activeAttendee.name}) -->
        <b-modal class="calendar-window" ref="calendarWindow" v-model="showCalendarModal" @hide="showCalendarModal = false" hide-footer centered size="lg" :title="formatMessage('Calendar of {name}', {name: activeAttendee.name})">
          <iframe v-if="showCalendarModal" :src="calendarUrl" class="calendar"></iframe>
        </b-modal>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { BModal, BButton, BButtonGroup, BAlert, BSpinner } from 'bootstrap-vue-next'
import _ from 'lodash'
import Poll from "./Poll.vue";

export default {
  data () {
    return {
      baseUrl: '',
      globalError: '',
      showGlobalError: false,
      transferingPoll: true,
      showPoll: true,
      poll: {},
      activeAttendee: { name: '', email: '', id: null, user_id: null },
      forceNewAttendee: false,
      showGtc: false,
      gtcText: '',
      hidegtcmessage: true,
      askPassword: false,
      password: '',
      wrongPassword: false,
      statusList: [],
      defaultStatus: 'NEEDS-ACTION',
      newAccountContact: false,
      showCalendarModal: false,
      calendarUrl: '',
      usePersonalLink: false,
      publicUrl: '',
      highlightName: false,
      highlightEmail: false,
      events: [],
      participantId: null,
      participants: []
    }
  },

  props: {
    pollCode: String,
    userCode: {type: String, required: false, default: null},
    key: {type: String, required: false, default: null},
  },

  watch: {
    poll () {
      if (this.activeAttendee.user_id === null || this.newAccountContact === true) {
        _.each(this.poll.alternative_dates, (date) => {
          this.activeAttendee[date.id] = { status: this.defaultStatus }
        })
      } else {
        _.each(this.poll.attendee_status, (attendee) => {
          if (attendee.user_id === this.activeAttendee.user_id) {
            this.activeAttendee.status = attendee.status
            this.activeAttendee.name = attendee.name
            this.activeAttendee.user_type = attendee.user_type
            this.activeAttendee.user_id = attendee.user_id
          }
        })
      }
    },
    globalError () {
      this.showGlobalError = this.globalError.length > 0;
    }
  },

  components: {
    Poll,
    'b-modal': BModal,
    'b-btn': BButton,
    'b-button-group': BButtonGroup,
    'b-spinner': BSpinner,
    'b-alert': BAlert
  },

  mounted () {
    this.loadPoll()

    document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none'

    let urlParams = window.location.href.substring(window.location.href.indexOf('poll/') + 5).split('/')

    if (urlParams.length > 1) {
      this.activeAttendee.id = urlParams[1]
    }

    this.baseUrl = window.location.href.substr(0, window.location.href.indexOf('/Calendar') + 1)
  },

  methods: {
    onApplyChanges () {
      this.transferingPoll = true
      let action = 'post'
      if (this.activeAttendee.id === null) {
        _.each(this.activeAttendee.status, (date) => {
          date.status = this.activeAttendee[date.id].status
        })
      }

      let needUpdate = false
      let url = this.baseUrl + 'Calendar/poll/' + this.poll.id
      let newAttendee = false

      let payload = { status: [] }
      if (this.activeAttendee.id === null || this.newAccountContact) {
        url = this.baseUrl + 'Calendar/poll/join/' + this.poll.id
        newAttendee = true
        needUpdate = true

        payload.name = this.activeAttendee.name
        payload.email = this.activeAttendee.email

        _.each(this.poll.alternative_dates, (date) => {
          payload.status.push({
            cal_event_id: date.id,
            status: this.activeAttendee[date.id]?.status ?? this.defaultStatus
          })
        })
      } else {
        _.each(this.poll.attendee_status, (attendee) => {
          _.each(attendee.status, (status) => {
            if (status.status_authkey === null || status.status === status.initial) {
              return
            }

            needUpdate = true
            let datePayload = {
              cal_event_id: status.cal_event_id,
              status: status.status,
              user_type: status.user_type,
              user_id: status.user_id,
              status_authkey: status.status_authkey,
              seq: status.seq
            }
            payload.status.push(datePayload)
          })
        })
      }

      if (!needUpdate) {
        this.transferingPoll = false
        return
      }

      let options = { auth: { password: this.password } }

      axios[action](url, payload, options).then((response) => {
        if (newAttendee === true && this.activeAttendee.id === null) {
          let first = _.head(response.data)

          if (typeof first === 'undefined') {
            this.transferingPoll = false
            this.loadPoll()
          }

          let target = window.location.href.replace(/\/*$/, '') +
            '/' + first.user_type + '-' + first.user_id +
            '/' + first.status_authkey

          window.location.replace(target)
        }

        this.transferingPoll = false
        this.loadPoll()
      }).catch(error => {
        if (error.response.status === 401) {
          if (typeof error.response.data === 'undefined') {
            return
          }

          if (error.response.data.indexOf('poll is locked') > -1) {
            this.poll.locked = 1
          }

          if (error.response.data.indexOf('please log in') > -1) {
            this.redirectToTine()
          }

          if (error.response.data.indexOf('use personal link') > -1) {
            this.usePersonalLink = true
          }
        } else {
          console.log(error)
          console.log(arguments)
        }
      })
    },
    redirectToTine () {
      window.location.replace('/')
    },
    onCancelChanges () {
      this.loadPoll()
    },
    loadPoll () {
      this.transferingPoll = true
      let url = window.location.href.replace(/\/view\//, '/')
      axios.get(url, {
        auth: {
          password: this.password
        }
      }).then(response => {
        if (typeof response.data === 'string') {
          this.globalError = this.formatMessage('An unexpected error occurred.')
          this.transferingPoll = false
          this.showPoll = false
          return
        }

        this.poll = response.data

        this.formatMessage.setup({ locale: this.poll.config.locale.locale || 'en' })

        if (this.poll.config.has_gtc === true) {
          this.hidegtcmessage = false
          this.retrieveGTC()
        }

        this.askPassword = this.poll.password !== null && this.password !== this.poll.password

        if (!this.poll.config.current_contact || this.forceNewAttendee) {
          this.activeAttendee.id = null
          this.forceNewAttendee = true
        } else {
          this.activeAttendee.id = this.poll.config.current_contact.type + '-' + this.poll.config.current_contact.id
          this.activeAttendee.name = this.poll.config.current_contact.n_fn
          this.activeAttendee.email = this.poll.config.current_contact.email
          this.activeAttendee.user_id = this.poll.config.current_contact.id
        }

        this.newAccountContact = true
        if (this.activeAttendee.id === null) {
          this.newAccountContact = false
        } else {
          _.each(this.poll.attendee_status, (attendee) => {
            if (attendee.user_id === this.activeAttendee.user_id) {
              this.newAccountContact = false
            }
          })
        }

        this.defaultStatus = this.poll.config.status_available.default

        if (typeof this.poll.config.jsonKey !== 'undefined') {
          this.$tine20.setJsonKey(this.poll.config.jsonKey)
        }

        let previous = null
        let first = null
        for (var i = 0; i < this.poll.config.status_available.records.length; i++) {
          var status = this.poll.config.status_available.records[i]

          var cellclass = 'table-light'
          switch (status.id) {
            case 'ACCEPTED':
              cellclass = 'table-success'
              break
            case 'DECLINED':
              cellclass = 'table-danger'
              break
            case 'TENTATIVE':
              cellclass = 'table-warning'
              break
          }

          this.statusList[status.id] = {
            icon: status.icon,
            cellclass: cellclass
          }

          if (first === null) {
            first = status.id
          }

          if (previous !== null) {
            this.statusList[previous].next = status.id
          }

          previous = status.id
        }
        this.statusList[previous].next = first

        this.participants = []
        _.each(this.poll.attendee_status, (attendee) => {
          let participant = attendee
          participant.poll_replies = []
          _.each(attendee.status, (status) => {
            status.initial = status.status
            participant.poll_replies.push(status)
          })
          this.participants.push(participant)
        })

        this.events = []
        _.each(this.poll.alternative_dates, (date) => {
          this.events.push(date)
        })

        this.transferingPoll = false
      }).catch(error => {
        if (error.response.status === 401) {
          if (error.response.data.indexOf('authkey mismatch') > -1) {
            this.askPassword = false
            this.globalError = this.formatMessage('Use your personal link please.')
            this.publicUrl = window.location.pathname.replace(/\/$/, '').split('/').slice(0, 5).join('/')
            this.showPoll = false
            this.transferingPoll = false
          } else {
            if (this.askPassword) {
              this.wrongPassword = true
            }
            this.askPassword = true
          }
        }
        else if (error.response.status === 404) {
          this.globalError = this.formatMessage('Invalid link')
          this.showPoll = false
          this.transferingPoll = false
        } else {
          console.log(error)
          console.log(arguments)
        }
      })
    },
    onOtherUser () {
      let urlParams = window.location.pathname.replace(/\/$/, '').split('/')

      if (urlParams.length > 5) {
        let target = urlParams.slice(0, -2).join('/')
        window.location.replace(target)
      }

      this.newAccountContact = false
      this.forceNewAttendee = true
      this.activeAttendee = {
        id: null,
        user_id: null,
        name: '',
        email: '',
        status: []
      }

      this.$tine20.request('Tinebase.logout', {}).then(() => {
      }).catch(error => {
        console.log(error)
      }).then(() => {
        this.loadPoll()
      })
    },
    submitPassword () {
      this.wrongPassword = false
      this.loadPoll()
    },
    retrieveGTC () {
      if (this.gtcText.length > 0) {
        return
      }

      let url = window.location.href.substring(0, window.location.href.indexOf('/poll/')) + '/pollagb'
      axios.get(url).then(response => {
        this.gtcText = response.data
        if (this.gtcText.length === 0) {
          this.hidegtcmessage = true
        }
      }).catch(error => {
        console.log(error)
        console.log(arguments)
      })
    },
    showCalendar (date, participant_id) {
      let calendarUrl = null
      _.each(this.participants, (participant) => {
        if (participant.user_id === participant_id) {
          _.each(participant.status, (status) => {
            if (status.cal_event_id === date.id) {
              calendarUrl = status.info_url
              return false
            }
          })
        }
        if (calendarUrl !== null) {
          return false
        }
      })

      if (calendarUrl !== null) {
        this.calendarUrl = calendarUrl
        this.showCalendarModal = true
      }
    },
    getStatusIcon (statusId) {
      let iconUrl
      _.each(this.poll.config.status_available.records, (status) => {
        if (status.id === statusId) {
          iconUrl = this.baseUrl + status.icon
        }
      })
      return iconUrl
    },
    getCalendarIcon (date) {
      let start = date.dtstart
      return this.baseUrl + 'images/icon-set/icon_cal_' + new Date(start.replace(' ', 'T')).getDate() + '.svg'
    },
    addParticipant (participant) {
      this.activeAttendee = participant
      this.onApplyChanges()
    }
  }
}
</script>

<style scoped>
@import 'bootstrap/dist/css/bootstrap.css';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

#root {
  padding: 10px;
  color: #555;
}

h1 {
  font-size: 2rem;
  color: #000;
}

h2 {
  font-size: 1.5rem;
  color: #222;
}

button {
  background-color: #DCE8F5;
  color: #222;
  border: 1px solid #008CC9;
}

button:hover {
  background-color: #FFF;
  color: #222;
  border: 1px solid #008CC9;
}

button:active {
  background-color: #DCE8F5;
  color: #222;
  border: 2px solid #008CC9;
}

.greetings {
  margin-top: 10px;
  margin-bottom: 25px;
}

.greetings-text {
  position: relative;
}

.greetings-text p {
  position: absolute;
  bottom: 0;
  display: inline-block;
  margin-bottom: 0;
}

td.table-info {
  background-color: #DCE8F5;
}

td.icon-cell {
  text-align: center;
  vertical-align: middle;
}

tr.row-active td.icon-cell {
  filter: brightness(0.9);
}

tr.row-active td {
  font-weight: bold;
}

td.name-field {
  padding: 5px 5px 5px 5px;
}

td.editable {
  cursor: pointer;
  user-select: none;
}

input.name-field {
  border-radius: 0px;
  margin-bottom: 5px;
  float: left;
  padding: 2px 5px 2px 5px;
}

input.email-field {
  border-radius: 0px;
  padding: 2px 5px 2px 5px;
}

input.highlight {
  box-shadow: inset 0 0 8px #ff0000;
}

.footer {
  margin-top: 100px;
  padding: 5px;
}

th {
  font-weight: normal;
  text-align: center;
}

div.modified-marker {
  display: block;
  position: relative;
  float: left;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 8px 8px 0 0;
  border-color: #ff0000 transparent transparent transparent;
  margin: -0.75rem 0 0 -0.75rem;
}

.login-error {
  margin-top: 10px;
}

span.calendar-symbol {
  float: left;
  margin-right: -21px;
  margin-left: 5px;
}

span.calendar-symbol img {
  min-height: 20px;
  min-width: 20px;
}

iframe.calendar {
  width: 100%;
  border: none;
}

@media (min-height: 700px) {
  iframe.calendar {
    min-height: 600px;
  }
}

@media (max-height: 699px) {
  iframe.calendar {
    min-height: 400px;
  }
}
</style>

<style>
  .calendar-window .modal-body {
    padding: 0;
  }

  .icon-cell img {
    width: 24px;
    margin: -2px;
  }

  button {
    cursor: pointer;
  }

  .modal-lg {
    width: 90%;
    height: 90%;
  }

  .modal-content {
    max-height: none;
  }

@media (min-width: 992px) {
  .modal-lg {
    max-width: none;
    max-height: none;
  }
}
</style>
