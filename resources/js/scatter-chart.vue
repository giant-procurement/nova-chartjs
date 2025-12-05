<template>
    <Scatter
        :data="chartData"
        :options="options"
        :height="height"
    />
</template>

<script>
import { Scatter } from 'vue-chartjs'
import HandlesDataLabelsPlugin from './mixins/HandlesDataLabelsPlugin';

export default {
  components: { Scatter },
  props: {
    height: {
      default: 120,
      type: Number
    }
  },
  mixins: [HandlesDataLabelsPlugin],
}
</script>
