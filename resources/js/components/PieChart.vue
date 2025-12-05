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
        <button
          v-show="buttonReload"
          @click="reloadPage()"
          class="px-2 py-1 text-xs rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition"
        >
          <Icon name="arrow-path" type="outline" class="w-4 h-4" />
        </button>
        <a
          v-show="btnExtLink"
          :href="externalLink"
          :target="externalLinkIn"
          class="px-2 py-1 text-xs rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition inline-flex items-center"
        >
          <Icon name="arrow-top-right-on-square" type="outline" class="w-4 h-4" />
        </a>
        <SelectControl
          v-if="showAdvanceFilter"
          v-model="advanceFilterSelected"
          @selected="handleFilterChanged"
          :options="advanceFilter"
        />
      </div>
    </div>
    <div :style="{ height: chartHeight + 'px', position: 'relative' }">
      <line-chart :data="datacollection" :options="options" :height="chartHeight"></line-chart>
    </div>
  </LoadingCard>
</template>

<script>
  import LineChart from '../pie-chart.vue';
  import Icon from '@ui/components/Icon.vue';
  import GlobalFilterBehavior from '../mixins/GlobalFilterBehavior';

  export default {
    mixins: [GlobalFilterBehavior],
    components: {
      Icon,
      LineChart
    },
    data () {
      this.card.options = this.card.options != undefined ? this.card.options : false;

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
        for ( let index in btnFilterList ) {
          filterOptions[i] = {value: index, label: btnFilterList[index]};
          i++;
        }
        selectedValue = this.card.options.btnFilterDefault || filterOptions[0]?.value;
      }

      return {
        datacollection: { labels: [], datasets: [] },
        options: {},
        loading: false,
        buttonRefresh: this.card.options.btnRefresh,
        buttonReload: this.card.options.btnReload,
        btnExtLink: this.card.options.extLink != undefined ? true : false,
        externalLink: this.card.options.extLink,
        externalLinkIn: this.card.options.extLinkIn != undefined ? this.card.options.extLinkIn : '_self',
        chartTooltips: this.card.options.tooltips != undefined ? this.card.options.tooltips : undefined,
        sweetAlert: this.card.options.sweetAlert2 != undefined ? this.card.options.sweetAlert2 : undefined,
        chartPlugins: this.card.options.plugins != undefined ? this.card.options.plugins : false,
        chartHeight: this.card.options.chartHeight != undefined ? this.card.options.chartHeight : 250,
        chartLayout: this.card.options.layout != undefined ? this.card.options.layout :
          {
            padding: {
              left: 20,
              right: 20,
              top: 0,
              bottom: 10
            }
          },
        chartLegend: this.card.options.legend != undefined ? this.card.options.legend :
          {
            display: true,
            position: 'right',
            labels: {
                fontColor: '#7c858e',
                fontFamily: "'Nunito'"
            }
          },
        showAdvanceFilter: this.card.model == 'custom' || this.card.model == undefined ? false : (hasFilterOptions || this.card.options.btnFilter == true),
        advanceFilterSelected: selectedValue,
        advanceFilter: filterOptions,
      }
    },
    computed: {
      checkTitle() {
        return this.card.title !== undefined ? this.card.title : 'Chart JS Integration';
      }
    },
    props: [
        'card'
    ],
    mounted () {
      this.fillData();
    },
    methods: {
      reloadPage(){
        window.location.reload()
      },

      handleFilterChanged(value) {
        this.fillData();
      },
      fillData () {
        this.options = {
          ...this.card.options,
          layout: this.chartLayout,
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: this.chartLegend,
            ...this.chartPlugins,
          },
        };

        if(this.chartTooltips !== undefined){
          this.options.plugins.tooltip = this.chartTooltips;
          const tooltiplist = ["custom", "itemSort", "filter"];
          for (let z = 0; z < tooltiplist.length; z++) {
            if(this.options.plugins.tooltip[tooltiplist[z]] != undefined){
              if(this.options.plugins.tooltip[tooltiplist[z]].search("function") != -1){
                eval("this.options.plugins.tooltip." + tooltiplist[z] + " = " + this.options.plugins.tooltip[tooltiplist[z]]);
              }
            }
          }

          if(this.chartTooltips.callbacks !== undefined){
            const callbacklist = ["beforeTitle", "title", "afterTitle", "beforeBody", "beforeLabel", "label", "labelColor", "labelTextColor", "afterLabel", "afterBody", "beforeFooter", "footer", "afterFooter"];
            for (let i = 0; i < callbacklist.length; i++) {
              if(this.options.plugins?.tooltip?.callbacks?.[callbacklist[i]] != undefined){
                if(this.options.plugins.tooltip.callbacks[callbacklist[i]].search("function") != -1){
                  eval("this.options.plugins.tooltip.callbacks." + callbacklist[i] + " = " + this.options.plugins.tooltip.callbacks[callbacklist[i]]);
                }
              }
            }
          }
        }

        if(this.card.model == 'custom' || this.card.model == undefined){
        // Custom Data
          this.title = this.card.title;
          this.datacollection = {
            labels: this.card.options.xaxis.categories,
            datasets: this.card.series,
          }

          // START == SETUP POPUP
          const sweetAlertWithLink = this.sweetAlert;
          if(sweetAlertWithLink != undefined) {
            this.options.onClick = function (event, element) {
              if (element.length > 0) {
                let series = element[0].datasetLabel;
                let label = element[0].label;
                let value = this.data.datasets[element[0].datasetIndex].data[element[0].index];

                const toLink = sweetAlertWithLink.linkTo != undefined ? sweetAlertWithLink.linkTo : "https://coroo.github.io/nova-chartjs/";
                const { linkTo, ...sweetAlert } = sweetAlertWithLink;

                // sum data
                let dataArr = this.data.datasets[0].data;
                let sum = 0;
                sum = dataArr.reduce((a, b) => parseInt(a) + parseInt(b), 0);
                let percentage = (value / sum) * 100 ;

                const Swal = require('sweetalert2')
                Swal.fire({
                  title: sweetAlert.title != undefined ? sweetAlert.title : '<strong>'+label+'</strong>',
                  icon: sweetAlert.icon != undefined ? sweetAlert.icon : 'info',
                  html: sweetAlert.html != undefined ? sweetAlert.html : 'Percentage: <b>' + percentage.toFixed(2) + '%</b><br/><b>'+value+'</b> data from <b>'+sum+'</b><br/> ',
                  showCloseButton: sweetAlert.showCloseButton != undefined ? sweetAlert.showCloseButton : true,
                  showCancelButton: sweetAlert.showCancelButton != undefined ? sweetAlert.showCancelButton : true,
                  focusConfirm: sweetAlert.focusConfirm != undefined ? sweetAlert.focusConfirm : false,
                  confirmButtonText: sweetAlert.confirmButtonText != undefined ? sweetAlert.confirmButtonText : '<i class="fas fa-external-link-alt"></i> See Detail',
                  confirmButtonAriaLabel: sweetAlert.confirmButtonAriaLabel != undefined ? sweetAlert.confirmButtonAriaLabel : 'See Detail',
                  cancelButtonAriaLabel: sweetAlert.cancelButtonAriaLabel != undefined ? sweetAlert.cancelButtonAriaLabel : 'Cancel',
                  footer: sweetAlert.footer != undefined ? sweetAlert.footer : '<a href="https://coroo.github.io/nova-chartjs/" target="_blank" style="text-decoration:none; color:#777; font-size:14px">Nova Chart JS © ' + new Date().getFullYear() + '</a>',
                  ...sweetAlert
                }).then((result) => {
                  if (result.value) {
                    window.location = toLink;
                  }
                })
              }
            }
          }
          // END == SETUP POPUP

          if( this.card.options.showPercentage != undefined ) {
            if( this.card.options.showPercentage == true ) {
              let dataArr = this.card.series[0].data;
              let sum = dataArr.reduce((a, b) => parseInt(a) + parseInt(b), 0);
              this.options.plugins.tooltip = {
                callbacks: {
                  ...this.options.plugins?.tooltip?.callbacks || {},
                  label: function (context) {
                    return context.label + ': ' + '' + context.raw + ' (' + (context.raw * 100 / sum).toFixed(2) + '%)';
                  }
                }
              };
            }
          }
        } else {
          // Set advanceFilterSelected if we have a filter dropdown
          if(this.showAdvanceFilter) {
            this.card.options.advanceFilterSelected = this.advanceFilterSelected;
          }

          // Use Model
          this.loading = true;
          const params = {
            model: this.card.model,
            series: this.card.series,
            options: this.mergeGlobalFilters(this.card.options),
            join: this.card.join,
            col_xaxis: this.card.col_xaxis,
            expires: 0,
          };

          const filtersParam = this.getGlobalFiltersParam();
          if (filtersParam) {
            params.filters = filtersParam;
          }

          Nova.request().get("/nova-vendor/coroowicaksono/check-data/circle-endpoint", {
            params: params,
          })
          .then(({ data }) => {
            this.datacollection = {
              labels: data.dataset.xAxis,
              datasets: data.dataset.yAxis,
            };

            this.loading = false;

            // START == SETUP POPUP
            const sweetAlertWithLink = this.sweetAlert;
            if(sweetAlertWithLink != undefined) {
              this.options.onClick = function (event, element) {
                if (element.length > 0) {
                  let series = element[0].datasetLabel;
                  let label = element[0].label;
                  let value = this.data.datasets[element[0].datasetIndex].data[element[0].index];

                  const toLink = sweetAlertWithLink.linkTo != undefined ? sweetAlertWithLink.linkTo : "https://coroo.github.io/nova-chartjs/";
                  const { linkTo, ...sweetAlert } = sweetAlertWithLink;

                  // sum data
                  let dataArr = this.data.datasets[0].data;
                  let sum = dataArr.reduce((a, b) => parseInt(a) + parseInt(b), 0);
                  let percentage = (value / sum) * 100;

                  const Swal = require('sweetalert2')
                  Swal.fire({
                    title: sweetAlert.title != undefined ? sweetAlert.title : '<strong>' + label + '</strong>',
                    icon: sweetAlert.icon != undefined ? sweetAlert.icon : 'info',
                    html: sweetAlert.html != undefined ? sweetAlert.html : 'Percentage: <b>' + percentage.toFixed(2) + '%</b><br/><b>' + value + '</b> data from <b>' + sum + '</b><br/> ',
                    showCloseButton: sweetAlert.showCloseButton != undefined ? sweetAlert.showCloseButton : true,
                    showCancelButton: sweetAlert.showCancelButton != undefined ? sweetAlert.showCancelButton : true,
                    focusConfirm: sweetAlert.focusConfirm != undefined ? sweetAlert.focusConfirm : false,
                    confirmButtonText: sweetAlert.confirmButtonText != undefined ? sweetAlert.confirmButtonText : '<i class="fas fa-external-link-alt"></i> See Detail',
                    confirmButtonAriaLabel: sweetAlert.confirmButtonAriaLabel != undefined ? sweetAlert.confirmButtonAriaLabel : 'See Detail',
                    cancelButtonAriaLabel: sweetAlert.cancelButtonAriaLabel != undefined ? sweetAlert.cancelButtonAriaLabel : 'Cancel',
                    footer: sweetAlert.footer != undefined ? sweetAlert.footer : '<a href="https://coroo.github.io/nova-chartjs/" target="_blank" style="text-decoration:none; color:#777; font-size:14px">Nova Chart JS © ' + new Date().getFullYear() + '</a>',
                    ...sweetAlert
                  }).then((result) => {
                    if (result.value) {
                      window.location = toLink;
                    }
                  })
                }
              }
            }
            // END == SETUP POPUP

            if( this.card.options.showPercentage != undefined ) {
              if( this.card.options.showPercentage == true ) {
                let dataArr = data.dataset.yAxis[0].data;
                let sum = dataArr.reduce((a, b) => parseInt(a) + parseInt(b), 0);
                this.options.plugins.tooltip = {
                  callbacks: {
                    ...this.options.plugins?.tooltip?.callbacks || {},
                    label: function (context) {
                      return context.label + ': ' + '' + context.raw + ' (' + (context.raw * 100 / sum).toFixed(2) + '%)';
                    }
                  }
                };
              }
            }
          })
          .catch(({ response }) => {
            this.errors = response.data.errors;
            this.loading = false;
          })
        }
      },
    },
  }
</script>
