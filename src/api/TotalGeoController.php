<?php

namespace Coroowicaksono\ChartJsIntegration\Api;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Laravel\Nova\Http\Requests\NovaRequest;

class TotalGeoController extends Controller
{
    use ValidatesRequests;

    /**
     * Handle geo chart data request.
     * Returns data grouped by country_code for choropleth map visualization.
     */
    public function handle(NovaRequest $request)
    {
        if ($request->input('model')) {
            $request->merge(['model' => urldecode($request->input('model'))]);
        }

        $options = is_string($request->options) ? json_decode($request->options, true) : $request->input('options', []);
        $join = is_string($request->join) ? json_decode($request->join, true) : $request->input('join', []);

        $advanceFilterSelected = $options['advanceFilterSelected'] ?? false;
        $calculation = $options['sum'] ?? 1;
        $countryColumn = $options['countryColumn'] ?? null;
        $dateColumn = $options['dateColumn'] ?? 'created_at';

        if (! $countryColumn) {
            return response()->json(['error' => 'countryColumn option is required'], 400);
        }

        $request->validate(['model' => ['bail', 'required', 'min:1', 'string']]);

        $model = $request->input('model');

        $query = $model::selectRaw($countryColumn . ' AS country_code, SUM(' . $calculation . ') AS value');

        // Apply joins
        if (count($join)) {
            $joinInformation = $join;
            $query->join(
                $joinInformation['joinTable'],
                $joinInformation['joinColumnFirst'],
                $joinInformation['joinEqual'],
                $joinInformation['joinColumnSecond']
            );
        }

        // Apply date range filters (same logic as TotalRecordsController)
        if (is_numeric($advanceFilterSelected)) {
            $query->where($dateColumn, '>=', Carbon::now()->subDays($advanceFilterSelected));
        } elseif ($advanceFilterSelected === 'YTD') {
            $query->whereBetween($dateColumn, [Carbon::now()->firstOfYear()->startOfDay(), Carbon::now()]);
        } elseif ($advanceFilterSelected === 'QTD') {
            $query->whereBetween($dateColumn, [Carbon::now()->firstOfQuarter()->startOfDay(), Carbon::now()]);
        } elseif ($advanceFilterSelected === 'MTD') {
            $query->whereBetween($dateColumn, [Carbon::now()->firstOfMonth()->startOfDay(), Carbon::now()]);
        }

        // Apply global filters from nova-global-filter (same as TotalRecordsController)
        if ($request->has('filters')) {
            foreach (json_decode($request->filters, true) as $filter => $value) {
                if (empty($value)) {
                    continue;
                }
                if (class_exists($filter)) {
                    $query = (new $filter)->apply($request, $query, $value);
                }
            }
        }

        // Apply range filter if metric specifies a column and value is selected (same as TotalRecordsController)
        if (isset($options['rangeFilterColumn']) && $advanceFilterSelected && $advanceFilterSelected !== 'all') {
            $query->where($options['rangeFilterColumn'], $advanceFilterSelected);
        }

        // Apply query filters (same as TotalRecordsController)
        if ($options['queryFilter'] ?? false) {
            $queryFilter = $options['queryFilter'];
            foreach ($queryFilter as $qF) {
                if (isset($qF['value']) && ! is_array($qF['value'])) {
                    if (isset($qF['operator'])) {
                        $query->where($qF['key'], $qF['operator'], $qF['value']);
                    } else {
                        $query->where($qF['key'], $qF['value']);
                    }
                } else {
                    if ($qF['operator'] == 'IS NULL') {
                        $query->whereNull($qF['key']);
                    } elseif ($qF['operator'] == 'IS NOT NULL') {
                        $query->whereNotNull($qF['key']);
                    } elseif ($qF['operator'] == 'IN') {
                        $query->whereIn($qF['key'], $qF['value']);
                    } elseif ($qF['operator'] == 'NOT IN') {
                        $query->whereNotIn($qF['key'], $qF['value']);
                    } elseif ($qF['operator'] == 'BETWEEN') {
                        $query->whereBetween($qF['key'], $qF['value']);
                    } elseif ($qF['operator'] == 'NOT BETWEEN') {
                        $query->whereNotBetween($qF['key'], $qF['value']);
                    }
                }
            }
        }

        $query->groupBy($countryColumn)
            ->orderByDesc('value');

        $dataSet = $query->get();

        // Transform to key-value format: { 'US': 1000, 'DE': 500 }
        $geoData = $dataSet->pluck('value', 'country_code')->toArray();

        return response()->json([
            'data' => $geoData,
        ]);
    }
}