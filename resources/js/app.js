import './bootstrap';

// Note: Alpine is bundled by Livewire 4 — do NOT import/start it here
// (a second instance breaks both).
import Chart from 'chart.js/auto';

window.Chart = Chart;
