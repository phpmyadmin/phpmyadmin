<?php
/**
 * SVG QR Code renderer
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QRMarkupSVG;

use function abs;
use function ceil;
use function pow;
use function sprintf;

/**
 * a bit messy proof-of-concept for a PMA themed QR Code
 */
class PmaQrCodeSVG extends QRMarkupSVG
{
    // logo from https://simpleicons.org/?q=phpmyadmin
    // @todo: put logo in separate file perhaps?
    // phpcs:disable
    protected const LOGO = '
    <svg class="pma-qrcode-logo" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>phpMyAdmin</title>
        <path class="sail-dark" d="M5.463 3.476C6.69 5.225 7.497 7.399 7.68 9.798a12.9 12.9 0 0 1-.672 5.254 4.29 4.29 0 0 1 2.969-1.523c.05-.004.099-.006.148-.008.08-.491.47-3.45-.977-6.68-1.068-2.386-3-3.16-3.685-3.365Z"/>
        <path class="sail-light" d="M7.24 3.513s2.406 1.066 3.326 5.547c.607 2.955.049 4.836-.402 5.773a7.347 7.347 0 0 1 4.506-1.994c.86-.065 1.695.02 2.482.233-.1-.741-.593-3.414-2.732-5.92-3.263-3.823-7.18-3.64-7.18-3.64Z"/>
        <path class="boat" d="M22.057 13.214l-17.92 3.049a2.284 2.284 0 0 1 1.535 2.254 2.31 2.31 0 0 1-.106.61c.055-.027 2.689-1.275 6.342-2.034 3.238-.673 5.723-.36 6.285-.273a6.46 6.46 0 0 1 3.864-3.606Z"/>
        <path class="water" d="M15.844 17.292c-2.318 0-4.641.495-6.614 1.166-2.868.976-2.951 1.348-5.55 1.043C1.844 19.286 0 18.386 0 18.386s2.406 1.97 4.914 2.127c1.986.125 3.505-.822 5.315-1.414 2.661-.871 4.511-.97 6.253-.975C19.361 18.116 24 19.353 24 19.353s-2.11-1.044-5.033-1.72a13.885 13.885 0 0 0-3.123-.34Z"/>
    </svg>';
    // phpcs:enable

    protected const LOGOSCALE = 0.2;

    /**
     * We're going to set some options here that we don't want or need to bother with in the 2fa module
     */
    protected function setOptions(): void
    {
        $this->options->eccLevel = EccLevel::H;
        $this->options->svgAddXmlHeader = false;
        $this->options->svgUseFillAttributes = false;
        // css -> theme stylesheets
        $this->options->svgDefs = '
    <style><![CDATA[
        .pma-2fa-qrcode { min-width: 200px; max-width: 500px; height: auto; }

        .qr-finder-dark, .qr-alignment-dark, .qr-finder-dot { fill: #669; }
        .qr-data-dark{ fill: #f90; }

        .pma-qrcode-logo > .sail-dark{ fill: #669; }
        .pma-qrcode-logo > .sail-light{ fill: #f90; }
        .pma-qrcode-logo > .boat{ fill: #999; }
        .pma-qrcode-logo > .water{ fill: #ccc; }
    ]]></style>';

        $this->options->drawLightModules = false;
        $this->options->drawCircularModules = true;
        $this->options->circleRadius = 0.4;
        $this->options->keepAsSquare = [
            QRMatrix::M_FINDER_DARK,
            QRMatrix::M_FINDER_DOT,
            QRMatrix::M_ALIGNMENT_DARK,
        ];

        $this->options->connectPaths = true;
        $this->options->excludeFromConnect = [
            QRMatrix::M_FINDER_DARK,
            QRMatrix::M_FINDER_DOT,
            QRMatrix::M_ALIGNMENT_DARK,
            QRMatrix::M_LOGO,
        ];

        $this->copyVars();
    }

    protected function createMarkup(bool $saveToFile): string
    {
        $this->setOptions();
        $this->clearLogoSpace();

        $svg = $this->header();
        $svg .= sprintf('<defs>%1$s%2$s</defs>%2$s', $this->options->svgDefs, $this->eol);
        $svg .= $this->paths();
        $svg .= $this->getLogo();
        // close svg
        $svg .= sprintf('%1$s</svg>%1$s', $this->eol);

        // we're putting out the raw SVG here, the base64 URI option is ignored
        return $svg;
    }

    protected function clearLogoSpace(): void
    {
        $r = (int) ceil(($this->moduleCount * $this::LOGOSCALE + 2) / 2);
        $c = $this->moduleCount / 2;

        for ($y = 0; $y < $this->moduleCount; $y++) {
            for ($x = 0; $x < $this->moduleCount; $x++) {
                if (! $this->checkIfInsideCircle($x + 0.5, $y + 0.5, $c, $c, $r)) {
                    continue;
                }

                $this->matrix->set($x, $y, false, QRMatrix::M_LOGO);
            }
        }
    }

    /**
     * @see https://stackoverflow.com/a/7227057
     */
    protected function checkIfInsideCircle(float $x, float $y, float $centerX, float $centerY, float $radius): bool
    {
        $dx = abs($x - $centerX);
        $dy = abs($y - $centerY);

        if ($dx + $dy <= $radius) {
            return true;
        }

        if ($dx > $radius || $dy > $radius) {
            return false;
        }

        return pow($dx, 2) + pow($dy, 2) <= pow($radius, 2);
    }

    /**
     * returns a <g> element that contains the SVG logo and positions it properly within the QR Code
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Element/g
     * @see https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/transform
     */
    protected function getLogo(): string
    {
        // phpcs:ignore
        $template = '<g transform="translate(%1$s %1$s) scale(%2$s)" class="pma-qrcode-logo-transform">%4$s%3$s%4$s</g>';
        $translate = ($this->moduleCount - $this->moduleCount * $this::LOGOSCALE) / 2;

        return sprintf($template, $translate, $this::LOGOSCALE, $this::LOGO, $this->eol);
    }
}
