<template>
  <thead v-if="dates.length > 0">
    <tr v-if="dates[0].summary">
      <th></th>
      <th v-for="date in dates" :key="date.dtstart" class="title">
        <div class="title">{{ date.summary }}</div>
      </th>
    </tr>
    <tr>
      <th></th>
      <th v-for="date in dates" :key="date.dtstart">
        <span class="date">{{ new Date(date.dtstart).toLocaleDateString(undefined, {day: '2-digit', month: '2-digit', year: '2-digit'}) }}</span><br />
        <span class="date">{{ new Date(date.dtstart).toLocaleTimeString(undefined, {hour: '2-digit', minute:'2-digit'}) }}</span><br />
      </th>
    </tr>
  </thead>
</template>

<script>
import {defineComponent} from 'vue'

export default defineComponent({
  name: "PollHeader",
  props: {
    dates: Array
  }
})
</script>

<style scoped>
th {
  border: 1px solid #ccc;
  padding: 5px;
  text-align: center;
}

div.title {
  writing-mode: vertical-lr;
  display: inline-block;
  transform: rotate(-170deg);
  text-align: left;
  white-space: nowrap;
}

th.title {
  height: 100px;
  overflow: visible;
  text-align: left;
  padding: 10px 5px 5px 20px;
}
</style>
