<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function array_filter;
use function array_intersect;
use function array_map;
use function explode;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function function_exists;
use function fwrite;
use function iconv;
use function mb_convert_encoding;
use function mb_convert_kana;
use function mb_detect_encoding;
use function mb_list_encodings;
use function preg_replace;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function strtoupper;
use function tempnam;
use function unlink;

/**
 * Encoding conversion helper class
 */
class Encoding
{
    public const ENGINE_NONE = 'none';
    public const ENGINE_ICONV = 'iconv';
    public const ENGINE_MBSTRING = 'mbstring';

    /**
     * Chosen encoding engine
     *
     * @var self::ENGINE_NONE|self::ENGINE_ICONV|self::ENGINE_MBSTRING|null
     */
    private static string|null $engine = null;

    /**
     * Map of conversion engine configurations
     *
     * Each entry contains:
     *
     * - function to detect
     * - engine contant
     * - extension name to warn when missing
     */
    private const ENGINE_MAP = [
        'iconv' => 'iconv',
        'mbstring' => 'mb_convert_encoding',
        'none' => 'isset',
    ];

    /**
     * Order of automatic detection of engines
     */
    private const ENGINE_ORDER = ['iconv', 'mbstring'];

    /**
     * Kanji encodings list
     */
    private static string $kanjiEncodings = 'ASCII,SJIS,EUC-JP,JIS';

    /**
     * Initializes encoding engine detecting available backends.
     */
    public static function initEngine(): void
    {
        $engine = Config::getInstance()->config->RecodingEngine;

        /* Use user configuration */
        if (isset(self::ENGINE_MAP[$engine])) {
            if (function_exists(self::ENGINE_MAP[$engine])) {
                self::$engine = $engine;

                return;
            }

            Core::warnMissingExtension($engine);
        }

        /* Autodetection */
        foreach (self::ENGINE_ORDER as $engine) {
            if (function_exists(self::ENGINE_MAP[$engine])) {
                self::$engine = $engine;

                return;
            }
        }

        /* Fallback to none conversion */
        self::$engine = self::ENGINE_NONE;
    }

    /**
     * Setter for engine. Use with caution, mostly useful for testing.
     *
     * @param self::ENGINE_NONE|self::ENGINE_ICONV|self::ENGINE_MBSTRING $engine
     */
    public static function setEngine(string $engine): void
    {
        self::$engine = $engine;
    }

    /**
     * Checks whether there is any charset conversion supported
     */
    public static function isSupported(): bool
    {
        if (self::$engine === null) {
            self::initEngine();
        }

        return self::$engine !== self::ENGINE_NONE;
    }

    /**
     * Converts encoding of text according to parameters with detected
     * conversion function.
     *
     * @param string $srcCharset  source charset
     * @param string $destCharset target charset
     * @param string $what        what to convert
     *
     * @return string   converted text
     */
    public static function convertString(
        string $srcCharset,
        string $destCharset,
        string $what,
    ): string {
        if ($srcCharset === $destCharset) {
            return $what;
        }

        if (self::$engine === null) {
            self::initEngine();
        }

        $config = Config::getInstance();
        $iconvExtraParams = '';
        if (str_starts_with($config->config->IconvExtraParams, '//')) {
            $iconvExtraParams = $config->config->IconvExtraParams;
        }

        return match (self::$engine) {
            self::ENGINE_ICONV => iconv($srcCharset, $destCharset . $iconvExtraParams, $what),
            self::ENGINE_MBSTRING => mb_convert_encoding($what, $destCharset, $srcCharset),
            default => $what,
        };
    }

    /**
     * Detects whether Kanji encoding is available
     */
    public static function canConvertKanji(): bool
    {
        return Current::$lang === 'ja';
    }

    /**
     * Setter for Kanji encodings. Use with caution, mostly useful for testing.
     */
    public static function getKanjiEncodings(): string
    {
        return self::$kanjiEncodings;
    }

    /**
     * Setter for Kanji encodings. Use with caution, mostly useful for testing.
     *
     * @param string $value Kanji encodings list
     */
    public static function setKanjiEncodings(string $value): void
    {
        self::$kanjiEncodings = $value;
    }

    /**
     * Reverses SJIS & EUC-JP position in the encoding codes list
     */
    public static function kanjiChangeOrder(): void
    {
        $parts = explode(',', self::$kanjiEncodings);
        if ($parts[1] === 'EUC-JP') {
            self::$kanjiEncodings = 'ASCII,SJIS,EUC-JP,JIS';

            return;
        }

        self::$kanjiEncodings = 'ASCII,EUC-JP,SJIS,JIS';
    }

    /**
     * Kanji string encoding convert
     *
     * @param string $str  the string to convert
     * @param string $enc  the destination encoding code
     * @param string $kana set 'kana' convert to JIS-X208-kana
     *
     * @return string   the converted string
     */
    public static function kanjiStrConv(string $str, string $enc, string $kana): string
    {
        if ($enc === '' && $kana === '') {
            return $str;
        }

        $stringEncoding = mb_detect_encoding($str, self::$kanjiEncodings);
        if ($stringEncoding === false) {
            $stringEncoding = 'utf-8';
        }

        if ($kana === 'kana') {
            $dist = mb_convert_kana($str, 'KV', $stringEncoding);
            $str = $dist;
        }

        if ($stringEncoding !== $enc && $enc !== '') {
            return mb_convert_encoding($str, $enc, $stringEncoding);
        }

        return $str;
    }

    /**
     * Kanji file encoding convert
     *
     * @param string $file the name of the file to convert
     * @param string $enc  the destination encoding code
     * @param string $kana set 'kana' convert to JIS-X208-kana
     *
     * @return string   the name of the converted file
     */
    public static function kanjiFileConv(string $file, string $enc, string $kana): string
    {
        if ($enc === '' && $kana === '') {
            return $file;
        }

        $tmpfname = (string) tempnam(Config::getInstance()->getUploadTempDir(), $enc);
        $fpd = fopen($tmpfname, 'wb');
        if ($fpd === false) {
            return $file;
        }

        $fps = fopen($file, 'r');
        if ($fps === false) {
            return $file;
        }

        self::kanjiChangeOrder();
        while (! feof($fps)) {
            $line = fgets($fps, 4096);
            if ($line === false) {
                continue;
            }

            $dist = self::kanjiStrConv($line, $enc, $kana);
            fwrite($fpd, $dist);
        }

        self::kanjiChangeOrder();
        fclose($fps);
        fclose($fpd);
        unlink($file);

        return $tmpfname;
    }

    /**
     * Defines radio form fields to switch between encoding modes
     *
     * @return string HTML code for the radio controls
     */
    public static function kanjiEncodingForm(): string
    {
        $template = new Template();

        return $template->render('encoding/kanji_encoding_form');
    }

    /**
     * Lists available encodings.
     *
     * @return string[]
     */
    public static function listEncodings(): array
    {
        if (self::$engine === null) {
            self::initEngine();
        }

        /* Most engines do not support listing */
        $config = Config::getInstance();
        if (self::$engine !== self::ENGINE_MBSTRING) {
            return array_filter($config->settings['AvailableCharsets'], static function (string $charset): bool {
                // Removes any ignored character
                $normalizedCharset = strtoupper((string) preg_replace(['/[^A-Za-z0-9\-\/]/'], '', $charset));

                // The character set ISO-2022-CN-EXT can be vulnerable (CVE-2024-2961).
                return ! str_contains($normalizedCharset, 'ISO-2022-CN-EXT')
                    && ! str_contains($normalizedCharset, 'ISO2022CNEXT');
            });
        }

        return array_intersect(
            array_map(strtolower(...), mb_list_encodings()),
            $config->settings['AvailableCharsets'],
        );
    }
}
