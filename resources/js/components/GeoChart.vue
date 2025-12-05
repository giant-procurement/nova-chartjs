<template>
  <LoadingCard :loading="loading" class="px-6 py-4">
    <div class="h-6 mb-4 flex items-center">
      <h4 class="mr-3 leading-tight text-sm font-bold">{{ checkTitle }}</h4>
      <div class="flex relative ml-auto flex-shrink-0 gap-2">
        <button
          v-show="buttonRefresh"
          @click="fillData()"
          class="px-2 py-1 text-xs rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition"
        >
          <Icon name="arrow-path" type="outline" class="w-4 h-4" />
        </button>
        <SelectControl
          v-if="showAdvanceFilter"
          v-model="advanceFilterSelected"
          @selected="handleFilterChanged"
          :options="advanceFilter"
        />
      </div>
    </div>
    <div :style="{ height: chartHeight + 'px', position: 'relative' }">
      <geo-chart-canvas
        v-if="hasData"
        :data="chartData"
        :options="chartOptions"
        :height="chartHeight"
        :color-scale="colorScale"
      />
      <div v-else class="flex items-center justify-center h-full">
        <span class="text-gray-400">Loading map data...</span>
      </div>
    </div>
  </LoadingCard>
</template>

<script>
import GeoChartCanvas from '../geo-chart.vue';
import Icon from '@ui/components/Icon.vue';
import GlobalFilterBehavior from '../mixins/GlobalFilterBehavior';

export default {
  mixins: [GlobalFilterBehavior],
  components: {
    Icon,
    GeoChartCanvas
  },

  props: ['card'],

  data() {
    this.card.options = this.card.options || {};

    // Use card.filterOptions or fall back to old btnFilterList
    let filterOptions = [];
    let hasFilterOptions = false;
    let selectedValue = null;

    if (this.card.filterOptions && this.card.filterOptions.length > 0) {
      filterOptions = this.card.filterOptions;
      hasFilterOptions = true;
      selectedValue = this.card.selectedFilterKey || this.card.filterOptions[0]?.value;
    } else if (this.card.options.btnFilterList) {
      const btnFilterList = this.card.options.btnFilterList;
      let i = 0;
      for (let index in btnFilterList) {
        filterOptions[i] = { value: index, label: btnFilterList[index] };
        i++;
      }
      selectedValue = this.card.options.btnFilterDefault || filterOptions[0]?.value;
    }

    return {
      chartData: {},
      chartOptions: {},
      loading: false,
      buttonRefresh: this.card.options.btnRefresh || true,
      chartHeight: this.card.options.chartHeight || 400,
      colorScale: this.card.colorScale || 'blues',
      showAdvanceFilter: this.card.model ? (hasFilterOptions || this.card.options.btnFilter === true) : false,
      advanceFilterSelected: selectedValue,
      advanceFilter: filterOptions
    };
  },

  computed: {
    checkTitle() {
      return this.card.title || 'Geo Chart';
    },
    hasData() {
      return this.chartData && this.chartData.data && Object.keys(this.chartData.data).length > 0;
    }
  },

  mounted() {
    this.fillData();
  },

  methods: {
    handleFilterChanged(value) {
      this.fillData();
    },

    fillData() {
      // Custom data mode (no model)
      if (!this.card.model) {
        this.chartData = {
          data: this.card.data || {},
          dataLabel: this.card.dataLabel || 'Value'
        };
        return;
      }

      // Set advanceFilterSelected if we have a filter dropdown
      if (this.showAdvanceFilter) {
        this.card.options.advanceFilterSelected = this.advanceFilterSelected;
      }

      // Use Model - fetch data from API
      this.loading = true;
      const params = {
        model: this.card.model,
        options: this.mergeGlobalFilters(this.card.options),
        join: this.card.join,
        expires: 0
      };

      const filtersParam = this.getGlobalFiltersParam();
      if (filtersParam) {
        params.filters = filtersParam;
      }

      Nova.request()
        .get('/nova-vendor/coroowicaksono/check-data/geo-endpoint', { params })
        .then(({ data }) => {
          console.log('GeoChart API response:', data);
          this.chartData = {
            data: data.data || {},
            dataLabel: this.card.dataLabel || 'Revenue'
          };
          console.log('GeoChart chartData set:', this.chartData);
          this.loading = false;
        })
        .catch((error) => {
          console.error('GeoChart error:', error);
          console.error('Response:', error?.response?.data);
          this.loading = false;
        });
    }
  }
};
</script>