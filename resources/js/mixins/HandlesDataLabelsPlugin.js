import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Chart } from 'chart.js';

export default {
    props: {
        data: {
            type: Object,
            required: true
        },
        options:{
            type: Object,
            required: true
        },
    },
    computed: {
        chartData() {
            return this.data;
        }
    },
    data() {
        return {
            plugins: [],
        }
    },
    mounted() {
        // Only add plugin once on mount, not on every data change
        if(this.options.plugins !== undefined){
            if(this.options.plugins.datalabels !== undefined){
                if(this.options.plugins.datalabels !== false){
                    if (!this.plugins.includes(ChartDataLabels)) {
                        this.plugins.push(ChartDataLabels);
                    }
                }
            }
        }
    }
}
