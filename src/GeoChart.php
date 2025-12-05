<?php

namespace Coroowicaksono\ChartJsIntegration;

use Laravel\Nova\Card;

class GeoChart extends Card
{
    /**
     * The width of the card (1/3, 1/2, or full).
     *
     * @var string
     */
    public $width = 'full';

    protected $filterOptions = [];

    protected $selectedFilterKey = null;

    public function __construct($component = null)
    {
        parent::__construct($component);
    }

    /**
     * Get the component name for the element.
     *
     * @return string
     */
    public function component()
    {
        return 'geo-chart';
    }

    /**
     * Set the data for the geo chart.
     * Expected format: ['country_code' => value, ...]
     * e.g., ['US' => 1000, 'GB' => 500, 'DE' => 750]
     */
    public function data(array $data): self
    {
        return $this->withMeta(['data' => $data]);
    }

    /**
     * Set the color scale for the choropleth.
     * Options: 'blues', 'greens', 'reds', 'oranges', 'purples', 'greys'
     */
    public function colorScale(string $colorScale): self
    {
        return $this->withMeta(['colorScale' => $colorScale]);
    }

    /**
     * Set the label for the data.
     */
    public function dataLabel(string $label): self
    {
        return $this->withMeta(['dataLabel' => $label]);
    }

    public function options(array $options): self
    {
        return $this->withMeta(['options' => (object) $options]);
    }

    public function title(string $title): self
    {
        return $this->withMeta(['title' => $title]);
    }

    /**
     * Set the API endpoint to fetch data from.
     */
    public function apiEndpoint(string $endpoint): self
    {
        return $this->withMeta(['apiEndpoint' => $endpoint]);
    }

    /**
     * Set the value field key in the API response.
     * e.g., 'revenue' if your data is [{country: 'US', revenue: 1000}, ...]
     */
    public function valueField(string $field): self
    {
        return $this->withMeta(['valueField' => $field]);
    }

    /**
     * Set the country field key in the API response.
     * e.g., 'country_code' if your data is [{country_code: 'US', value: 1000}, ...]
     */
    public function countryField(string $field): self
    {
        return $this->withMeta(['countryField' => $field]);
    }

    public function uriKey(string $uriKey)
    {
        return $this->withMeta(['uriKey' => $uriKey]);
    }

    public function model(string $model): self
    {
        return $this->withMeta(['model' => $model]);
    }

    public function join(string $joinTable, string $joinColumnFirst, string $joinEqual, string $joinColumnSecond): self
    {
        return $this->withMeta(['join' => [
            'joinTable' => $joinTable,
            'joinColumnFirst' => $joinColumnFirst,
            'joinEqual' => $joinEqual,
            'joinColumnSecond' => $joinColumnSecond,
        ]]);
    }

    public function filterOptions(array $filterOptions): self
    {
        $this->filterOptions = $filterOptions;

        return $this;
    }

    public function defaultFilter(string $key): self
    {
        $this->selectedFilterKey = $key;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'selectedFilterKey' => $this->selectedFilterKey,
            'filterOptions' => collect($this->filterOptions)
                ->map(static fn ($filterOption, $key) => ['label' => $filterOption, 'value' => $key])
                ->values()
                ->all(),
        ]);
    }
}