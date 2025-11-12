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
    <line-chart :chart-data="datacollection" :options="options" :height="chartHeight"></line-chart>
  </LoadingCard>
</template>

<script>
  import LineChart from '../bar-chart';
  import Icon from '@ui/components/Icon.vue';

  export default {
    components: {
      Icon,
      LineChart
    },
    data () {
      this.card.options = this.card.options != undefined ? this.card.options : false;

      // setup btn filter list
      const btnFilterList = this.card.options.btnFilterList;
      let filledAdvancedList = [];
      let i = 0;

      for ( let index in btnFilterList ) {
        filledAdvancedList[i] = {value: index, label: btnFilterList[index]};
        i++;
      }

      return {
        datacollection: {},
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
            position: 'left',
            labels: {
              fontColor: '#7c858e',
              fontFamily: "'Nunito'"
            }
          },
        showAdvanceFilter: this.card.model == 'custom' || this.card.model == undefined ? false : this.card.options.btnFilter == true ? true : false ,
        advanceFilterSelected: this.card.options.btnFilterDefault != undefined ? this.card.options.btnFilterDefault : 'QTD',
        advanceFilter: this.card.options.btnFilterList != undefined ? filledAdvancedList : [
          { label: 'Year to Date', value: 'YTD' },
          { label: 'Quarter to Date', value: 'QTD' },
          { label: 'Month to Date', value: 'MTD' },
          { label: '30 Days', value: 30 },
          { label: '60 Days', value: 60 },
          { label: '365 Days', value: 365 },
        ],
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
          scales: {
            yAxes: {
              stacked: true,
              ...this.card.options.scales?.yAxes || {},
              ticks: {
                maxTicksLimit: 5,
                ...this.card.options.scales?.yAxes?.ticks || {},
                font: {
                  size: 10,
                  ...this.card.options.scales?.yAxes?.ticks?.font || {},
                },
                callback: function(num, index, values) {
                  if (num >= 1000000000) {
                    return (num / 1000000000).toFixed(1).replace(/\.0$/, '') + 'G';
                  }
                  if (num >= 1000000) {
                    return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
                  }
                  if (num >= 1000) {
                    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
                  }
                  return num;
                }
              }
            },
            xAxes: {
              stacked: true,
              ...this.card.options.scales?.xAxes || {},
              ticks: {
                ...this.card.options.scales?.xAxes?.ticks || {},
                font: {
                  lineHeight: 0.8,
                  size: 10,
                  ...this.card.options.scales?.xAxes?.ticks?.font || {},
                }
              }
            }
          },
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

                const Swal = require('sweetalert2')
                Swal.fire({
                  title: sweetAlert.title != undefined ? sweetAlert.title : '<strong>'+value+'</strong>',
                  icon: sweetAlert.icon != undefined ? sweetAlert.icon : 'info',
                  html: sweetAlert.html != undefined ? sweetAlert.html : series == undefined ? 'You can see detail by click below button:' : '<b>' + series + '</b> in '+label+'<br/> ',
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
        } else {
          if(this.showAdvanceFilter == true) this.card.options.advanceFilterSelected = this.advanceFilterSelected != undefined ? this.advanceFilterSelected : false;

          // Use Model
          this.loading = true;
          Nova.request().get("/nova-vendor/coroowicaksono/check-data/endpoint", {
            params: {
              model: this.card.model,
              series: this.card.series,
              options: this.card.options,
              join: this.card.join,
              col_xaxis: this.card.col_xaxis,
              expires: 0,
            },
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

                  const Swal = require('sweetalert2')
                  Swal.fire({
                    title: sweetAlert.title != undefined ? sweetAlert.title : '<strong>'+value+'</strong>',
                    icon: sweetAlert.icon != undefined ? sweetAlert.icon : 'info',
                    html: sweetAlert.html != undefined ? sweetAlert.html : series == undefined ? 'You can see detail by click below button:' : '<b>' + series + '</b> in '+label+'<br/> ',
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
          })
          .catch(({ response }) => {
            this.loading = false;
            this.errors = response.data.errors;
          })
        }
      },
    },
  }
</script>
