<template>
  <thead v-if="dates.length > 0">
    <tr ref="summaryRow" v-if="showEventTitles">
      <th :rowspan="hasTypes ? 3 : 2" class="top-left"><slot name="top-left"></slot></th>
      <th v-for="date in dates" :key="date.dtstart" class="title name">
        <div class="title name">{{ date.summary }}{{!show_site || !date.event_site ? '' : ', ' + date.event_site.n_fn}}</div>
      </th>
    </tr>
    <tr v-if="hasTypes">
      <th v-for="date in dates" :key="date.dtstart" class="title tags">
        <TTag class="type-tag" v-for="type in date.event_types"
              :text="type.event_type.name"
              :description="type.event_type.description"
              :tagColor="type.event_type.color">
        </TTag>
      </th>
    </tr>
    <tr class="tr-date">
      <th v-if="!showEventTitles" class="top-left">&nbsp;</th>
      <th v-for="date in dates" :key="date.dtstart">
        <span class="date">{{ format_date(date.dtstart, '2-digit') }}</span> <span class="time">{{ format_time(date.dtstart, 'short') }}</span>
      </th>
    </tr>
  </thead>
</template>

<script>
import { defineComponent } from 'vue'
import TTag from "../../../../Tinebase/js/vue/components/TTag.vue";
import { format_date, format_time } from 'Tinebase/js/util/datetimeformat';

export default defineComponent({
  name: 'PollHeader',
  methods: {format_date, format_time},
  props: {
    dates: Array,
    show_site: {type: Boolean, default: false}
  },
  components: {
    TTag
  },
  computed: {
    showEventTitles () {
      let anySummary = false
      let anySite = false
      for (let i = 0; i < this.dates.length; i++) {
        let date = this.dates[i]
        if (date.summary) {
          anySummary = true
        }
        if (date.event_site) {
          anySite = true
        }
      }
      return anySummary || (this.show_site && anySite);
    },
    hasTypes () {
      let anyType = false
      for (let i = 0; i < this.dates.length; i++) {
        let date = this.dates[i]
        if (date.event_types) {
          anyType = true
        }
      }
      return anyType
    }
  }
})

</script>

<style scoped>
th {
  border: none;
  padding: 0;
  text-align: center;
  border-collapse: collapse;
}

thead th{
  background: #fff;
  z-index: 2;
}

/* Header cell of the fixed column needs higher z-index */
thead th:first-child {
  z-index: 3;
}

thead th.top-left {
  background: #fff;
  z-index: 3;
}

div.title {
  text-align: left;
  text-wrap: wrap;
  max-width: 250px;
  min-width: 150px;
  padding: 15px 10px;
  margin: auto;
}

th.title {
  min-height: 100px;
  overflow: visible;
  vertical-align: top;
}

th.tags {
  padding: 4px 4px 4px 0;
}

.type-tag {
  width: 100%;
  margin-bottom: 4px;
}

tr.tr-date th {
  padding: 4px;
}

span.time {
  margin-left: 5px
}
</style>
