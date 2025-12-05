<template>
  <div class="geo-chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { Chart } from 'chart.js';
import { ChoroplethController, GeoFeature, ColorScale, ProjectionScale } from 'chartjs-chart-geo';
import * as topojson from 'topojson-client';

// Register the geo chart components
Chart.register(ChoroplethController, GeoFeature, ColorScale, ProjectionScale);

export default {
  props: {
    data: {
      type: Object,
      required: true
    },
    options: {
      type: Object,
      default: () => ({})
    },
    height: {
      type: Number,
      default: 400
    },
    colorScale: {
      type: String,
      default: 'blues'
    }
  },

  data() {
    return {
      chart: null,
      countries: null,
      worldAtlas: null,
      worldDataLoaded: false,
      isRendering: false
    };
  },

  watch: {
    data: {
      handler() {
        // Only update if world data is loaded and not currently rendering
        if (this.worldDataLoaded && !this.isRendering) {
          this.updateChart();
        }
      },
      deep: true
    }
  },

  async mounted() {
    await this.loadWorldData();
    this.worldDataLoaded = true;
    // Ensure DOM is ready before rendering
    this.$nextTick(() => {
      this.renderChart();
    });
  },

  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy();
    }
  },

  methods: {
    async loadWorldData() {
      // Load world-atlas TopoJSON (50m for better detail)
      const response = await fetch('https://unpkg.com/world-atlas/countries-50m.json');
      this.worldAtlas = await response.json();
      this.countries = topojson.feature(this.worldAtlas, this.worldAtlas.objects.countries).features;
    },

    getColorScale() {
      const scales = {
        blues: ['#f7fbff', '#08306b'],
        greens: ['#f7fcf5', '#00441b'],
        reds: ['#fff5f0', '#67000d'],
        oranges: ['#fff5eb', '#7f2704'],
        purples: ['#fcfbfd', '#3f007d'],
        greys: ['#ffffff', '#000000']
      };
      return scales[this.colorScale] || scales.blues;
    },

    renderChart() {
      console.log('GeoChart renderChart called', {
        hasCountries: !!this.countries,
        hasCanvas: !!this.$refs.chartCanvas,
        data: this.data,
        isRendering: this.isRendering
      });

      // Prevent concurrent renders
      if (this.isRendering) {
        console.log('GeoChart renderChart - already rendering, skipping');
        return;
      }

      if (!this.countries || !this.$refs.chartCanvas) {
        console.log('GeoChart renderChart - missing countries or canvas');
        return;
      }

      this.isRendering = true;

      try {
        const ctx = this.$refs.chartCanvas.getContext('2d');
        if (!ctx) {
          console.log('GeoChart renderChart - could not get canvas context');
          return;
        }

        const geoData = this.data.data || {};
        const dataLabel = this.data.dataLabel || 'Value';
        const [minColor, maxColor] = this.getColorScale();

        // Map country features with values
        const chartFeatures = this.countries.map(country => {
          // Try to match by ISO alpha-2 code
          const countryCode = this.getCountryCode(country);
          // Ensure value is a number (API may return strings)
          const rawValue = geoData[countryCode];
          const value = rawValue ? parseFloat(rawValue) : 0;
          return {
            feature: country,
            value: value
          };
        });

        this.chart = new Chart(ctx, {
          type: 'choropleth',
          data: {
            labels: chartFeatures.map(d => d.feature.properties.name),
            datasets: [{
              label: dataLabel,
              data: chartFeatures
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            showOutline: true,
            showGraticule: true,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                callbacks: {
                  label: (context) => {
                    const value = context.raw?.value || 0;
                    if (value === 0) return 'No data';
                    return `${dataLabel}: ${this.formatValue(value)}`;
                  }
                }
              }
            },
            scales: {
              projection: {
                axis: 'x',
                projection: 'equalEarth'
              },
              color: {
                axis: 'x',
                quantize: 7,
                legend: {
                  position: 'bottom-right',
                  align: 'right'
                },
                interpolate: (v) => {
                  // Use logarithmic scale to handle extreme outliers
                  // This makes lower values much more visible when there are huge differences
                  if (v <= 0) return this.interpolateColor(minColor, maxColor, 0);
                  // Log scale: map 0-1 to visible range (0.15-1.0 to avoid pure white)
                  const logAdjusted = Math.log10(1 + v * 99) / 2; // log10(1) = 0, log10(100) = 2
                  const adjusted = 0.15 + (logAdjusted * 0.85);
                  return this.interpolateColor(minColor, maxColor, Math.min(adjusted, 1));
                }
              }
            }
          }
        });

        console.log('GeoChart renderChart - chart created successfully');
      } catch (error) {
        console.error('GeoChart renderChart error:', error);
      } finally {
        this.isRendering = false;
      }
    },

    updateChart() {
      if (this.chart) {
        this.chart.destroy();
        this.chart = null;
      }
      this.$nextTick(() => {
        this.renderChart();
      });
    },

    getCountryCode(feature) {
      // world-atlas uses numeric ISO codes, we need to map to alpha-2
      // The feature.id is the numeric ISO code
      const numericToAlpha2 = this.getNumericToAlpha2Map();
      return numericToAlpha2[feature.id] || null;
    },

    getNumericToAlpha2Map() {
      // ISO 3166-1 numeric to alpha-2 mapping
      return {
        '004': 'AF', '008': 'AL', '012': 'DZ', '020': 'AD', '024': 'AO', '028': 'AG', '032': 'AR',
        '051': 'AM', '036': 'AU', '040': 'AT', '031': 'AZ', '044': 'BS', '048': 'BH', '050': 'BD',
        '052': 'BB', '112': 'BY', '056': 'BE', '084': 'BZ', '204': 'BJ', '064': 'BT', '068': 'BO',
        '070': 'BA', '072': 'BW', '076': 'BR', '096': 'BN', '100': 'BG', '854': 'BF', '108': 'BI',
        '132': 'CV', '116': 'KH', '120': 'CM', '124': 'CA', '140': 'CF', '148': 'TD', '152': 'CL',
        '156': 'CN', '170': 'CO', '174': 'KM', '178': 'CG', '180': 'CD', '188': 'CR', '384': 'CI',
        '191': 'HR', '192': 'CU', '196': 'CY', '203': 'CZ', '208': 'DK', '262': 'DJ', '212': 'DM',
        '214': 'DO', '218': 'EC', '818': 'EG', '222': 'SV', '226': 'GQ', '232': 'ER', '233': 'EE',
        '748': 'SZ', '231': 'ET', '242': 'FJ', '246': 'FI', '250': 'FR', '266': 'GA', '270': 'GM',
        '268': 'GE', '276': 'DE', '288': 'GH', '300': 'GR', '308': 'GD', '320': 'GT', '324': 'GN',
        '624': 'GW', '328': 'GY', '332': 'HT', '340': 'HN', '348': 'HU', '352': 'IS', '356': 'IN',
        '360': 'ID', '364': 'IR', '368': 'IQ', '372': 'IE', '376': 'IL', '380': 'IT', '388': 'JM',
        '392': 'JP', '400': 'JO', '398': 'KZ', '404': 'KE', '296': 'KI', '408': 'KP', '410': 'KR',
        '414': 'KW', '417': 'KG', '418': 'LA', '428': 'LV', '422': 'LB', '426': 'LS', '430': 'LR',
        '434': 'LY', '438': 'LI', '440': 'LT', '442': 'LU', '450': 'MG', '454': 'MW', '458': 'MY',
        '462': 'MV', '466': 'ML', '470': 'MT', '584': 'MH', '478': 'MR', '480': 'MU', '484': 'MX',
        '583': 'FM', '498': 'MD', '492': 'MC', '496': 'MN', '499': 'ME', '504': 'MA', '508': 'MZ',
        '104': 'MM', '516': 'NA', '520': 'NR', '524': 'NP', '528': 'NL', '554': 'NZ', '558': 'NI',
        '562': 'NE', '566': 'NG', '807': 'MK', '578': 'NO', '512': 'OM', '586': 'PK', '585': 'PW',
        '591': 'PA', '598': 'PG', '600': 'PY', '604': 'PE', '608': 'PH', '616': 'PL', '620': 'PT',
        '634': 'QA', '642': 'RO', '643': 'RU', '646': 'RW', '659': 'KN', '662': 'LC', '670': 'VC',
        '882': 'WS', '674': 'SM', '678': 'ST', '682': 'SA', '686': 'SN', '688': 'RS', '690': 'SC',
        '694': 'SL', '702': 'SG', '703': 'SK', '705': 'SI', '090': 'SB', '706': 'SO', '710': 'ZA',
        '728': 'SS', '724': 'ES', '144': 'LK', '729': 'SD', '740': 'SR', '752': 'SE', '756': 'CH',
        '760': 'SY', '762': 'TJ', '834': 'TZ', '764': 'TH', '626': 'TL', '768': 'TG', '776': 'TO',
        '780': 'TT', '788': 'TN', '792': 'TR', '795': 'TM', '798': 'TV', '800': 'UG', '804': 'UA',
        '784': 'AE', '826': 'GB', '840': 'US', '858': 'UY', '860': 'UZ', '548': 'VU', '862': 'VE',
        '704': 'VN', '887': 'YE', '894': 'ZM', '716': 'ZW', '275': 'PS', '158': 'TW', '344': 'HK',
        '446': 'MO'
      };
    },

    interpolateColor(color1, color2, factor) {
      const hex = (c) => parseInt(c.slice(1), 16);
      const r1 = (hex(color1) >> 16) & 255;
      const g1 = (hex(color1) >> 8) & 255;
      const b1 = hex(color1) & 255;
      const r2 = (hex(color2) >> 16) & 255;
      const g2 = (hex(color2) >> 8) & 255;
      const b2 = hex(color2) & 255;

      const r = Math.round(r1 + factor * (r2 - r1));
      const g = Math.round(g1 + factor * (g2 - g1));
      const b = Math.round(b1 + factor * (b2 - b1));

      return `rgb(${r}, ${g}, ${b})`;
    },

    formatValue(value) {
      if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
      }
      if (value >= 1000) {
        return (value / 1000).toFixed(1) + 'K';
      }
      return value.toLocaleString();
    }
  }
};
</script>

<style scoped>
.geo-chart-container {
  width: 100%;
  height: 100%;
  position: relative;
}
.geo-chart-container canvas {
  width: 100% !important;
  height: 100% !important;
}
</style>