<?php
/**
 * Amazon Marketplace Pro
 *
 * NOTICE OF LICENCE
 *
 * This software is commercial and licensed, not sold. The licence is
 * bundled with this package in the file LICENSE.txt. Redistribution,
 * resale and publication of the source are prohibited.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

/*
 * An e-mail as the buyer-reply reader needs it: subject, sender and the text
 * of the message, from the raw message an IMAP server returns (RFC 5322 with
 * MIME parts, RFC 2045-2047).
 *
 * PHP 5.6+ compatible.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AmazonMailMessage
{
    /** Deepest nesting of multipart parts that is followed. */
    public static $MAX_DEPTH = 10;

    /** @var array lower-case header name => value */
    private $headers;

    /** @var string */
    private $body;

    /**
     * @param string $raw the message as the mail server stores it
     */
    public function __construct($raw)
    {
        list($this->headers, $this->body) = self::split((string) $raw);
    }

    /** @return string the decoded subject */
    public function subject()
    {
        return trim(self::decodeHeader($this->header('subject')));
    }

    /** @return string the sender's address, '' when there is none */
    public function fromAddress()
    {
        $from = self::decodeHeader($this->header('from'));
        if (preg_match('/<([^<>\s@]+@[^<>\s]+)>/', $from, $m)
            || preg_match('/([^\s<>"\'(),;:]+@[^\s<>"\'(),;:]+)/', $from, $m)) {
            return $m[1];
        }

        return '';
    }

    /** @return string the text of the message, preferring text/plain over HTML */
    public function text()
    {
        $found = ['plain' => '', 'html' => ''];
        self::collect($this->headers, $this->body, $found, 0);
        if ($found['plain'] !== '') {
            return trim($found['plain']);
        }
        if ($found['html'] === '') {
            return '';
        }
        $html = preg_replace('#<(script|style)\b.*?</\1>#is', '', $found['html']);
        $html = preg_replace('#<br\s*/?>|</p>|</div>|</tr>#i', "\n", $html);

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * @param string $name lower case
     *
     * @return string
     */
    private function header($name)
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : '';
    }

    /**
     * Headers and body of a message or of one MIME part.
     *
     * @param string $raw
     *
     * @return array [headers, body]
     */
    private static function split($raw)
    {
        if (preg_match('/\r?\n\r?\n/', $raw, $m, PREG_OFFSET_CAPTURE)) {
            $head = substr($raw, 0, $m[0][1]);
            $body = substr($raw, $m[0][1] + strlen($m[0][0]));
        } else {
            $head = $raw;
            $body = '';
        }
        // Folded header lines continue with whitespace.
        $head = preg_replace('/\r?\n[ \t]+/', ' ', $head);
        $headers = [];
        foreach (preg_split('/\r?\n/', $head) as $line) {
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $colon)));
            if ($name !== '' && !isset($headers[$name])) {
                $headers[$name] = trim(substr($line, $colon + 1));
            }
        }

        return [$headers, $body];
    }

    /**
     * Find the first text/plain and text/html parts, decoded to UTF-8.
     *
     * @param array $headers
     * @param string $body
     * @param array $found plain and html, filled in
     * @param int $depth
     */
    private static function collect(array $headers, $body, array &$found, $depth)
    {
        $type = self::contentType(isset($headers['content-type']) ? $headers['content-type'] : 'text/plain');

        if (strpos($type['mime'], 'multipart/') === 0) {
            if (!isset($type['params']['boundary']) || $depth >= self::$MAX_DEPTH) {
                return;
            }
            foreach (self::parts($body, $type['params']['boundary']) as $part) {
                list($partHeaders, $partBody) = self::split($part);
                self::collect($partHeaders, $partBody, $found, $depth + 1);
            }

            return;
        }

        $key = ($type['mime'] === 'text/plain') ? 'plain' : (($type['mime'] === 'text/html') ? 'html' : '');
        if ($key === '' || $found[$key] !== '') {
            return;
        }
        if (isset($headers['content-disposition']) && stripos($headers['content-disposition'], 'attachment') === 0) {
            return;
        }
        $encoding = isset($headers['content-transfer-encoding']) ? strtolower(trim($headers['content-transfer-encoding'])) : '';
        $charset = isset($type['params']['charset']) ? $type['params']['charset'] : '';
        $found[$key] = self::toUtf8(self::decodeTransfer($body, $encoding), $charset);
    }

    /**
     * @param string $value a Content-Type header
     *
     * @return array mime (lower case) and params (lower-case names)
     */
    private static function contentType($value)
    {
        $semicolon = strpos($value, ';');
        $mime = strtolower(trim($semicolon === false ? $value : substr($value, 0, $semicolon)));
        $params = [];
        if ($semicolon !== false
            && preg_match_all('/;\s*([\w.\-]+)\s*=\s*("(?:[^"\\\\]|\\\\.)*"|[^;\s]+)/', $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $param = $match[2];
                if (strlen($param) > 1 && $param[0] === '"') {
                    $param = preg_replace('/\\\\(.)/', '$1', substr($param, 1, -1));
                }
                $params[strtolower($match[1])] = $param;
            }
        }

        return ['mime' => $mime !== '' ? $mime : 'text/plain', 'params' => $params];
    }

    /**
     * The parts of a multipart body, without the preamble and epilogue.
     *
     * @param string $body
     * @param string $boundary
     *
     * @return string[]
     */
    private static function parts($body, $boundary)
    {
        $delimiter = preg_quote('--' . $boundary, '/');
        $pieces = preg_split('/(?:^|\r?\n)' . $delimiter . '(--|)[ \t]*(?=\r?\n|$)/', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        $parts = [];
        // pieces: preamble, [closing marker, part]...
        for ($i = 1, $count = count($pieces); $i < $count; $i += 2) {
            if ($pieces[$i] === '--') {
                break;
            }
            if (isset($pieces[$i + 1])) {
                $parts[] = preg_replace('/^\r?\n/', '', $pieces[$i + 1]);
            }
        }

        return $parts;
    }

    /**
     * @param string $content
     * @param string $encoding the Content-Transfer-Encoding, lower case
     *
     * @return string
     */
    private static function decodeTransfer($content, $encoding)
    {
        if ($encoding === 'base64') {
            return (string) base64_decode(preg_replace('/[^A-Za-z0-9+\/=]/', '', $content));
        }
        if ($encoding === 'quoted-printable') {
            return quoted_printable_decode($content);
        }

        return $content;
    }

    /**
     * Encoded words (=?charset?B|Q?...?=) decoded to UTF-8.
     *
     * @param string $value
     *
     * @return string
     */
    private static function decodeHeader($value)
    {
        $word = '=\?[^?\s]+\?[BbQq]\?[^?\s]*\?=';
        // Whitespace between two encoded words is not part of the text.
        $value = preg_replace('/(' . $word . ')\s+(?=' . $word . ')/', '$1', $value);

        return preg_replace_callback('/=\?([^?*\s]+)(?:\*[^?\s]*)?\?([BbQq])\?([^?\s]*)\?=/', function ($m) {
            $text = (strtoupper($m[2]) === 'B')
                ? base64_decode($m[3])
                : quoted_printable_decode(str_replace('_', ' ', $m[3]));

            return AmazonMailMessage::toUtf8((string) $text, $m[1]);
        }, $value);
    }

    /**
     * @param string $text
     * @param string $charset '' when the message does not say
     *
     * @return string
     */
    public static function toUtf8($text, $charset)
    {
        $charset = strtoupper(trim($charset));
        if ($charset === '' || $charset === 'UTF-8' || $charset === 'US-ASCII') {
            return $text;
        }
        if ($charset === 'ISO-8859-1' || $charset === 'LATIN1') {
            // Browsers and mail clients read ISO-8859-1 as its superset.
            $charset = 'WINDOWS-1252';
        }
        if (function_exists('mb_convert_encoding') && function_exists('mb_list_encodings')) {
            foreach (mb_list_encodings() as $known) {
                if (strtoupper($known) === $charset) {
                    return mb_convert_encoding($text, 'UTF-8', $known);
                }
            }
        }

        return $text;
    }
}
