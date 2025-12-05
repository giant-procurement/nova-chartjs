<template>
  <Bar
      :data="chartData"
      :options="options"
      :height="height"
      :plugins="plugins"
  />
</template>

<script>
import { Bar } from 'vue-chartjs'
import HandlesDataLabelsPlugin from './mixins/HandlesDataLabelsPlugin';

export default {
  components: {Bar},
  props: {
    height: {
      default: 120,
      type: Number
    }
  },
  mixins: [HandlesDataLabelsPlugin],
}
</script>
