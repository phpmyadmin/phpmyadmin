<?php
/**
 * Value object class for a collation
 */

declare(strict_types=1);

namespace PhpMyAdmin\Charsets;

use function _pgettext;
use function array_shift;
use function explode;
use function implode;
use function in_array;

/**
 * Value object class for a collation
 */
final class Collation
{
    private const UNICODE_CHARSETS = ['ucs2', 'utf8', 'utf8mb3', 'utf8mb4', 'utf16', 'utf16le', 'utf16be', 'utf32'];

    private string|null $description = null;

    /** @var array<string, string|null> */
    private static array $suffixesCache = [];

    /**
     * @param string $name      The collation name
     * @param string $charset   The name of the character set with which the collation is associated
     * @param bool   $isDefault Whether the collation is the default for its character set
     */
    private function __construct(
        private string $name,
        private string $charset,
        private bool $isDefault,
    ) {
    }

    /** @param string[] $state State obtained from the database server */
    public static function fromServer(array $state): self
    {
        return new self(
            $state['Collation'] ?? '',
            $state['Charset'] ?? '',
            isset($state['Default']) && ($state['Default'] === 'Yes' || $state['Default'] === '1'),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description ??= $this->buildDescription();
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * Returns description for given collation
     *
     * @return string collation description
     */
    private function buildDescription(): string
    {
        $parts = explode('_', $this->name);

        /* First should be charset */
        $charset = array_shift($parts);
        $charsetName = $this->getCharsetName($charset);
        $unknown = $charsetName === null;
        $unicode = in_array($charset, self::UNICODE_CHARSETS, true);
        $name = $charsetName ?? _pgettext('Collation', 'Unknown');
        $variant = $charset === 'utf8mb4' ? 'UCA 4.0.0' : null;
        $suffixes = [];
        $languageSkipped = false;

        /* Next will be language */
        $level = 1;
        foreach ($parts as $part) {
            if ($level === 1) {
                /* Locale name or code */
                if ($part === 'german' || $part === 'de') {
                    /* Name is set by the next token */
                    $level = 2;
                    continue;
                }

                if ($part === 'es') {
                    /* Name is set by the next token */
                    $level = 3;
                    continue;
                }

                /* Next will be variant */
                $level = 4;
                $language = $this->getLanguageName($part, $name, $unicode, $unknown);
                if ($language !== null) {
                    $name = $language;
                    continue;
                }

                $languageSkipped = true;
                // Not parsed token, fall to next level
            }

            if ($level === 2) {
                /* Next will be variant */
                $level = 4;
                /* Germal variant */
                if ($part === 'pb') {
                    $name = _pgettext('Collation', 'German (phone book order)');
                    continue;
                }

                $name = _pgettext('Collation', 'German (dictionary order)');
                // Not parsed token, fall to next level
            }

            if ($level === 3) {
                /* Next will be variant */
                $level = 4;
                /* Spanish variant */
                if ($part === 'trad') {
                    $name = _pgettext('Collation', 'Spanish (traditional)');
                    continue;
                }

                $name = _pgettext('Collation', 'Spanish (modern)');
                // Not parsed token, fall to next level
            }

            if ($level === 4) {
                /* Script of the language, e.g. utf8mb4_sr_latn_0900_ai_ci */
                $script = $this->getScript($part);
                if ($script !== null) {
                    $name .= ' (' . $script . ')';
                    continue;
                }

                /* Next will be suffix */
                $level = 5;
                /* Variant */
                $variantFound = $this->getVariant($part);
                if ($variantFound !== null) {
                    $variant = $variantFound;
                    if ($languageSkipped) {
                        /* MariaDB puts the language after the variant, e.g. utf8mb4_uca1400_swedish_ai_ci */
                        $level = 1;
                        $languageSkipped = false;
                    }

                    continue;
                }
                // Not parsed token, fall to next level
            }

            /* Suffixes */
            $suffix = $this->addSuffixes($part);
            if ($suffix === null) {
                continue;
            }

            $suffixes[] = $suffix;
        }

        return $this->buildName($name, $variant, $suffixes);
    }

    /** @param string[] $suffixes */
    private function buildName(string $result, string|null $variant, array $suffixes): string
    {
        if ($variant !== null) {
            $result .= ' (' . $variant . ')';
        }

        if ($suffixes !== []) {
            $result .= ', ' . implode(', ', $suffixes);
        }

        return $result;
    }

    private function getVariant(string $part): string|null
    {
        return match ($part) {
            'uca1400' => 'UCA 14.0.0',
            '0900' => 'UCA 9.0.0',
            '520' => 'UCA 5.2.0',
            'mysql561' => 'MySQL 5.6.1',
            'mysql500' => 'MySQL 5.0.0',
            default => null,
        };
    }

    private function getScript(string $part): string|null
    {
        return match ($part) {
            'cyrl' => _pgettext('Collation', 'Cyrillic'),
            'latn' => _pgettext('Collation', 'Latin'),
            default => null,
        };
    }

    private function addSuffixes(string $part): string|null
    {
        if (isset(self::$suffixesCache[$part])) {
            return self::$suffixesCache[$part];
        }

        return self::$suffixesCache[$part] = match ($part) {
            'ci' => _pgettext('Collation variant', 'case-insensitive'),
            'cs' => _pgettext('Collation variant', 'case-sensitive'),
            'ai' => _pgettext('Collation variant', 'accent-insensitive'),
            'as' => _pgettext('Collation variant', 'accent-sensitive'),
            'ks' => _pgettext('Collation variant', 'kana-sensitive'),
            'w2','l2' => _pgettext('Collation variant', 'multi-level'),
            'bin' => _pgettext('Collation variant', 'binary'),
            'nopad' => _pgettext('Collation variant', 'no-pad'),
            default => null,
        };
    }

    private function getCharsetName(string $charset): string|null
    {
        if (in_array($charset, self::UNICODE_CHARSETS, true)) {
            return _pgettext('Collation', 'Unicode');
        }

        return match ($charset) {
            'binary' => _pgettext('Collation', 'Binary'),
            // West European charsets
            'ascii', 'cp850', 'dec8', 'hp8', 'latin1', 'macroman' => _pgettext('Collation', 'West European'),
            // Central European charsets
            'cp1250', 'cp852', 'latin2', 'macce' => _pgettext('Collation', 'Central European'),
            // Russian charsets
            'cp866', 'koi8r' => _pgettext('Collation', 'Russian'),
            // Chinese charsets
            'gb2312', 'gbk' => _pgettext('Collation', 'Simplified Chinese'),
            'big5' => _pgettext('Collation', 'Traditional Chinese'),
            'gb18030' => _pgettext('Collation', 'Chinese'),
            // Japanese charsets
            'sjis', 'ujis', 'cp932', 'eucjpms' => _pgettext('Collation', 'Japanese'),
            // Baltic charsets
            'cp1257', 'latin7' => _pgettext('Collation', 'Baltic'),
            // Other
            'armscii8', 'armscii' => _pgettext('Collation', 'Armenian'),
            'cp1251' => _pgettext('Collation', 'Cyrillic'),
            'cp1256' => _pgettext('Collation', 'Arabic'),
            'euckr' => _pgettext('Collation', 'Korean'),
            'hebrew' => _pgettext('Collation', 'Hebrew'),
            'geostd8' => _pgettext('Collation', 'Georgian'),
            'greek' => _pgettext('Collation', 'Greek'),
            'keybcs2' => _pgettext('Collation', 'Czech-Slovak'),
            'koi8u' => _pgettext('Collation', 'Ukrainian'),
            'latin5' => _pgettext('Collation', 'Turkish'),
            'swe7' => _pgettext('Collation', 'Swedish'),
            'tis620' => _pgettext('Collation', 'Thai'),
            default => null,
        };
    }

    /**
     * @param string $name    The name found so far, kept by tokens that do not name a language
     * @param bool   $unicode Whether the charset is a Unicode charset
     * @param bool   $unknown Whether the charset is not known
     *
     * @return string|null The new name, or null when the token is not a language
     */
    private function getLanguageName(string $part, string $name, bool $unicode, bool $unknown): string|null
    {
        return match ($part) {
            'general' => $name,
            'bosnian', 'bs' => _pgettext('Collation', 'Bosnian'),
            'bulgarian', 'bg' => _pgettext('Collation', 'Bulgarian'),
            'chinese', 'cn', 'zh' => $unicode ? _pgettext('Collation', 'Chinese') : $name,
            'croatian', 'hr' => _pgettext('Collation', 'Croatian'),
            'czech', 'cs' => _pgettext('Collation', 'Czech'),
            'danish', 'da' => _pgettext('Collation', 'Danish'),
            'english', 'en' => _pgettext('Collation', 'English'),
            'esperanto', 'eo' => _pgettext('Collation', 'Esperanto'),
            'estonian', 'et' => _pgettext('Collation', 'Estonian'),
            'galician', 'gl' => _pgettext('Collation', 'Galician'),
            'german1' => _pgettext('Collation', 'German (dictionary order)'),
            'german2' => _pgettext('Collation', 'German (phone book order)'),
            'hungarian', 'hu' => _pgettext('Collation', 'Hungarian'),
            'icelandic', 'is' => _pgettext('Collation', 'Icelandic'),
            'japanese', 'ja' => _pgettext('Collation', 'Japanese'),
            'la' => _pgettext('Collation', 'Classical Latin'),
            'latvian', 'lv' => _pgettext('Collation', 'Latvian'),
            'lithuanian', 'lt' => _pgettext('Collation', 'Lithuanian'),
            'korean', 'ko' => _pgettext('Collation', 'Korean'),
            'mongolian', 'mn' => _pgettext('Collation', 'Mongolian'),
            'myanmar', 'my' => _pgettext('Collation', 'Burmese'),
            'nb' => _pgettext('Collation', 'Norwegian Bokmål'),
            'nn' => _pgettext('Collation', 'Norwegian Nynorsk'),
            'persian' => _pgettext('Collation', 'Persian'),
            'polish', 'pl' => _pgettext('Collation', 'Polish'),
            'roman' => _pgettext('Collation', 'West European'),
            'romanian', 'ro' => _pgettext('Collation', 'Romanian'),
            'ru' => _pgettext('Collation', 'Russian'),
            'serbian', 'sr' => _pgettext('Collation', 'Serbian'),
            'si', 'sinhala' => _pgettext('Collation', 'Sinhalese'),
            'slovak', 'sk' => _pgettext('Collation', 'Slovak'),
            'slovenian', 'sl' => _pgettext('Collation', 'Slovenian'),
            'spanish' => _pgettext('Collation', 'Spanish (modern)'),
            'spanish2' => _pgettext('Collation', 'Spanish (traditional)'),
            'swedish', 'sv' => _pgettext('Collation', 'Swedish'),
            'thai', 'th' => _pgettext('Collation', 'Thai'),
            'turkish', 'tr' => _pgettext('Collation', 'Turkish'),
            'ukrainian', 'uk' => _pgettext('Collation', 'Ukrainian'),
            'vietnamese', 'vi' => _pgettext('Collation', 'Vietnamese'),
            'unicode' => $unknown ? _pgettext('Collation', 'Unicode') : $name,
            default => null,
        };
    }
}
