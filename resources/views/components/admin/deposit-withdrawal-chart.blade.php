<?php

use Livewire\Volt\Component;
use App\Models\Transaction;
use App\Enums\TransactionStatus;
use App\Support\AdminCharts;

new class extends Component {
    public function with(): array
    {
        $this->authorize('view-metrics');

        $rows = Transaction::where('status', TransactionStatus::SUCCESSFUL)
            ->whereIn('type', ['deposit', 'withdrawal'])
            ->where('created_at', '>=', now()->subWeeks(7)->startOfWeek())
            ->selectRaw('date(created_at) as day, type, sum(amount) as total')
            ->groupBy('day', 'type')
            ->get();

        $labels = [];
        $deposits = [];
        $withdrawals = [];
        $tableRows = [];

        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $deposit = 0.0;
            $withdrawal = 0.0;

            foreach ($rows as $row) {
                if ($row->day < $weekStart->format('Y-m-d') || $row->day > $weekEnd->format('Y-m-d')) {
                    continue;
                }

                if ($row->type === 'deposit') {
                    $deposit += (float) $row->total;
                } elseif ($row->type === 'withdrawal') {
                    $withdrawal += (float) $row->total;
                }
            }

            $label = $weekStart->format('M d');
            $labels[] = $label;
            $deposits[] = $deposit;
            $withdrawals[] = $withdrawal;
            $tableRows[] = [
                $label,
                '$'.number_format($deposit, 2),
                '$'.number_format($withdrawal, 2),
            ];
        }

        return [
            'chartConfig' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        AdminCharts::flowBarDataset('Deposits', $deposits, AdminCharts::FLOW_IN),
                        AdminCharts::flowBarDataset('Withdrawals', $withdrawals, AdminCharts::FLOW_OUT),
                    ],
                ],
                'options' => AdminCharts::options(withLegend: true, currencyY: true),
            ],
            'table' => [
                'headings' => ['Week of', 'Deposits', 'Withdrawals'],
                'rows' => $tableRows,
            ],
        ];
    }
}; ?>

<x-chart-card
    title="Deposits vs Withdrawals"
    subtitle="Last 8 weeks, successful transactions"
    canvas-id="admin-deposit-withdrawal-chart"
    :config="$chartConfig"
    :table="$table"
/>
