<?php

declare(strict_types=1);

namespace PhpMyAdmin\Image;

use GdImage;

use function extension_loaded;
use function function_exists;
use function imagearc;
use function imagecolorallocate;
use function imagecopyresampled;
use function imagecreatefromstring;
use function imagecreatetruecolor;
use function imagefilledpolygon;
use function imagefilledrectangle;
use function imagejpeg;
use function imageline;
use function imagepng;
use function imagestring;
use function imagesx;
use function imagesy;

final class ImageWrapper
{
    private function __construct(private GdImage $image)
    {
    }

    public function getImage(): GdImage
    {
        return $this->image;
    }

    /**
     * @param array<string, int>|null $background
     * @psalm-param array{red: int, green: int, blue: int} $background
     */
    public static function create(int $width, int $height, array|null $background = null): self|null
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            return null;
        }

        if (! isset($background, $background['red'], $background['green'], $background['blue'])) {
            return new self($image);
        }

        $backgroundColor = imagecolorallocate($image, $background['red'], $background['green'], $background['blue']);
        if ($backgroundColor === false) {
            return null;
        }

        if (! imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $backgroundColor)) {
            return null;
        }

        return new self($image);
    }

    public static function fromString(string $data): self|null
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $image = imagecreatefromstring($data);
        if ($image === false) {
            return null;
        }

        return new self($image);
    }

    public function arc(
        int $centerX,
        int $centerY,
        int $width,
        int $height,
        int $startAngle,
        int $endAngle,
        int $color,
    ): bool {
        return imagearc($this->image, $centerX, $centerY, $width, $height, $startAngle, $endAngle, $color);
    }

    public function colorAllocate(int $red, int $green, int $blue): int|false
    {
        return imagecolorallocate($this->image, $red, $green, $blue);
    }

    public function copyResampled(
        ImageWrapper $sourceImage,
        int $destinationX,
        int $destinationY,
        int $sourceX,
        int $sourceY,
        int $destinationWidth,
        int $destinationHeight,
        int $sourceWidth,
        int $sourceHeight,
    ): bool {
        return imagecopyresampled(
            $this->image,
            $sourceImage->getImage(),
            $destinationX,
            $destinationY,
            $sourceX,
            $sourceY,
            $destinationWidth,
            $destinationHeight,
            $sourceWidth,
            $sourceHeight,
        );
    }

    /** @param list<int> $points */
    public function filledPolygon(array $points, int $color): bool
    {
        return imagefilledpolygon($this->image, $points, $color);
    }

    public function height(): int
    {
        return imagesy($this->image);
    }

    /** @param resource|string|null $file */
    public function jpeg($file = null, int $quality = -1): bool
    {
        if (! function_exists('imagejpeg')) {
            return false;
        }

        return imagejpeg($this->image, $file, $quality);
    }

    public function line(int $x1, int $y1, int $x2, int $y2, int $color): bool
    {
        return imageline($this->image, $x1, $y1, $x2, $y2, $color);
    }

    /** @param resource|string|null $file */
    public function png($file = null, int $quality = -1, int $filters = -1): bool
    {
        if (! function_exists('imagepng')) {
            return false;
        }

        return imagepng($this->image, $file, $quality, $filters);
    }

    public function string(int $font, int $x, int $y, string $string, int $color): bool
    {
        return imagestring($this->image, $font, $x, $y, $string, $color);
    }

    public function width(): int
    {
        return imagesx($this->image);
    }
}
