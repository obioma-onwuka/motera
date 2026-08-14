<?php

use Livewire\Volt\Component;
use App\Models\BankAccount;
use App\Support\AdminCharts;

new class extends Component {
    public function with(): array
    {
        $this->authorize('view-metrics');

        $counts = BankAccount::selectRaw('tier, count(*) as total')
            ->groupBy('tier')
            ->pluck('total', 'tier');

        $data = [];
        foreach (['tier_1', 'tier_2', 'tier_3'] as $tier) {
            $data[] = (int) ($counts[$tier] ?? 0);
        }

        $total = array_sum($data);

        $legend = [];
        foreach ($data as $index => $count) {
            $legend[] = [
                'label' => 'Tier '.($index + 1),
                'hex' => AdminCharts::TIER_RAMP[$index],
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        $tableRows = [];
        foreach ($legend as $item) {
            $tableRows[] = [$item['label'], (string) $item['count'], $item['percent'].'%'];
        }

        return [
            'chartConfig' => [
                'type' => 'doughnut',
                'data' => [
                    'labels' => ['Tier 1', 'Tier 2', 'Tier 3'],
                    'datasets' => [[
                        'data' => $data,
                        'backgroundColor' => AdminCharts::TIER_RAMP,
                        'borderColor' => '#FFFFFF',
                        'borderWidth' => 2,
                        'hoverOffset' => 6,
                    ]],
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'cutout' => '68%',
                    'plugins' => [
                        'legend' => ['display' => false],
                        'tooltip' => [
                            'backgroundColor' => AdminCharts::TOOLTIP_BG,
                            'bodyColor' => '#FFFFFF',
                            'padding' => 12,
                            'cornerRadius' => 8,
                        ],
                        'crosshair' => ['enabled' => false],
                    ],
                    'countY' => true,
                ],
            ],
            'table' => [
                'headings' => ['Tier', 'Accounts', 'Share'],
                'rows' => $tableRows,
            ],
            'totalAccounts' => $total,
            'legend' => $legend,
        ];
    }
}; ?>

<div class="space-y-6">
    <x-admin.chart-card
        title="Account Tier Distribution"
        subtitle="Bank accounts by KYC tier"
        canvas-id="admin-tier-donut"
        :config="$chartConfig"
        :table="$table"
        :center-value="number_format($totalAccounts)"
        center-label="Accounts"
    />

    <div class="bg-white rounded-2xl border border-brand-border shadow-sm p-6 space-y-3">
        @foreach ($legend as $item)
            <div class="flex items-center gap-3">
                <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" style="background-color: {{ $item['hex'] }}"></span>
                <span class="text-xs font-bold text-slate-700">{{ $item['label'] }}</span>
                <span class="ml-auto text-xs font-bold text-slate-700 tabular-nums">
                    {{ number_format($item['count']) }}
                    <span class="text-slate-400 font-medium">· {{ $item['percent'] }}%</span>
                </span>
            </div>
        @endforeach
    </div>
</div>
