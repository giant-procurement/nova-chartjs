export default {
    data: () => ({
        globalFilters: {}
    }),

    created() {
        // Listen for global filter changes
        Nova.$on("global-filter-changed", filter => {
            this.globalFilters[filter.class] = filter.currentValue;

            if (filter.currentValue === '' || JSON.stringify(filter.currentValue) === JSON.stringify({})) {
                delete this.globalFilters[filter.class];
            }

            // Re-fetch chart data when global filter changes
            this.fillData();
        });

        // Listen for global filter resets
        Nova.$on("global-filter-reset", (filters) => {
            filters.forEach(filter => {
                delete this.globalFilters[filter.class];
            });

            // Re-fetch chart data when filters reset
            this.fillData();
        });
    },

    methods: {
        // Get the global filters as a JSON string (matching GlobalFilterable format)
        getGlobalFiltersParam() {
            if (Object.keys(this.globalFilters).length === 0) {
                return null;
            }
            return JSON.stringify(this.globalFilters);
        },

        // Helper to merge global filters into chart options
        mergeGlobalFilters(options) {
            // If global filters are active, get all historical data
            if (Object.keys(this.globalFilters).length > 0) {
                return {
                    ...options,
                    latestData: '*'
                };
            }

            return options;
        }
    }
}