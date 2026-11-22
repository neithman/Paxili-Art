{{--
    Sales chart card with Daily / Weekly / Monthly filter.
    Usage: @include('partials.chart', ['chart' => $chart, 'route' => 'admin.dashboard'])
    Drawing happens in public/js/app.js -> drawBarChart()
--}}
<div class="card">
    <div class="card-title">
        <span>Sales graph</span>
        <div class="flex">
            <select data-chart-switch="#salesChart" style="width:auto;padding:6px 12px">
                <option value="revenue">Revenue</option>
                <option value="sales">Items sold</option>
            </select>
            <div class="period-tabs">
                @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $key => $label)
                    <a href="{{ route($route, ['period' => $key]) }}" class="{{ $chart['period'] === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>
    <div class="chart-box">
        <canvas id="salesChart"
            data-chart='@json(["labels" => $chart["labels"], "values" => $chart["revenue"], "prefix" => config("app.currency")])'
            data-all='@json(["revenue" => $chart["revenue"], "sales" => $chart["sales"], "prefix" => config("app.currency")])'></canvas>
        <div class="chart-tooltip"></div>
    </div>
    <p class="muted small mt-1 mb-0">
        Total in this period: <strong>{{ money(array_sum($chart['revenue'])) }}</strong>
        from <strong>{{ array_sum($chart['sales']) }}</strong> item(s) sold.
    </p>
</div>
