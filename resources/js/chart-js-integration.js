import ChartDataLabels from 'chartjs-plugin-datalabels';
import zoomPlugin from 'chartjs-plugin-zoom';
import { Chart, registerables } from 'chart.js';

import StackedChart from './components/StackedChart'
import BarChart from './components/BarChart'
import LineChart from './components/StripeChart'
import DoughnutChart from './components/DoughnutChart'
import PieChart from './components/PieChart'
import PolarAreaChart from './components/PolarAreaChart'
import ScatterChart from './components/ScatterChart'
import GeoChart from './components/GeoChart'

Nova.booting((Vue) => {
    const textColor = getComputedStyle(document.documentElement)
        .getPropertyValue('--colors-gray-400');

    Chart.unregister(ChartDataLabels);
    Chart.register(...registerables);
    Chart.register(zoomPlugin);
    Chart.defaults.color = `rgba(${textColor}, 1)`;

    Vue.component('stacked-chart', StackedChart);
    Vue.component('bar-chart', BarChart);
    Vue.component('stripe-chart', LineChart);
    Vue.component('doughnut-chart', DoughnutChart);
    Vue.component('pie-chart', PieChart);
    Vue.component('polar-area-chart', PolarAreaChart);
    Vue.component('scatter-chart', ScatterChart);
    Vue.component('geo-chart', GeoChart);
})

