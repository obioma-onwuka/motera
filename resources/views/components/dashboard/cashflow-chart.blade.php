<?php

use Livewire\Volt\Component;
use App\Support\AdminCharts;

new class extends Component {
    public function with(): array
    {
        $account = auth()->user()->primaryAccount;

        $rows = $account
            ? $account->ledgerEntries()
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->selectRaw('date(created_at) as day, type, sum(amount) as total')
                ->groupBy('day', 'type')
                ->get()
            : collect();

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
            'hasAccount' => $account !== null,
            'chartConfig' => [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        AdminCharts::flowLineDataset('Income', $credits, AdminCharts::FLOW_IN),
                        AdminCharts::flowLineDataset('Spending', $debits, AdminCharts::FLOW_OUT),
                    ],
                ],
                'options' => AdminCharts::options(withLegend: true, currencyY: true),
            ],
            'table' => [
                'headings' => ['Date', 'Income', 'Spending'],
                'rows' => $tableRows,
            ],
        ];
    }
}; ?>

@if($hasAccount)
    <x-chart-card title="Cash Flow" subtitle="Income vs spending — last 30 days" canvas-id="user-cashflow-chart" :config="$chartConfig" :table="$table" footer="Based on your primary account ledger. Income and spending include system entries." />
@else
    <div class="border-2 border-dashed border-brand-border rounded-[2rem] p-8 text-center">
        <div class="h-10 w-10 mx-auto rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        </div>
        <p class="text-sm font-bold text-brand-text-primary mt-4">No account yet</p>
        <p class="text-xs text-brand-text-secondary mt-1">Your cashflow chart will appear once your account is active.</p>
    </div>
@endif
