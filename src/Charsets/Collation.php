<?php
/**
 * Value object class for a collation
 */

declare(strict_types=1);

namespace PhpMyAdmin\Charsets;

use function __;
use function _pgettext;
use function explode;
use function implode;

/**
 * Value object class for a collation
 */
final class Collation
{
    /**
     * A description of the collation
     */
    private string $description;

    /**
     * @param string $name         The collation name
     * @param string $charset      The name of the character set with which the collation is associated
     * @param int    $id           The collation ID
     * @param bool   $isDefault    Whether the collation is the default for its character set
     * @param bool   $isCompiled   Whether the character set is compiled into the server
     * @param int    $sortLength   Used for determining the memory used to sort strings in this collation
     * @param string $padAttribute The collation pad attribute
     */
    private function __construct(
        private string $name,
        private string $charset,
        private int $id,
        private bool $isDefault,
        private bool $isCompiled,
        private int $sortLength,
        private string $padAttribute,
    ) {
        $this->description = $this->buildDescription();
    }

    /** @param string[] $state State obtained from the database server */
    public static function fromServer(array $state): self
    {
        return new self(
            $state['Collation'] ?? '',
            $state['Charset'] ?? '',
            (int) ($state['Id'] ?? 0),
            isset($state['Default']) && ($state['Default'] === 'Yes' || $state['Default'] === '1'),
            isset($state['Compiled']) && ($state['Compiled'] === 'Yes' || $state['Compiled'] === '1'),
            (int) ($state['Sortlen'] ?? 0),
            $state['Pad_attribute'] ?? '',
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isCompiled(): bool
    {
        return $this->isCompiled;
    }

    public function getSortLength(): int
    {
        return $this->sortLength;
    }

    public function getPadAttribute(): string
    {
        return $this->padAttribute;
    }

    /**
     * Returns description for given collation
     *
     * @return string collation description
     */
    private function buildDescription(): string
    {
        $parts = explode('_', $this->getName());

        $name = __('Unknown');
        $variant = null;
        $suffixes = [];
        $unicode = false;
        $unknown = false;

        $level = 0;
        foreach ($parts as $part) {
            if ($level === 0) {
                /* Next will be language */
                $level = 1;
                /* First should be charset */
                [$name, $unicode, $unknown, $variant] = $this->getNameForLevel0($unicode, $unknown, $part, $variant);
                continue;
            }

            if ($level === 1) {
                /* Next will be variant unless changed later */
                $level = 4;
                /* Locale name or code */
                [$name, $level, $found] = $this->getNameForLevel1($unicode, $unknown, $part, $name, $level);
                if ($found) {
                    continue;
                }
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
                /* Next will be suffix */
                $level = 5;
                /* Variant */
                $variantFound = $this->getVariant($part);
                if ($variantFound !== null) {
                    $variant = $variantFound;
                    continue;
                }
                // Not parsed token, fall to next level
            }

            if ($level < 5) {
                continue;
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
            '0900' => 'UCA 9.0.0',
            '520' => 'UCA 5.2.0',
            'mysql561' => 'MySQL 5.6.1',
            'mysql500' => 'MySQL 5.0.0',
            default => null,
        };
    }

    private function addSuffixes(string $part): string|null
    {
        return match ($part) {
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

    /**
     * @return array<int, bool|string|null>
     * @psalm-return array{string, bool, bool, string|null}
     */
    private function getNameForLevel0(
        bool $unicode,
        bool $unknown,
        string $part,
        string|null $variant,
    ): array {
        switch ($part) {
            case 'binary':
                $name = _pgettext('Collation', 'Binary');
                break;
            // Unicode charsets
            case 'utf8mb4':
                $variant = 'UCA 4.0.0';
                // Fall through to other unicode
            case 'ucs2':
            case 'utf8':
            case 'utf8mb3':
            case 'utf16':
            case 'utf16le':
            case 'utf16be':
            case 'utf32':
                $name = _pgettext('Collation', 'Unicode');
                $unicode = true;
                break;
            // West European charsets
            case 'ascii':
            case 'cp850':
            case 'dec8':
            case 'hp8':
            case 'latin1':
            case 'macroman':
                $name = _pgettext('Collation', 'West European');
                break;
            // Central European charsets
            case 'cp1250':
            case 'cp852':
            case 'latin2':
            case 'macce':
                $name = _pgettext('Collation', 'Central European');
                break;
            // Russian charsets
            case 'cp866':
            case 'koi8r':
                $name = _pgettext('Collation', 'Russian');
                break;
            // Chinese charsets
            case 'gb2312':
            case 'gbk':
                $name = _pgettext('Collation', 'Simplified Chinese');
                break;
            case 'big5':
                $name = _pgettext('Collation', 'Traditional Chinese');
                break;
            case 'gb18030':
                $name = _pgettext('Collation', 'Chinese');
                $unicode = true;
                break;
            // Japanese charsets
            case 'sjis':
            case 'ujis':
            case 'cp932':
            case 'eucjpms':
                $name = _pgettext('Collation', 'Japanese');
                break;
            // Baltic charsets
            case 'cp1257':
            case 'latin7':
                $name = _pgettext('Collation', 'Baltic');
                break;
            // Other
            case 'armscii8':
            case 'armscii':
                $name = _pgettext('Collation', 'Armenian');
                break;
            case 'cp1251':
                $name = _pgettext('Collation', 'Cyrillic');
                break;
            case 'cp1256':
                $name = _pgettext('Collation', 'Arabic');
                break;
            case 'euckr':
                $name = _pgettext('Collation', 'Korean');
                break;
            case 'hebrew':
                $name = _pgettext('Collation', 'Hebrew');
                break;
            case 'geostd8':
                $name = _pgettext('Collation', 'Georgian');
                break;
            case 'greek':
                $name = _pgettext('Collation', 'Greek');
                break;
            case 'keybcs2':
                $name = _pgettext('Collation', 'Czech-Slovak');
                break;
            case 'koi8u':
                $name = _pgettext('Collation', 'Ukrainian');
                break;
            case 'latin5':
                $name = _pgettext('Collation', 'Turkish');
                break;
            case 'swe7':
                $name = _pgettext('Collation', 'Swedish');
                break;
            case 'tis620':
                $name = _pgettext('Collation', 'Thai');
                break;
            default:
                $name = _pgettext('Collation', 'Unknown');
                $unknown = true;
                break;
        }

        return [$name, $unicode, $unknown, $variant];
    }

    /**
     * @return array<int, bool|int|string>
     * @psalm-return array{string, int, bool}
     */
    private function getNameForLevel1(
        bool $unicode,
        bool $unknown,
        string $part,
        string $name,
        int $level,
    ): array {
        $found = true;

        switch ($part) {
            case 'general':
                break;
            case 'bulgarian':
            case 'bg':
                $name = _pgettext('Collation', 'Bulgarian');
                break;
            case 'chinese':
            case 'cn':
            case 'zh':
                if ($unicode) {
                    $name = _pgettext('Collation', 'Chinese');
                }

                break;
            case 'croatian':
            case 'hr':
                $name = _pgettext('Collation', 'Croatian');
                break;
            case 'czech':
            case 'cs':
                $name = _pgettext('Collation', 'Czech');
                break;
            case 'danish':
            case 'da':
                $name = _pgettext('Collation', 'Danish');
                break;
            case 'english':
            case 'en':
                $name = _pgettext('Collation', 'English');
                break;
            case 'esperanto':
            case 'eo':
                $name = _pgettext('Collation', 'Esperanto');
                break;
            case 'estonian':
            case 'et':
                $name = _pgettext('Collation', 'Estonian');
                break;
            case 'german1':
                $name = _pgettext('Collation', 'German (dictionary order)');
                break;
            case 'german2':
                $name = _pgettext('Collation', 'German (phone book order)');
                break;
            case 'german':
            case 'de':
                /* Name is set later */
                $level = 2;
                break;
            case 'hungarian':
            case 'hu':
                $name = _pgettext('Collation', 'Hungarian');
                break;
            case 'icelandic':
            case 'is':
                $name = _pgettext('Collation', 'Icelandic');
                break;
            case 'japanese':
            case 'ja':
                $name = _pgettext('Collation', 'Japanese');
                break;
            case 'la':
                $name = _pgettext('Collation', 'Classical Latin');
                break;
            case 'latvian':
            case 'lv':
                $name = _pgettext('Collation', 'Latvian');
                break;
            case 'lithuanian':
            case 'lt':
                $name = _pgettext('Collation', 'Lithuanian');
                break;
            case 'korean':
            case 'ko':
                $name = _pgettext('Collation', 'Korean');
                break;
            case 'myanmar':
            case 'my':
                $name = _pgettext('Collation', 'Burmese');
                break;
            case 'persian':
                $name = _pgettext('Collation', 'Persian');
                break;
            case 'polish':
            case 'pl':
                $name = _pgettext('Collation', 'Polish');
                break;
            case 'roman':
                $name = _pgettext('Collation', 'West European');
                break;
            case 'romanian':
            case 'ro':
                $name = _pgettext('Collation', 'Romanian');
                break;
            case 'ru':
                $name = _pgettext('Collation', 'Russian');
                break;
            case 'si':
            case 'sinhala':
                $name = _pgettext('Collation', 'Sinhalese');
                break;
            case 'slovak':
            case 'sk':
                $name = _pgettext('Collation', 'Slovak');
                break;
            case 'slovenian':
            case 'sl':
                $name = _pgettext('Collation', 'Slovenian');
                break;
            case 'spanish':
                $name = _pgettext('Collation', 'Spanish (modern)');
                break;
            case 'es':
                /* Name is set later */
                $level = 3;
                break;
            case 'spanish2':
                $name = _pgettext('Collation', 'Spanish (traditional)');
                break;
            case 'swedish':
            case 'sv':
                $name = _pgettext('Collation', 'Swedish');
                break;
            case 'thai':
            case 'th':
                $name = _pgettext('Collation', 'Thai');
                break;
            case 'turkish':
            case 'tr':
                $name = _pgettext('Collation', 'Turkish');
                break;
            case 'ukrainian':
            case 'uk':
                $name = _pgettext('Collation', 'Ukrainian');
                break;
            case 'vietnamese':
            case 'vi':
                $name = _pgettext('Collation', 'Vietnamese');
                break;
            case 'unicode':
                if ($unknown) {
                    $name = _pgettext('Collation', 'Unicode');
                }

                break;
            default:
                $found = false;
        }

        return [$name, $level, $found];
    }
}
