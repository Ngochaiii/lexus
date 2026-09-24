<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Lọc HTML của ô soạn thảo trước khi in ra trang.
 *
 * Nội dung do nhân viên đại lý nhập, nhưng phần lớn là DÁN từ Word/Google Docs
 * — thứ mang theo cả rừng thẻ và style rác, và trên lý thuyết là cả mã chạy
 * được. In thẳng bằng {!! !!} là mở một cửa XSS chỉ chờ một tài khoản admin bị
 * chiếm. Nên ở đây đi theo danh sách CHO PHÉP: thẻ nào không có tên trong danh
 * sách thì gỡ vỏ giữ ruột, thuộc tính nào không khai thì bỏ, link chỉ nhận
 * http(s), mailto, tel hoặc đường dẫn nội bộ.
 */
class RichText
{
    /** Thẻ giữ lại. Cố ý KHÔNG có script, style, iframe, form, span, div. */
    private const TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'blockquote', 'code', 'pre', 'hr', 'img', 'sub', 'sup',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** Thuộc tính giữ lại theo từng thẻ. Mọi on* và style đều rơi hết ở đây. */
    private const ATTRS = [
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    /** Thẻ bị xoá cả ruột, không chỉ gỡ vỏ. */
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        // Ép UTF-8: không có meta này DOMDocument đọc chuỗi theo ISO-8859-1,
        // tiếng Việt có dấu ra ký tự lạ.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"?><div id="rt-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('rt-root');

        if (! $root) {
            return '';
        }

        static::scrub($root);

        $out = '';

        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    /** Duyệt ngược để xoá/thay node giữa chừng không làm hỏng vòng lặp. */
    private static function scrub(DOMNode $node): void
    {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $child = $node->childNodes->item($i);

            if (! $child instanceof DOMElement) {
                continue;   // node chữ và comment: chữ giữ, comment vô hại
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, static::DROP, true)) {
                $node->removeChild($child);

                continue;
            }

            static::scrub($child);

            if (! in_array($tag, static::TAGS, true)) {
                static::unwrap($child);

                continue;
            }

            static::scrubAttributes($child, $tag);
        }
    }

    private static function scrubAttributes(DOMElement $el, string $tag): void
    {
        $allowed = static::ATTRS[$tag] ?? [];

        for ($i = $el->attributes->length - 1; $i >= 0; $i--) {
            $name = $el->attributes->item($i)->nodeName;

            if (! in_array(strtolower($name), $allowed, true)) {
                $el->removeAttribute($name);
            }
        }

        if ($tag === 'a') {
            $href = $el->getAttribute('href');

            if (! static::safeUrl($href)) {
                $el->removeAttribute('href');
            } elseif (str_starts_with(strtolower($href), 'http')) {
                // Link ra ngoài mở tab mới; noopener chặn trang đích với tới
                // window.opener của mình.
                $el->setAttribute('target', '_blank');
                $el->setAttribute('rel', 'noopener nofollow');
            }
        }

        if ($tag === 'img' && ! static::safeUrl($el->getAttribute('src'))) {
            $el->parentNode?->removeChild($el);
        }
    }

    private static function safeUrl(string $url): bool
    {
        return (bool) preg_match('#^(https?://|mailto:|tel:|/|\#)#i', trim($url));
    }

    /** Gỡ vỏ thẻ lạ nhưng giữ nguyên chữ bên trong. */
    private static function unwrap(DOMElement $el): void
    {
        while ($el->firstChild) {
            $el->parentNode->insertBefore($el->firstChild, $el);
        }

        $el->parentNode->removeChild($el);
    }
}
