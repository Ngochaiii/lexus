<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Copy <table> từ web khác, dán vào ô "Dán bảng từ HTML", parser đọc
 * <tr>/<td> và đổ ra cấu trúc `specs`:
 *
 *   [{ "group": "Động Cơ & Hiệu Năng",
 *      "rows": [{"label": "Loại động cơ", "value": "V35A-FTS"}] }]
 *
 * Dòng chỉ có một ô (hoặc ô có colspan) được hiểu là tiêu đề nhóm.
 * Hàng nào không rơi vào nhóm nào thì gom vào nhóm mặc định.
 */
class SpecTableParser
{
    public function __construct(
        protected string $defaultGroup = 'Thông số',
    ) {}

    /** @return array<int, array{group: string, rows: array<int, array{label: string, value: string}>}> */
    public function parse(string $html): array
    {
        if (blank(trim($html))) {
            return [];
        }

        $rows = $this->extractRows($html);

        if ($rows === []) {
            return $this->parseAsPlainText($html);
        }

        $groups = [];
        $current = null;

        foreach ($rows as $cells) {
            // Dòng một ô → tiêu đề nhóm mới
            if (count($cells) === 1) {
                $title = $cells[0];

                if (blank($title)) {
                    continue;
                }

                $current = $title;
                $groups[$current] ??= [];

                continue;
            }

            [$label, $value] = [array_shift($cells), implode(' · ', array_filter($cells))];

            if (blank($label) && blank($value)) {
                continue;
            }

            $current ??= $this->defaultGroup;
            $groups[$current] ??= [];
            $groups[$current][] = ['label' => $label, 'value' => $value];
        }

        return $this->toSpecs($groups);
    }

    /**
     * Lấy các hàng đã chuẩn hoá text. Ô có colspan coi như ô duy nhất của hàng.
     *
     * @return array<int, array<int, string>>
     */
    protected function extractRows(string $html): array
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        $rows = [];

        /** @var DOMElement $tr */
        foreach ($xpath->query('//tr') ?: [] as $tr) {
            $cells = [];

            foreach ($tr->childNodes as $cell) {
                if (! $cell instanceof DOMElement || ! in_array(strtolower($cell->nodeName), ['td', 'th'], true)) {
                    continue;
                }

                $text = $this->text($cell);

                // Ô trải hết hàng → coi như hàng tiêu đề nhóm
                if ((int) $cell->getAttribute('colspan') > 1) {
                    $cells = [$text];
                    break;
                }

                $cells[] = $text;
            }

            $cells = $this->trimTrailingEmpty($cells);

            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /**
     * Không có <table> — thử đọc dạng "Nhãn: giá trị" mỗi dòng một cặp.
     *
     * @return array<int, array{group: string, rows: array<int, array{label: string, value: string}>}>
     */
    protected function parseAsPlainText(string $text): array
    {
        $rows = [];

        foreach (preg_split('/\R/', strip_tags($text)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }

            [$label, $value] = array_map(trim(...), explode(':', $line, 2));

            if ($label !== '' && $value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        return $rows === [] ? [] : $this->toSpecs([$this->defaultGroup => $rows]);
    }

    /** @param array<string, array<int, array{label: string, value: string}>> $groups */
    protected function toSpecs(array $groups): array
    {
        return collect($groups)
            ->reject(fn (array $rows) => $rows === [])
            ->map(fn (array $rows, string $group) => ['group' => $group, 'rows' => array_values($rows)])
            ->values()
            ->all();
    }

    protected function text(DOMNode $node): string
    {
        $text = html_entity_decode($node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{a0}", ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /** @param array<int, string> $cells */
    protected function trimTrailingEmpty(array $cells): array
    {
        while ($cells !== [] && blank(end($cells))) {
            array_pop($cells);
        }

        return array_values($cells);
    }
}
