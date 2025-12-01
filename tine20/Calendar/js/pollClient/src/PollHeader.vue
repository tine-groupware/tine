<template>
  <thead v-if="dates.length > 0">
    <tr ref="summaryRow" v-if="showEventTitles">
      <th></th>
      <th v-for="date in dates" :key="date.dtstart" class="title">
        <div class="title">{{ date.summary }}{{!show_site || !date.event_site ? '' : ', ' + date.event_site.n_fn}}</div>
        <TTag class="type-tag" v-if="hasTypes" v-for="type in date.event_types" :text="type.event_type.name" :description="type.event_type.description"></TTag>
      </th>
    </tr>
    <tr class="tr-date">
      <th></th>
      <th v-for="date in dates" :key="date.dtstart">
        <span class="date">{{ format_date(date.dtstart, 'short') }}</span><br />
        <span class="date">{{ format_time(date.dtstart, 'short') }}</span><br />
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
  border: 1px solid #ccc;
  padding: 5px;
  text-align: center;
  border-collapse: collapse;
}
thead th{
  background: #fff;
  z-index: 2;
  box-shadow: inset 0 0 0 0.5px #ccc;
}

/* Header cell of the fixed column needs higher z-index */
thead th:first-child {
  z-index: 3;
  background: #fff;
}

div.title {
  text-align: center;
  text-wrap: wrap;
  max-width: 250px;
  min-width: 150px;
  padding: 15px 10px;
  margin: auto;
}

th.title {
  min-height: 100px;
  overflow: visible;
}

.type-tag {
  margin: 1px;
}
</style>
