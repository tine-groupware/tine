<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jan Evers <jevers@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<template>
  <div class="tag" :style="{backgroundColor: (tagColor ?? '#3a8acc')}" :class="getColorClass">
    <span v-if="show" class="text" :class="{'text-margin': showClose}" :title="description">{{text}}</span>
    <span v-if="showClose" class="close" @click="closeEvent">&#128473;</span>
  </div>
</template>
<script>
import { contrastColors } from 'Tinebase/js/util/contrastColors'

export default {
  name: 'TTag',
  props: {
    text: String,
    show: { type: Boolean, default: true },
    showClose: { type: Boolean, default: false },
    description: { type: String, default: '' },
    tagColor: { type: String, default: null }
  },
  emits: ['close'],
  computed: {
    getColorClass () {
      const brightness = contrastColors.getBrightness(this.tagColor)
      return brightness > 127 ? 'dark' : 'bright'
    }
  },
  methods: {
    closeEvent () {
      if (this.showClose) {
        this.$emit('close')
      }
    }
  }
}
</script>

<style scoped>
.tag {
  display: inline-block;
  padding: 1px 0.6rem;
  border-radius: 0.6rem;
  height: 1.2rem;
  position: relative;
}

.text {
  display: block;
  font-size: 0.75rem;
  left: 0.2rem;
  top: 0.1rem;
  cursor: default;
}

.text-margin {
  margin-right: 0.8rem;
}

.close {
  display: block;
  position: absolute;
  right: 0.2rem;
  top: 0.2rem;
  background: #c4dcf0;
  color: #3a8acc;
  height: 0.8rem;
  line-height: 0.8rem;
  width: 0.8rem;
  border-radius: 0.4rem;
  font-size: 0.5rem;
  margin: 0;
  cursor: pointer;
}

.dark {
  color: var(--bs-heading-color);
}

.bright {
  color: var(--bs-light);
}
</style>
