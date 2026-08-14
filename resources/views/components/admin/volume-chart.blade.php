<?php

use Livewire\Volt\Component;
use App\Models\LedgerEntry;
use App\Support\AdminCharts;

new class extends Component {
    public function with(): array
    {
        $this->authorize('view-metrics');

        $rows = LedgerEntry::where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('date(created_at) as day, type, sum(amount) as total')
            ->groupBy('day', 'type')
            ->get();

        $labels = [];
        $credits = [];
        $debits = [];
        $tableRows = [];

        foreach (range(29, 0) as $offset) {
            $day = now()->subDays($offset);
            $key = $day->format('Y-m-d');

            $credit = 0.0;
            $debit = 0.0;

            foreach ($rows as $row) {
                if ($row->day !== $key) {
                    continue;
                }

                if ($row->type === 'credit') {
                    $credit = (float) $row->total;
                } elseif ($row->type === 'debit') {
                    $debit = (float) $row->total;
                }
            }

            $labels[] = $day->format('M d');
            $credits[] = $credit;
            $debits[] = $debit;
            $tableRows[] = [
                $day->format('M d'),
                '$'.number_format($credit, 2),
                '$'.number_format($debit, 2),
            ];
        }

        return [
            'chartConfig' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        AdminCharts::flowLineDataset('Credits', $credits, AdminCharts::FLOW_IN),
                        AdminCharts::flowLineDataset('Debits', $debits, AdminCharts::FLOW_OUT),
                    ],
                ],
                'options' => AdminCharts::options(withLegend: true, currencyY: true),
            ],
            'table' => [
                'headings' => ['Date', 'Credits', 'Debits'],
                'rows' => $tableRows,
            ],
        ];
    }
}; ?>

<x-admin.chart-card
    title="Platform Transaction Volume"
    subtitle="Last 30 days"
    canvas-id="admin-volume-chart"
    :config="$chartConfig"
    :table="$table"
    footer="Credits and debits across all ledger entries, including system contra entries."
/>
