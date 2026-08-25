<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Allowlist sanitizer for the rich-text fields that print templates render as raw
 * HTML (BAC resolution bodies, NOA/NTP remarks, item descriptions). Anything not
 * explicitly allowed here — script, event handlers, javascript: URLs, iframes —
 * is dropped, so a requester cannot plant script that runs in an approver's browser.
 */
class RichText
{
    protected static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        return static::sanitizer()->sanitize($html);
    }

    protected static function sanitizer(): HtmlSanitizer
    {
        if (static::$sanitizer instanceof HtmlSanitizer) {
            return static::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig())
            ->allowStaticElements()
            ->allowElement('p', ['style'])
            ->allowElement('br')
            ->allowElement('div', ['style'])
            ->allowElement('span', ['style'])
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('sub')
            ->allowElement('sup')
            ->allowElement('ul')
            ->allowElement('ol', ['type', 'start'])
            ->allowElement('li')
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('h5')
            ->allowElement('h6')
            ->allowElement('blockquote')
            ->allowElement('table', ['style', 'border', 'cellpadding', 'cellspacing'])
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tfoot')
            ->allowElement('tr', ['style'])
            ->allowElement('th', ['style', 'colspan', 'rowspan'])
            ->allowElement('td', ['style', 'colspan', 'rowspan'])
            ->allowElement('a', ['href', 'title'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->dropElement('script')
            ->dropElement('style')
            ->dropElement('iframe')
            ->dropElement('object')
            ->dropElement('embed')
            ->dropElement('form')
            ->dropElement('input');

        return static::$sanitizer = new HtmlSanitizer($config);
    }
}
