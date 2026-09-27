<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\SiteEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Số liệu cho trang Báo cáo trong admin (App\Filament\Pages\Reports).
 *
 * Tính bằng PHP trên dữ liệu thô: lượng lead/sự kiện của một đại lý nhỏ,
 * không cần kho dữ liệu hay bảng tổng hợp.
 */
class Insights
{
    public function __construct(public readonly int $days = 30) {}

    private function since(): Carbon
    {
        return now()->subDays($this->days)->startOfDay();
    }

    /** @return Collection<int, Lead> */
    private function leads(): Collection
    {
        return once(fn () => Lead::query()->with('variant')->where('created_at', '>=', $this->since())->get());
    }

    /** Tỷ lệ chốt = giao xe / (lead thật, trừ spam). */
    private static function rate(int $won, int $total): ?float
    {
        return $total > 0 ? round($won / $total * 100, 1) : null;
    }

    /** @return array<string, mixed> */
    public function overview(): array
    {
        $leads = $this->leads()->where('status', '!=', 'spam');
        $won = $leads->where('status', 'won');

        return [
            'leads' => $leads->count(),
            'open' => $leads->whereIn('status', Lead::OPEN)->count(),
            'won' => $won->count(),
            'rate' => self::rate($won->count(), $leads->count()),
            'deal_value' => (float) Lead::where('status', 'won')->where('closed_at', '>=', $this->since())->sum('deal_value'),
            'commission' => (float) Lead::where('status', 'won')->where('closed_at', '>=', $this->since())->sum('commission'),
            'due' => Lead::whereIn('status', Lead::OPEN)->where('follow_up_at', '<=', now()->endOfDay())->count(),
        ];
    }

    /** Phễu: số lead đang ở từng bước. @return array<string, int> */
    public function funnel(): array
    {
        $counts = $this->leads()->countBy('status');

        return collect(Lead::STATUSES)->map(fn ($label, $key) => (int) ($counts[$key] ?? 0))->all();
    }

    /** @return array<int, array{label:string, leads:int, won:int, rate:?float}> */
    public function bySource(): array
    {
        return $this->leads()->where('status', '!=', 'spam')
            ->groupBy(fn ($l) => $l->source ?: 'unknown')
            ->map(fn ($g, $source) => [
                'label' => $source === 'unknown' ? 'Chưa rõ (lead cũ)' : Lead::sourceLabel($source),
                'leads' => $g->count(),
                'won' => $g->where('status', 'won')->count(),
                'rate' => self::rate($g->where('status', 'won')->count(), $g->count()),
            ])
            ->sortByDesc('leads')->values()->all();
    }

    /** @return array<int, array{label:string, leads:int, won:int, rate:?float}> */
    public function byVariant(): array
    {
        return $this->leads()->where('status', '!=', 'spam')
            ->groupBy(fn ($l) => $l->variant?->name ?? 'Chưa chọn phiên bản')
            ->map(fn ($g, $name) => [
                'label' => $name,
                'leads' => $g->count(),
                'won' => $g->where('status', 'won')->count(),
                'rate' => self::rate($g->where('status', 'won')->count(), $g->count()),
            ])
            ->sortByDesc('leads')->values()->all();
    }

    /** @return array<int, array{label:string, count:int}> */
    public function lostReasons(): array
    {
        return $this->leads()->where('status', 'lost')
            ->countBy(fn ($l) => Lead::LOST_REASONS[$l->lost_reason] ?? 'Chưa ghi lý do')
            ->sortDesc()->map(fn ($count, $label) => ['label' => $label, 'count' => $count])->values()->all();
    }

    /** Bấm Gọi / Zalo theo trang. @return array<int, array{path:string, call:int, zalo:int, total:int}> */
    public function contactClicks(): array
    {
        return SiteEvent::query()->whereIn('type', ['call', 'zalo'])->where('created_at', '>=', $this->since())
            ->get(['type', 'path'])
            ->groupBy(fn ($e) => $e->path ?: '(không rõ)')
            ->map(fn ($g, $path) => [
                'path' => $path,
                'call' => $g->where('type', 'call')->count(),
                'zalo' => $g->where('type', 'zalo')->count(),
                'total' => $g->count(),
            ])
            ->sortByDesc('total')->values()->take(20)->all();
    }

    /** Ngưỡng "tốt" / "cần cải thiện" của Google cho từng chỉ số. */
    public const VITAL_THRESHOLDS = ['LCP' => [2500, 4000], 'INP' => [200, 500], 'CLS' => [0.1, 0.25], 'FCP' => [1800, 3000], 'TTFB' => [800, 1800]];

    public static function p75(Collection $values): ?float
    {
        $sorted = $values->sort()->values();
        if ($sorted->isEmpty()) {
            return null;
        }

        return (float) $sorted[(int) ceil(0.75 * $sorted->count()) - 1];
    }

    public static function rating(string $metric, ?float $value): ?string
    {
        if ($value === null || ! isset(self::VITAL_THRESHOLDS[$metric])) {
            return null;
        }
        [$good, $poor] = self::VITAL_THRESHOLDS[$metric];

        return $value <= $good ? 'good' : ($value <= $poor ? 'needs' : 'poor');
    }

    /**
     * p75 từng chỉ số theo thiết bị — cách Google chấm Core Web Vitals. 28 ngày.
     *
     * @return array<string, array<string, array{value:?float, rating:?string, n:int}>>
     */
    public function vitals(): array
    {
        $rows = SiteEvent::query()->where('type', 'vital')->where('created_at', '>=', now()->subDays(28))->get(['metric', 'value', 'device']);
        $out = [];
        foreach (array_keys(self::VITAL_THRESHOLDS) as $metric) {
            foreach (['all' => null, 'mobile' => 'mobile', 'desktop' => 'desktop'] as $key => $device) {
                $values = $rows->where('metric', $metric)->when($device, fn ($c) => $c->where('device', $device))->pluck('value');
                $p75 = self::p75($values);
                $out[$metric][$key] = ['value' => $p75, 'rating' => self::rating($metric, $p75), 'n' => $values->count()];
            }
        }

        return $out;
    }

    /** Trang chậm nhất theo LCP p75 (đủ 5 lượt đo). @return array<int, array{path:string, lcp:float, rating:string, n:int}> */
    public function slowestPages(): array
    {
        return SiteEvent::query()->where('type', 'vital')->where('metric', 'LCP')->where('created_at', '>=', now()->subDays(28))
            ->get(['path', 'value'])
            ->groupBy('path')
            ->filter(fn ($g) => $g->count() >= 5)
            ->map(fn ($g, $path) => ['path' => $path, 'lcp' => self::p75($g->pluck('value')), 'n' => $g->count()])
            ->map(fn ($r) => $r + ['rating' => self::rating('LCP', $r['lcp'])])
            ->sortByDesc('lcp')->values()->take(10)->all();
    }
}
