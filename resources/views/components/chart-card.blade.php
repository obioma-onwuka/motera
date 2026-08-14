@props([
    'title' => '',
    'subtitle' => null,
    'canvasId' => '',
    'config' => [],
    'table' => null,
    'footer' => null,
    'centerValue' => null,
    'centerLabel' => null,
])

<div
    class="bg-white rounded-2xl border border-brand-border shadow-sm p-6 flex flex-col"
    x-data="{
        showTable: false,
        chart: null,
        config: @js($config),
        init() {
            if (window.Chart) {
                this.renderChart();
            }
        },
        renderChart() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            const cfg = this.applyConventions(structuredClone(this.config));
            if (!window.Chart || !this.$refs.canvas) { return; }
            this.chart = new Chart(this.$refs.canvas.getContext('2d'), cfg);
        },
        applyConventions(cfg) {
            // PHP cannot serialize closures; these flags opt into JS callbacks.
            if (cfg.options?.currencyY) {
                cfg.options.plugins.tooltip.callbacks = {
                    label: (ctx) => ` ${ctx.dataset.label}: $${Number(ctx.parsed.y ?? ctx.parsed).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`,
                };
                cfg.options.scales.y.ticks.callback = (v) => '$' + Intl.NumberFormat('en', { notation: 'compact' }).format(v);
                delete cfg.options.currencyY;
            }
            if (cfg.options?.countY) {
                const total = cfg.data.datasets[0].data.reduce((a, b) => a + b, 0);
                cfg.options.plugins.tooltip.callbacks = {
                    label: (ctx) => ` ${ctx.label}: ${ctx.parsed} accounts (${total > 0 ? Math.round((ctx.parsed / total) * 100) : 0}%)`,
                };
                delete cfg.options.countY;
            }
            return cfg;
        },
    }"
>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-brand-text-primary">{{ $title }}</h3>
            @if ($subtitle)
                <p class="text-[10px] text-brand-text-secondary uppercase font-bold tracking-widest mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($table)
            <button
                type="button"
                @click="showTable = !showTable"
                class="flex-shrink-0 text-[10px] font-black uppercase tracking-widest text-brand-primary bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-full transition-colors"
                x-text="showTable ? 'Chart' : 'Table'"
            >Table</button>
        @endif
    </div>

    <div x-show="!showTable" class="relative h-64">
        <canvas id="{{ $canvasId }}" x-ref="canvas"></canvas>
        @if ($centerValue !== null)
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span class="text-3xl font-black tracking-tight text-brand-text-primary">{{ $centerValue }}</span>
                @if ($centerLabel)
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $centerLabel }}</span>
                @endif
            </div>
        @endif
    </div>

    @if ($table)
        <div x-show="showTable" x-cloak class="h-64 overflow-y-auto border border-brand-border rounded-xl">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        @foreach ($table['headings'] as $heading)
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase whitespace-nowrap">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-border">
                    @foreach ($table['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td class="px-4 py-2.5 text-xs text-slate-600 tabular-nums whitespace-nowrap">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($footer)
        <p class="mt-4 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $footer }}</p>
    @endif
</div>

<script>
(function () {
    if (!window.Chart || Chart.registry.plugins.get('crosshair')) return;

    // Vertical hairline tracking the pointer's nearest X (line charts only — bars use per-mark tooltips).
    Chart.register({
        id: 'crosshair',
        afterDatasetsDraw(chart, args, opts) {
            if (!opts.enabled) return;
            const active = chart.tooltip.getActiveElements();
            if (!active.length) return;
            const x = active[0].element.x;
            const { ctx, chartArea } = chart;
            ctx.save();
            ctx.strokeStyle = '#CBD5E1';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(x, chartArea.top);
            ctx.lineTo(x, chartArea.bottom);
            ctx.stroke();
            ctx.restore();
        },
    });

    // Selective direct label: value at the end of the line, for datasets flagged endLabel: true.
    Chart.register({
        id: 'endLabel',
        afterDatasetsDraw(chart) {
            const { ctx, chartArea } = chart;
            chart.data.datasets.forEach((ds, i) => {
                if (!ds.endLabel) return;
                const meta = chart.getDatasetMeta(i);
                const last = meta.data[meta.data.length - 1];
                if (!last) return;
                const value = ds.data[ds.data.length - 1];
                ctx.save();
                ctx.fillStyle = '#475569';
                ctx.font = "700 10px 'Figtree', ui-sans-serif, system-ui, sans-serif";
                ctx.textBaseline = 'middle';
                ctx.fillText(String(value), Math.min(last.x + 8, chartArea.right - 40), last.y);
                ctx.restore();
            });
        },
    });
})();
</script>
