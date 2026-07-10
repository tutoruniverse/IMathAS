<template>
  <div v-if="!!fbtext">
    <div v-show="active" class="questionpane introtext">
      <span>{{ label }}</span>:<br/>
      <div
        class="fbbox"
        ref="fbbox"
        v-html="fbtext"
      />
    </div>
  </div>
</template>

<script>
import { store } from '@/basicstore';

export default {
  name: 'Feedback',
  props: {
    active: {default: true },
    qn: { default: 'general' },
  },
  computed: {
    label () {
      return this.$t(this.qn=='general' ? 'gradebook-general_feedback' : 'gradebook-feedback');
    },
    fbtext () {
      if (this.qn === 'general') {
        return store.assessInfo?.feedback;
      } else {
        return store.assessInfo.questions[this.qn]?.feedback;
      }
    }
  },
  mounted () {
    if (this.fbtext) {
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.fbbox);
      window.initSageCell(this.$refs.fbbox);
      window.initlinkmarkup(this.$refs.fbbox);
    }
  },
};
</script>
