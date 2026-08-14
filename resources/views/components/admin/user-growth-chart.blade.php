<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Support\AdminCharts;

new class extends Component {
    public function with(): array
    {
        $this->authorize('view-metrics');

        $counts = User::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $signups = [];
        $tableRows = [];
        $total = 0;

        foreach (range(29, 0) as $offset) {
            $day = now()->subDays($offset);
            $key = $day->format('Y-m-d');

            // Floats so the chart series stays numeric end-to-end
            // (counts flow through the same decimal-style casts as amounts).
            $count = (float) ($counts[$key] ?? 0);
            $total += (int) $count;

            $labels[] = $day->format('M d');
            $signups[] = $count;
            $tableRows[] = [$day->format('M d'), (string) (int) $count];
        }

        return [
            'chartConfig' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [...AdminCharts::flowLineDataset('Signups', $signups, AdminCharts::GROWTH), 'endLabel' => true],
                    ],
                ],
                'options' => AdminCharts::options(withLegend: false),
            ],
            'table' => [
                'headings' => ['Date', 'New users'],
                'rows' => $tableRows,
            ],
            'total' => $total,
            'footer' => '30-day signups: '.number_format($total),
        ];
    }
}; ?>

<x-chart-card
    title="User Growth"
    subtitle="Signups per day, last 30 days"
    canvas-id="admin-user-growth-chart"
    :config="$chartConfig"
    :table="$table"
    :footer="$footer"
/>
