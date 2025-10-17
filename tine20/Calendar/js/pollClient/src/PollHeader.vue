<template>
  <thead v-if="dates.length > 0" :style="`--summary-row-height: ${height}px`">
    <tr ref="summaryRow" v-if="showEventTitles">
      <th></th>
      <th v-for="date in dates" :key="date.dtstart" class="title">
        <div class="title">{{ date.summary }}{{!show_site || !date.event_site ? '' : ', ' + date.event_site.n_fn}}</div>
      </th>
    </tr>
    <tr class="tr-date">
      <th></th>
      <th v-for="date in dates" :key="date.dtstart">
        <span class="date">{{ new Date(date.dtstart).toLocaleDateString(undefined, {day: '2-digit', month: '2-digit', year: '2-digit'}) }}</span><br />
        <span class="date">{{ new Date(date.dtstart).toLocaleTimeString(undefined, {hour: '2-digit', minute:'2-digit'}) }}</span><br />
      </th>
    </tr>
  </thead>
</template>

<script setup>
import { ref } from 'vue'
import { useElementSize } from '@vueuse/core'

const summaryRow = ref()
const { height } = useElementSize(summaryRow)
</script>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'PollHeader',
  props: {
    dates: Array,
    show_site: {type: Boolean, default: false}
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
    }
  }
})

</script>

<style scoped>
th {
  border: 1px solid #ccc;
  padding: 5px;
  text-align: center;
}
/* Fixed first column */
thead th{
  position: sticky;
  left: 0;
  top: 0;
  background: #fff;
  z-index: 2;
  box-shadow: inset 0 0 0 0.5px #ccc;
}

thead tr.tr-date th{
  top: var(--summary-row-height);
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
}

th.title {
  min-height: 100px;
  overflow: visible;
}
</style>
