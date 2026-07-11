// Shared human-readable descriptions of drill settings/results, used in
// SettingsList.vue (before starting), Drill.vue, and GBViewAssess.vue
// (teacher view).
export const drillGoalMixin = {
  methods: {
    // Description of the goal, built from drillsettings ({style, n}) -
    // i.e. the *current* settings for the assessment.
    drillGoalLabel (drillsettings) {
      if (!drillsettings) {
        return '';
      }
      const n = drillsettings.n;
      switch (drillsettings.style) {
        case 'time_maxcorrect':
          return this.$t('drill-goal_time_maxcorrect', { n: n });
        case 'count_time':
          return this.$t('drill-goal_count_time', { n: n });
        case 'count_correct_time':
          return this.$t('drill-goal_count_correct_time', { n: n });
        case 'count_correct_attempts':
          return this.$t('drill-goal_count_correct_attempts', { n: n });
        case 'streak_time':
          return this.$t('drill-goal_streak_time', { n: n });
        case 'streak_attempts':
          return this.$t('drill-goal_streak_attempts', { n: n });
        default:
          return '';
      }
    },
    // In-progress status towards the goal, built from drillsettings
    // ({style, n}) and the question's current drillcount.
    drillProgressLabel (drillsettings, count) {
      if (!drillsettings) {
        return '';
      }
      const n = drillsettings.n;
      switch (drillsettings.style) {
        case 'time_maxcorrect':
          return this.$t('drill-progress_time_maxcorrect', { count: count });
        case 'count_time':
          return this.$t('drill-progress_count_time', { count: count, n: n });
        case 'count_correct_time':
        case 'count_correct_attempts':
          return this.$t('drill-progress_count_correct', { count: count, n: n });
        case 'streak_time':
        case 'streak_attempts':
          return this.$t('drill-progress_streak', { count: count, n: n });
        default:
          return '';
      }
    },
    // Description of a single completed run, built from a drillresults
    // entry ({style, n, time, attempts, correct, completed}) - each entry
    // records the settings in effect *at the time*, so this stays accurate
    // even after the assessment's current drill settings later change.
    drillResultLabel (r) {
      if (!r) {
        return '';
      }
      switch (r.style) {
        case 'time_maxcorrect':
          return this.$t('drill-result_time_maxcorrect', { correct: r.correct, n: r.n });
        case 'count_time':
          return this.$t('drill-result_count_time', { n: r.n, time: r.time });
        case 'count_correct_time':
          return this.$t('drill-result_count_correct_time', { n: r.n, time: r.time });
        case 'count_correct_attempts':
          return this.$t('drill-result_count_correct_attempts', { n: r.n, attempts: r.attempts });
        case 'streak_time':
          return this.$t('drill-result_streak_time', { n: r.n, time: r.time });
        case 'streak_attempts':
          return this.$t('drill-result_streak_attempts', { n: r.n, attempts: r.attempts });
        default:
          return '';
      }
    }
  }
};
