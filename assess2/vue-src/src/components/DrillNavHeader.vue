<template>
  <div id="drill-question-header">
    <div id="drill-question-select"
      role="navigation" :aria-label="$t('regions-qnav')"
    >
      <menu-button id="drillqnav"
        :options = "navOptions"
        :selected = "curOption"
        searchby = "dispname"
      >
        <template #button>
          <drill-question-list-item
            v-if = "curQData"
            :option = "curQData"
            :selected = "true"
          />
        </template>
        <template v-slot="{ option, selected }">
          <drill-question-list-item
            :option="option"
            :selected="selected"
          />
        </template>
      </menu-button>
      <span v-if="personalBestLabel" class="subdued small drill-personalbest">
        {{ personalBestLabel }}
      </span>
    </div>
    <div id="drill-timer-area">
      <timer
        v-if="isTiming"
        :total="drillsettings.n"
        :end="drillTimerEnd"
        :grace="0"
      />
      <span v-else-if="showCountUp" class="drill-countup">
        {{ elapsedDisplay }}
      </span>
    </div>
  </div>
</template>

<script>
import MenuButton from '@/components/widgets/MenuButton.vue';
import DrillQuestionListItem from '@/components/DrillQuestionListItem.vue';
import Timer from '@/components/Timer.vue';
import { store, actions } from '@/basicstore';

export default {
  name: 'DrillNavHeader',
  props: ['qn'],
  components: {
    MenuButton,
    DrillQuestionListItem,
    Timer
  },
  data: function () {
    return {
      timeoutHandle: null,
      elapsedSeconds: 0,
      elapsedTimer: null,
      countupStartTime: 0
    };
  },
  computed: {
    questionData () {
      if (this.qn < 0 || !store.assessInfo.questions.hasOwnProperty(this.qn)) {
        return null;
      }
      return store.assessInfo.questions[this.qn];
    },
    isDrilling () {
      return !!this.questionData && this.questionData.drillstatus === 1;
    },
    drillsettings () {
      return store.assessInfo.drillsettings;
    },
    isTiming () {
      return this.isDrilling &&
        this.drillsettings.style === 'time_maxcorrect' &&
        this.questionData.hasOwnProperty('drillend_in');
    },
    drillTimerEnd () {
      if (!this.isTiming) {
        return 0;
      }
      return new Date().getTime() + this.questionData.drillend_in * 1000;
    },
    showCountUp () {
      return this.isDrilling &&
        (this.drillsettings.style === 'count_time' ||
          this.drillsettings.style === 'count_correct_time' ||
          this.drillsettings.style === 'streak_time');
    },
    elapsedDisplay () {
      const m = Math.floor(this.elapsedSeconds / 60);
      const s = this.elapsedSeconds % 60;
      return m + ':' + (s < 10 ? '0' : '') + s;
    },
    hasIntro () {
      return (store.assessInfo.intro !== '' ||
        store.assessInfo.resources.length > 0 ||
        !!store.assessInfo?.feedback
      );
    },
    dispqn () {
      return this.qn + 1;
    },
    curOption () {
      // navOptions is offset by 1 when an intro entry is included at index 0
      return this.hasIntro ? this.dispqn : this.dispqn - 1;
    },
    curQData () {
      return this.navOptions[this.curOption] ?? null;
    },
    personalBest () {
      if (this.qn < 0 || !store.assessInfo.questions.hasOwnProperty(this.qn)) {
        return null;
      }
      const results = store.assessInfo.questions[this.qn].drillresults;
      if (!results || results.length === 0) {
        return null;
      }
      const style = store.assessInfo.drillsettings.style;
      let best = null;
      for (const r of results) {
        if (best === null) {
          best = r;
        } else if (style === 'time_maxcorrect') {
          if (r.correct > best.correct) { best = r; }
        } else if (style === 'count_correct_attempts' || style === 'streak_attempts') {
          if (r.attempts < best.attempts) { best = r; }
        } else if (r.time < best.time) {
          best = r;
        }
      }
      return best;
    },
    personalBestLabel () {
      if (!this.personalBest) {
        return '';
      }
      const style = store.assessInfo.drillsettings.style;
      if (style === 'time_maxcorrect') {
        return this.$t('drill-best_correct', { n: this.personalBest.correct });
      } else if (style === 'count_correct_attempts' || style === 'streak_attempts') {
        return this.$t('drill-best_attempts', { n: this.personalBest.attempts });
      } else {
        return this.$t('drill-best_time', { n: this.personalBest.time });
      }
    },
    navOptions () {
      const out = [];
      if (this.hasIntro) {
        out.push({
          qn: -1,
          dispname: this.$t('intro'),
          onclick: () => this.selectQuestion(-1)
        });
      }
      for (const qnkey in store.assessInfo.questions) {
        const thisqn = parseInt(qnkey);
        const q = store.assessInfo.questions[thisqn];
        out.push(Object.assign({}, q, {
          qn: thisqn,
          dispname: q.dispname ? q.dispname : this.$t('question_n', { n: thisqn + 1 }),
          onclick: () => this.selectQuestion(thisqn)
        }));
      }
      return out;
    }
  },
  methods: {
    selectQuestion (newqn) {
      if (newqn === this.qn) {
        return;
      }
      if (this.qn > -1 && store.assessInfo.questions.hasOwnProperty(this.qn) &&
        store.assessInfo.questions[this.qn].drillstatus === 1
      ) {
        // stop the active session on the question we're leaving; don't
        // block navigation waiting on it
        actions.stopDrill(this.qn);
      }
      this.$router.push('/drill/' + (newqn + 1));
    },
    handleTimeUp () {
      actions.handleDrillTimeout(this.qn);
    },
    // Timer.vue is purely a visual countdown (matches how the assessment-level
    // timer works: expiration is handled independently, not via a Timer event)
    scheduleTimeout (endMs) {
      window.clearTimeout(this.timeoutHandle);
      if (!this.isTiming || !endMs) {
        return;
      }
      const remaining = endMs - new Date().getTime();
      if (remaining <= 0) {
        this.handleTimeUp();
        return;
      }
      this.timeoutHandle = window.setTimeout(() => { this.handleTimeUp(); }, remaining);
    },
    startCountUp () {
      this.stopCountUp();
      this.elapsedSeconds = 0;
      this.countupStartTime = new Date().getTime();
      this.elapsedTimer = window.setInterval(() => {
        this.elapsedSeconds = Math.round((new Date().getTime() - this.countupStartTime) / 1000);
      }, 1000);
    },
    stopCountUp () {
      window.clearInterval(this.elapsedTimer);
      this.elapsedTimer = null;
    }
  },
  watch: {
    drillTimerEnd (newVal) {
      this.scheduleTimeout(newVal);
    },
    showCountUp (newVal) {
      if (newVal) {
        this.startCountUp();
      } else {
        this.stopCountUp();
      }
    },
    qn () {
      window.clearTimeout(this.timeoutHandle);
      if (this.showCountUp) {
        this.startCountUp();
      } else {
        this.stopCountUp();
      }
    }
  },
  mounted () {
    this.scheduleTimeout(this.drillTimerEnd);
    if (this.showCountUp) {
      this.startCountUp();
    }
  },
  beforeUnmount () {
    window.clearTimeout(this.timeoutHandle);
    this.stopCountUp();
  }
};
</script>

<style>
#drill-question-header {
  display: flex;
  flex-flow: row wrap;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ccc;
  padding: 0;
}
#drill-question-header > * {
  margin: 4px 0;
}
#drill-question-select {
  display: flex;
  flex-flow: row nowrap;
  align-items: center;
}
.drill-personalbest {
  margin-left: 12px;
}
#drill-timer-area {
  display: flex;
  align-items: center;
}
.drill-countup {
  border: 1px solid #ccc;
  padding: 5px;
}
</style>
