<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Theme Generator styles for the pmahomme theme
 *
 * @package    PhpMyAdmin-theme
 * @subpackage PMAHomme
 */

// unplanned execution path
if (! defined('PHPMYADMIN') && ! defined('TESTSUITE')) {
    exit();
}

$pickerMask = $theme->getImgPath('theme_generator/picker_mask_200.png');
$arrow = $theme->getImgPath('theme_generator/arrow.png');
$arrows = $theme->getImgPath('theme_generator/arrows.png');
$hue = $theme->getImgPath('theme_generator/hue.png');
$alpha = $theme->getImgPath('theme_generator/alpha.png');
$alphaMask = $theme->getImgPath('theme_generator/alpha_mask.png');
$grain = $theme->getImgPath('theme_generator/grain.png');
?>

.ui-color-picker {
    width: 420px;
    margin: 0;
    border: 1px solid #DDD;
    background-color: #FFF;
    display: table;

    -moz-user-select: none;
    -webkit-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.ui-color-picker .picking-area {
    width: 198px;
    height: 198px;
    margin: 5px;
    border: 1px solid #DDD;
    position: relative;
    float: left;
    display: table;
}

.ui-color-picker .picking-area:hover {
    cursor: default;
}

/* HSB format - Hue-Saturation-Value(Brightness) */
.ui-color-picker .picking-area {
    background: url(<?php echo $pickerMask; ?>) center center;

    background: -moz-linear-gradient(bottom, #000 0%, rgba(0, 0, 0, 0) 100%),
                -moz-linear-gradient(left, #FFF 0%, rgba(255, 255, 255, 0) 100%);
    background: -webkit-linear-gradient(bottom, #000 0%, rgba(0, 0, 0, 0) 100%),
                -webkit-linear-gradient(left, #FFF 0%, rgba(255, 255, 255, 0) 100%);
    background: -ms-linear-gradient(bottom, #000 0%, rgba(0, 0, 0, 0) 100%),
                -ms-linear-gradient(left, #FFF 0%, rgba(255, 255, 255, 0) 100%);
    background: -o-linear-gradient(bottom, #000 0%, rgba(0, 0, 0, 0) 100%),
                -o-linear-gradient(left, #FFF 0%, rgba(255, 255, 255, 0) 100%);

    background-color: #F00;
}

/* HSL format - Hue-Saturation-Lightness */
.ui-color-picker[data-mode='HSL'] .picking-area {
    background: -moz-linear-gradient(top, hsl(0, 0%, 100%) 0%, hsla(0, 0%, 100%, 0) 50%,
                                    hsla(0, 0%, 0%, 0) 50%, hsl(0, 0%, 0%) 100%),
                -moz-linear-gradient(left, hsl(0, 0%, 50%) 0%, hsla(0, 0%, 50%, 0) 100%);
    background: -webkit-linear-gradient(top, hsl(0, 0%, 100%) 0%, hsla(0, 0%, 100%, 0) 50%,
                                    hsla(0, 0%, 0%, 0) 50%, hsl(0, 0%, 0%) 100%),
                -webkit-linear-gradient(left, hsl(0, 0%, 50%) 0%, hsla(0, 0%, 50%, 0) 100%);
    background: -ms-linear-gradient(top, hsl(0, 0%, 100%) 0%, hsla(0, 0%, 100%, 0) 50%,
                                    hsla(0, 0%, 0%, 0) 50%, hsl(0, 0%, 0%) 100%),
                -ms-linear-gradient(left, hsl(0, 0%, 50%) 0%, hsla(0, 0%, 50%, 0) 100%);
    background: -o-linear-gradient(top, hsl(0, 0%, 100%) 0%, hsla(0, 0%, 100%, 0) 50%,
                                    hsla(0, 0%, 0%, 0) 50%, hsl(0, 0%, 0%) 100%),
                -o-linear-gradient(left, hsl(0, 0%, 50%) 0%, hsla(0, 0%, 50%, 0) 100%);
    background-color: #F00;
}

.ui-color-picker .picker {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 1px solid #FFF;
    position: absolute;
    top: 45%;
    left: 45%;
}

.ui-color-picker .picker:before {
    width: 8px;
    height: 8px;
    content: "";
    position: absolute;
    border: 1px solid #999;
    border-radius: 50%;
}

.ui-color-picker .hue,
.ui-color-picker .alpha {
    width: 198px;
    height: 28px;
    margin: 5px;
    border: 1px solid #FFF;
    float: left;
}

.ui-color-picker .hue {
    background: url(<?php echo $hue; ?>) center;
    background: -moz-linear-gradient(left, #F00 0%, #FF0 16.66%, #0F0 33.33%, #0FF 50%,
                #00F 66.66%, #F0F 83.33%, #F00 100%);
    background: -webkit-linear-gradient(left, #F00 0%, #FF0 16.66%, #0F0 33.33%, #0FF 50%,
                #00F 66.66%, #F0F 83.33%, #F00 100%);
    background: -ms-linear-gradient(left, #F00 0%, #FF0 16.66%, #0F0 33.33%, #0FF 50%,
                #00F 66.66%, #F0F 83.33%, #F00 100%);
    background: -o-linear-gradient(left, #F00 0%, #FF0 16.66%, #0F0 33.33%, #0FF 50%,
                #00F 66.66%, #F0F 83.33%, #F00 100%);
}

.ui-color-picker .alpha {
    border: 1px solid #CCC;
    background: url(<?php echo $alpha; ?>);
}

.ui-color-picker .alpha-mask {
    width: 100%;
    height: 100%;
    background: url(<?php echo $alphaMask; ?>);
}

.ui-color-picker .slider-picker {
    width: 2px;
    height: 100%;
    border: 1px solid #777;
    background-color: #FFF;
    position: relative;
    top: -1px;
}

/* input HSB and RGB */

.ui-color-picker .info {
    width: 200px;
    margin: 5px;
    float: left;
}

.ui-color-picker .info * {
    float: left;
}

.ui-color-picker .input {
    width: 64px;
    margin: 5px 2px;
    float: left;
}

.ui-color-picker .input .name {
    height: 20px;
    width: 30px;
    text-align: center;
    font-size: 14px;
    line-height: 18px;
    float: left;
}

.ui-color-picker .input input {
    width: 30px;
    height: 18px;
    margin: 0;
    padding: 0;
    border: 1px solid #DDD;
    text-align: center;
    float: right;

    -moz-user-select: text;
    -webkit-user-select: text;
    -ms-user-select: text;
}

.ui-color-picker .input[data-topic="lightness"] {
    display: none;
}

.ui-color-picker[data-mode='HSL'] .input[data-topic="value"] {
    display: none;
}

.ui-color-picker[data-mode='HSL'] .input[data-topic="lightness"] {
    display: block;
}

.ui-color-picker .input[data-topic="alpha"] {
    margin-top: 10px;
    width: 93px;
}

.ui-color-picker .input[data-topic="alpha"] > .name {
    width: 60px;
}

.ui-color-picker .input[data-topic="alpha"] > input {
    float: right;
}

.ui-color-picker .input[data-topic="hexa"] {
    width: auto;
    float: right;
    margin: 6px 8px 0 0;
}

.ui-color-picker .input[data-topic="hexa"] > .name {
    display: none;
}

.ui-color-picker .input[data-topic="hexa"] > input {
    width: 90px;
    height: 24px;
    padding: 2px 0;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

/* Preview color */
.ui-color-picker .preview {
    width: 95px;
    height: 53px;
    margin: 5px;
    margin-top: 10px;
    border: 1px solid #DDD;
    background-image: url(<?php echo $alpha; ?>);
    float: left;
    position: relative;
}

.ui-color-picker .preview:before {
    height: 100%;
    width: 50%;
    left: 50%;
    top: 0;
    content: "";
    background: #FFF;
    position: absolute;
    z-index: 1;
}

.ui-color-picker .preview-color {
    width: 100%;
    height: 100%;
    background-color: rgba(255, 0, 0, 0.5);
    position: absolute;
    z-index: 1;
}

.ui-color-picker .switch_mode {
    width: 10px;
    height: 20px;
    position: relative;
    border-radius: 5px 0 0 5px;
    border: 1px solid #DDD;
    background-color: #EEE;
    left: -12px;
    top: -1px;
    z-index: 1;
    transition: all 0.5s;
}

.ui-color-picker .switch_mode:hover {
    background-color: #CCC;
    cursor: pointer;
}

/*
 * UI Component
 */

.ui-input-slider {
    height: 20px;
    font-family: "Segoe UI", Arial, Helvetica, sans-serif;
    -moz-user-select: none;
    user-select: none;
}

.ui-input-slider * {
    float: left;
    height: 100%;
    line-height: 100%;
}

/* Input Slider */

.ui-input-slider > input {
    margin: 0;
    padding: 0;
    width: 50px;
    text-align: center;

    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

.ui-input-slider-info {
    width: 90px;
    padding: 0px 10px 0px 0px;
    text-align: right;
    text-transform: lowercase;
}

.ui-input-slider-left, .ui-input-slider-right {
    width: 16px;
    cursor: pointer;
    background: url(<?php echo $arrows; ?>) center left no-repeat;
}

.ui-input-slider-right {
    background: url(<?php echo $arrows; ?>) center right no-repeat;
}

.ui-input-slider-name {
    width: 90px;
    padding: 0 10px 0 0;
    text-align: right;
    text-transform: lowercase;
}

.ui-input-slider-btn-set {
    width: 25px;
    background-color: #2C9FC9;
    border-radius: 5px;
    color: #FFF;
    font-weight: bold;
    line-height: 14px;
    text-align: center;
}

.ui-input-slider-btn-set:hover {
    background-color: #379B4A;
    cursor: pointer;
}

/*
 * COLOR PICKER TOOL
 */

body {
    max-width: 1000px;
    margin: 0 auto;

    font-family: "Segoe UI", Arial, Helvetica, sans-serif;

    box-shadow: 0 0 5px 0 #999;

    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;

    -moz-user-select: none;
    -webkit-user-select: none;
    -ms-user-select: none;
    user-select: none;

}

/**
 * 	Container
 */
#container {
    width: 100%;

    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;

    display: table;
}

/**
 * 	Picker Zone
 */

#picker {
    padding: 10px;
    width: 980px;
}

.ui-color-picker {
    padding: 3px 5px;
    float: left;
    border-color: #FFF;
}

.ui-color-picker .switch_mode {
    display: none;
}

.ui-color-picker .preview-color:hover {
    cursor: move;
}

/**
 * Picker Container
 */

#picker-samples {
    width: 375px;
    height: 114px;
    max-height: 218px;
    margin: 0 10px 0 30px;
    overflow: hidden;
    position: relative;
    float: left;

    transition: all 0.2s;
}

#picker-samples .sample {
    width: 40px;
    height: 40px;
    margin: 5px;
    border: 1px solid #DDD;
    position: absolute;
    float: left;
    transition: all 0.2s;
}

#picker-samples .sample:hover {
    cursor: pointer;
    border-color: #BBB;
    transform: scale(1.15);
    border-radius: 3px;
}

#picker-samples .sample[data-active='true'] {
    border-color: #999;
}

#picker-samples .sample[data-active='true']:after {
    content: "";
    position: absolute;
    background: url(<?php echo $arrow; ?>) center no-repeat;
    width: 100%;
    height: 12px;
    top: -12px;
    z-index: 2;
}

#picker-samples #add-icon {
    width: 100%;
    height: 100%;
    position: relative;
    box-shadow: inset 0px 0px 2px 0px #DDD;
}

#picker-samples #add-icon:hover {
    cursor: pointer;
    border-color: #DDD;
    box-shadow: inset 0px 0px 5px 0px #CCC;
}

#picker-samples #add-icon:before,
#picker-samples #add-icon:after {
    content: "";
    position: absolute;
    background-color: #EEE;
    box-shadow: 0 0 1px 0 #EEE;
}

#picker-samples #add-icon:before {
    width: 70%;
    height: 16%;
    top: 42%;
    left: 15%;
}

#picker-samples #add-icon:after {
    width: 16%;
    height: 70%;
    top: 15%;
    left: 42%;
}

#picker-samples #add-icon:hover:before,
#picker-samples #add-icon:hover:after {
    background-color: #DDD;
    box-shadow: 0 0 1px 0 #DDD;
}

/**
 * 	Controls
 */

#controls {
    width: 110px;
    padding: 10px;
    float: right;
}

#controls #picker-switch {
    text-align: center;
    float: left;
}

#controls .icon {
    width: 48px;
    height: 48px;
    margin: 10px 0;
    background-repeat: no-repeat;
    background-position: center;
    border: 1px solid #DDD;
    display: table;
    float: left;
}

#controls .icon:hover {
    cursor: pointer;
}

#controls .switch {
    width: 106px;
    padding: 1px;
    border: 1px solid #CCC;
    font-size: 14px;
    text-align: center;
    line-height: 24px;
    overflow: hidden;
    float: left;
}

#controls .switch:hover {
    cursor: pointer;
}

#controls .switch > * {
    width: 50%;
    padding: 2px 0;
    background-color: #EEE;
    float: left;
}

#controls .switch [data-active='true'] {
    color: #FFF;
    background-image: url(<?php echo $grain; ?>);
    background-color: #777;
}

/**
 * 	Color Theme
 */

#color-theme {
    margin: 0 8px 0 0;
    border: 1px solid #EEE;
    display: inline-block;
    float: right;
}

#color-theme .box {
    width: 80px;
    height: 92px;
    float: left;
}

/**
 * Color info box
 */
#color-info {
    width: 360px;
    float: left;
}

#color-info .title {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    text-align: center;
}

#color-info .copy-container {
    position: absolute;
    top: -100%;
}

#color-info .property {
    min-width: 280px;
    height: 30px;
    margin: 10px 0;
    text-align: center;
    line-height: 30px;
}

#color-info .property > * {
    float: left;
}

#color-info .property .type {
    width: 60px;
    height: 100%;
    padding: 0 16px 0 0;
    text-align: right;
}

#color-info .property .value {
    width: 200px;
    height: 100%;
    padding: 0 10px;
    font-family: "Segoe UI", Arial, Helvetica, sans-serif;
    font-size: 16px;
    color: #777;
    text-align: center;
    background-color: #FFF;
    border: none;
}

#color-info .property .value:hover {
    color: #37994A;
}

#color-info .property .value:hover + .copy {
    background-position: center right;
}

/**
 * 	Color Palette
 */

#palette {
    width: 700px;
    height: 220px;
    padding: 10px 0;
    background-image: url(<?php echo $grain; ?>);
    background-repeat: repeat;
    background-color: #EEE;
    color: #777;

    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

#color-palette {
    width: 640px;
    font-family: Arial, Helvetica, sans-serif;
    color: #777;
    float: left;
}

#color-palette .container {
    width: 100%;
    height: 50px;
    line-height: 50px;
    overflow: hidden;
    float: left;
    transition: all 0.5s;
}

#color-palette .container > * {
    float: left;
}

#color-palette .title {
    width: 100px;
    padding: 0 10px;
    text-align: right;
    line-height: inherit;
}

#color-palette .palette {
    width: 456px;
    height: 38px;
    margin: 3px;
    padding: 3px;
    display: table;
    background-color: #FFF;
}

#color-palette .palette .sample {
    width: 30px;
    height: 30px;
    margin: 3px;
    position: relative;
    border: 1px solid #DDD;
    float: left;
    transition: all 0.2s;
}

#color-palette .palette .sample:hover {
    cursor: pointer;
    border-color: #BBB;
    transform: scale(1.15);
    border-radius: 3px;
}

#zindex {
    height: 20px;
    margin: 5px;
    font-size: 16px;
    position: absolute;
    opacity: 0;
    top: -10000px;
    left: 0;
    color: #777;
    float: left;
    transition: opacity 1s;
}

#zindex input {
    border: 1px solid #DDD;
    font-size: 16px;
    color: #777;
}

#zindex .ui-input-slider-info {
    width: 60px;
}

#zindex[data-active='true'] {
    top: 0;
    opacity: 1;
}
