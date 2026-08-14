<?php

namespace App\Support;

/**
 * Brand chart palette + Chart.js config builders for the admin dashboard.
 *
 * Palette instance (light-only charts on white cards, surface #ffffff).
 * Validated — re-run when changing values:
 *   node scripts/validate_palette.js "#0D9488,#EB6834" --mode light --surface "#ffffff"
 *   node scripts/validate_palette.js "#60A5FA,#3B82F6,#1D4ED8" --ordinal --mode light --surface "#ffffff"
 *
 * Status colors (#16A34A, #D97706, #DC2626) are reserved for status pills only.
 */
final class AdminCharts
{
    public const FLOW_IN = '#0D9488'; // credits / deposits

    public const FLOW_OUT = '#EB6834'; // debits / withdrawals

    public const GROWTH = '#1D4ED8'; // single-series blue (brand primary)

    public const TIER_RAMP = ['#60A5FA', '#3B82F6', '#1D4ED8']; // ordinal, tier_1 → tier_3

    public const INK_TICK = '#475569';

    public const INK_GRID = '#E2E8F0';

    public const INK_AXIS = '#CBD5E1';

    public const TOOLTIP_BG = '#0F172A';

    private static function font(int $size = 11, string $weight = '400'): array
    {
        return ['family' => "'Figtree', ui-sans-serif, system-ui, sans-serif", 'size' => $size, 'weight' => $weight];
    }

    private static function rgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return sprintf('rgba(%d,%d,%d,%s)', $r, $g, $b, rtrim(rtrim(sprintf('%.2f', $alpha), '0'), '.'));
    }

    /**
     * 2px line series with 10% area wash; hover dot with 2px white surface ring.
     */
    public static function flowLineDataset(string $label, array $data, string $hex): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'borderColor' => $hex,
            'backgroundColor' => self::rgba($hex, 0.10),
            'fill' => true,
            'tension' => 0.35,
            'borderWidth' => 2,
            'pointRadius' => 0,
            'pointHoverRadius' => 5,
            'pointHoverBorderWidth' => 2,
            'pointHoverBorderColor' => '#FFFFFF',
            'pointHoverBackgroundColor' => $hex,
        ];
    }

    /**
     * Grouped bars, ≤24px thick, 4px rounded data-end, square at baseline.
     */
    public static function flowBarDataset(string $label, array $data, string $hex): array
    {
        return [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $hex,
            'borderWidth' => 0,
            'borderSkipped' => false,
            'borderRadius' => ['topLeft' => 4, 'topRight' => 4, 'bottomLeft' => 0, 'bottomRight' => 0],
            'categoryPercentage' => 0.6,
            'barPercentage' => 0.5,
            'maxBarThickness' => 24,
        ];
    }

    /**
     * Shared options. $currencyY: the chart-card partial injects JS callbacks
     * (currency tooltip label + compact $ y-tick) — PHP arrays cannot carry closures.
     */
    public static function options(bool $withLegend, bool $currencyY = false): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => [
                    'display' => $withLegend,
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'color' => self::INK_TICK,
                        'boxWidth' => 24,
                        'boxHeight' => 2,
                        'padding' => 16,
                        'font' => self::font(11, '700'),
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => self::TOOLTIP_BG,
                    'titleColor' => '#94A3B8',
                    'bodyColor' => '#FFFFFF',
                    'titleFont' => self::font(10, '700'),
                    'bodyFont' => self::font(12, '700'),
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                    'boxWidth' => 8,
                    'boxHeight' => 2,
                ],
                'crosshair' => ['enabled' => true],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'border' => ['color' => self::INK_AXIS],
                    'ticks' => ['color' => self::INK_TICK, 'maxTicksLimit' => 6, 'maxRotation' => 0, 'font' => self::font()],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => self::INK_GRID],
                    'border' => ['display' => false],
                    'ticks' => ['color' => self::INK_TICK, 'maxTicksLimit' => 5, 'font' => self::font()],
                ],
            ],
            'currencyY' => $currencyY, // convention flag, stripped + expanded by chart-card JS
        ];
    }
}
