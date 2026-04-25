<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination;

    public $type = '';
    public $search = '';
    public $limit = null;

    public function updatingType() { $this->resetPage(); }
    public function updatingSearch() { $this->resetPage(); }

    public function with()
    {
        $query = Auth::user()->primaryAccount?->ledgerEntries()?->with('transaction');

        if (!$query) {
            return ['entries' => collect(), 'chartData' => []];
        }

        // --- Chart Data Aggregation ---
        $chartEntries = (clone $query)->where('created_at', '>=', now()->subDays(7))->get();
        $dates = collect(range(6, 0))->map(fn($days) => now()->subDays($days)->format('M d'));
        
        $credits = [];
        $debits = [];
        
        foreach ($dates as $date) {
            $dayEntries = $chartEntries->filter(fn($e) => $e->created_at->format('M d') === $date);
            $credits[] = $dayEntries->where('type', 'credit')->sum('amount');
            $debits[] = $dayEntries->where('type', 'debit')->sum('amount');
        }

        $chartData = [
            'labels' => $dates->toArray(),
            'credits' => $credits,
            'debits' => $debits,
        ];
        // ------------------------------

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->search) {
            $query->where('description', 'like', '%' . $this->search . '%');
        }

        if ($this->limit) {
            return [
                'entries' => $query->latest()->limit($this->limit)->get(),
                'chartData' => $chartData
            ];
        }

        return [
            'entries' => $query->latest()->paginate(10),
            'chartData' => $chartData,
        ];
    }

    public function export()
    {
        $entries = Auth::user()->primaryAccount->ledgerEntries()->latest()->get();
        $filename = "statement_" . now()->format('Y-m-d_H-i-s') . ".csv";
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($entries) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Description', 'Reference', 'Type', 'Amount']);

            foreach ($entries as $entry) {
                fputcsv($file, [
                    $entry->created_at->format('Y-m-d H:i:s'),
                    $entry->description,
                    $entry->reference,
                    strtoupper($entry->type),
                    $entry->amount,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
};
?>

<div class="space-y-6">
    <!-- Chart Section -->
    @if(!$limit && count($chartData['labels'] ?? []) > 0)
    <div class="bg-white p-6 rounded-3xl border border-brand-border shadow-sm mb-6"
         x-data="{
            chartData: @js($chartData),
            init() {
                if(!window.Chart) {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                    script.onload = () => this.renderChart();
                    document.head.appendChild(script);
                } else {
                    this.renderChart();
                }
            },
            renderChart() {
                const ctx = document.getElementById('txChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: this.chartData.labels,
                        datasets: [
                            {
                                label: 'Income',
                                data: this.chartData.credits,
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.4
                            },
                            {
                                label: 'Expense',
                                data: this.chartData.debits,
                                borderColor: '#EF4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                fill: true,
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
         }">
         <h3 class="text-sm font-bold text-brand-text-primary mb-4">Cash Flow (Last 7 Days)</h3>
         <div class="h-64">
             <canvas id="txChart"></canvas>
         </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white p-4 rounded-2xl border border-brand-border flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <input type="text" wire:model.live="search" placeholder="Search transactions..." class="w-full px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm">
        </div>
        <div class="flex gap-2">
            <select wire:model.live="type" class="px-4 py-3 rounded-xl border-brand-border focus:ring-brand-primary focus:border-brand-primary text-sm bg-white">
                <option value="">All Types</option>
                <option value="debit">Debits (-)</option>
                <option value="credit">Credits (+)</option>
            </select>
            <button wire:click="export" class="px-6 py-3 bg-brand-primary text-white rounded-xl text-sm font-bold shadow-sm hover:opacity-90 transition-opacity">
                Export
            </button>
        </div>
    </div>

    <!-- History List -->
    <div class="bg-white rounded-3xl overflow-hidden border border-brand-border shadow-sm">
        <div class="divide-y divide-brand-border">
            @forelse($entries as $entry)
                <div class="p-6 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl {{ $entry->type === 'credit' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }} flex items-center justify-center flex-shrink-0">
                            @if($entry->type === 'credit')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                            @else
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                            @endif
                        </div>
                        <div>
                            <p class="font-bold text-brand-text-primary">{{ $entry->description }}</p>
                            <p class="text-[10px] text-brand-text-secondary font-mono mt-0.5">{{ $entry->transaction->reference }} • {{ $entry->created_at->format('j M Y, H:i') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-lg {{ $entry->type === 'credit' ? 'text-green-600' : 'text-brand-text-primary' }}">
                            {{ $entry->type === 'credit' ? '+' : '-' }}₦{{ number_format($entry->amount, 2) }}
                        </p>
                        <span class="text-[8px] uppercase tracking-widest font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                            {{ $entry->transaction->status }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="text-slate-300 mb-4 flex justify-center">
                        <svg class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <p class="text-brand-text-secondary font-medium">No transactions found matching your filters.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Pagination -->
    @if(method_exists($entries, 'links'))
    <div class="pt-4">
        {{ $entries->links() }}
    </div>
    @endif
</div>