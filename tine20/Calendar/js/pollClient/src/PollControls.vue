<template>
  <div class="row" :style="width ? { width: width + 'px' } : {}">
    <div class="col-md-12">
      <b-button-group>
        <b-btn @click="onCancelChanges" variant="secondary">{{formatMessage('Reload')}}</b-btn>
        <b-btn v-if="showSave" @click="onApplyChanges" variant="primary">{{formatMessage('Save')}}</b-btn>
      </b-button-group>
    </div>
  </div>
</template>

<script>
import {defineComponent} from 'vue'
import {BButton, BButtonGroup} from 'bootstrap-vue-next'

export default defineComponent({
  name: "PollControls",
  props: {
    width: {type: Number,required:false},
    showSave: {type: Boolean, default: false}
  },
  emits: ['savePoll', 'resetPoll'],
  components: {
    'b-btn': BButton,
    'b-button-group': BButtonGroup,
  },
  methods: {
    onApplyChanges () {
      this.$emit('savePoll')
    },
    onCancelChanges() {
      this.$emit('resetPoll')
    }
  }
})
</script>


<style scoped>
  .row {
    margin-top: 20px;
    text-align: right;
    display: table;
    margin-left: auto;
    margin-right: auto;
  }
  button.btn {
    border-radius: 0;
    border: none;
    background-color: #0a62a4;
  }
</style>
