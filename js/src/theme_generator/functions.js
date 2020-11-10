/* global ColorPickerTool */ // js/theme_generator/preview.js

AJAX.registerOnload('vendor/mozilla-color-picker.js', function () {
    ColorPickerTool.init();
    var paletteList = document.getElementsByClassName('palette');
    var titleList = document.getElementsByClassName('title');
    for (var i = 0; i < paletteList.length; i++) {
        (function (i) {
            paletteList[i].onclick = function () {
                document.getElementById('theme').options.selectedIndex = i;
            };
            titleList[i].onclick = function () {
                document.getElementById('theme').options.selectedIndex = i;
            };
        }(i));
    }
});

/**
 * Generates theme CSS files from SCSS files
 *
 * @param {Object<string,string>} arr containing SCSS files data
 *
 * @param {String} name Name of the theme
 *
 */
// eslint-disable-next-line no-unused-vars
function generateCss (arr, name) {
    arr['theme.scss'] = arr['theme.scss'].replace('../../bootstrap/scss/', '');
    arr['_bootstrap.scss'] = arr['_bootstrap.scss'].replace(/\.\.\/\.\.\/\.\.\/node_modules\/bootstrap\/scss/g, 'bootstrap');
    // Array related with generateFiles array in ThemeGeneratorController
    var generateFiles = ['printview.scss', 'theme.scss', 'theme-rtl.scss'];
    var fileCount = 0;
    for (var file in arr) {
        var splitFile = file.split('/');
        if (splitFile[splitFile.length - 1][0] === '_' || splitFile[splitFile.length - 1] === 'theme.scss') {
            // eslint-disable-next-line no-undef
            Sass.writeFile(file, arr[file]);
        }
    }
    for (var i = 0; i < generateFiles.length; i++) {
        // eslint-disable-next-line no-undef,no-loop-func
        Sass.compile(arr[generateFiles[i]], function callback (result) {
            $.post('index.php?route=/theme-generator/save', {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'CSS_file' : result.text,
                'count' : fileCount,
                'name': name,
            }, function (data) {
                if (data.success === false) {
                    // in the case of an error, show the error message returned.
                    Functions.ajaxShowMessage(data.error, false);
                } else {
                    Functions.ajaxShowMessage(data.message, false);
                }
            });
            fileCount += 1;
        });
    }
}

/**
 * Generates Variables SCSS file
 *
 * @param {Object<string,string>} colorPalette contains selected palette information
 *
 * @returns {String} variables file string
 *
 */
// eslint-disable-next-line no-unused-vars
function getVariablesFile (colorPalette) {
    var txt = '$navi-width: 240px;';
    txt = txt.concat('$navi-color: #000;');
    txt = txt.concat('$navi-background: ' + colorPalette['Navigation Panel'] + ';');
    txt = txt.concat('$navi-right-gradient: ' + colorPalette['Background Colour'] + ';');
    txt = txt.concat('$navi-pointer-color: #000;');
    txt = txt.concat('$navi-pointer-background: ' + colorPalette['Navigation Hover'] + ';');
    txt = txt.concat('$main-color: ' + colorPalette['Text Colour'] + ';');
    txt = txt.concat('$browse-pointer-color: #000;');
    txt = txt.concat('$browse-pointer-background: #cfc;');
    txt = txt.concat('$browse-marker-color: #000;');
    txt = txt.concat('$browse-marker-background: #fc9;');
    txt = txt.concat('$border: 0;');
    txt = txt.concat('$table-variants: (');
    txt = txt.concat('"primary": #0D6EFD,');
    txt = txt.concat('"secondary": #6C757D,');
    txt = txt.concat('"success": #198754,');
    txt = txt.concat('"info": #0DCAF0,');
    txt = txt.concat('"warning": #FFC107,');
    txt = txt.concat('"danger": #DC3545,');
    txt = txt.concat('"light": ' + colorPalette['Table Row Background'] + ',');
    txt = txt.concat('"dark": #212529,');
    txt = txt.concat(');');
    txt = txt.concat('$th-background: ' + colorPalette['Table Header and Footer Background'] + ';');
    txt = txt.concat('$th-color: color-contrast(' + colorPalette['Table Header and Footer Background'] + ',' + colorPalette['Table Header and Footer Text Colour'] + ');');
    txt = txt.concat('$th-hyperlink-color: ' + colorPalette['Hyperlink Text'] + ';');
    txt = txt.concat('$bg-one: #e5e5e5;');
    txt = txt.concat('$bg-two: #d5d5d5;');
    txt = txt.concat('$body-color: ' + colorPalette['Text Colour'] + ';');
    txt = txt.concat('$body-bg: ' + colorPalette['Background Colour'] + ';');
    txt = txt.concat('$link-color: ' + colorPalette['Hyperlink Text'] + ';');
    txt = txt.concat('$link-decoration: none;');
    txt = txt.concat('$link-hover-color: ' + colorPalette['Hyperlink Text'] + ';');
    txt = txt.concat('$link-hover-decoration: underline;');
    txt = txt.concat('$font-family-base: sans-serif;');
    txt = txt.concat('$font-family-monospace: monospace;');
    txt = txt.concat('$font-size-base: 0.82rem;');
    txt = txt.concat('$h1-font-size: 140%;');
    txt = txt.concat('$h2-font-size: 2em;');
    txt = txt.concat('$h3-font-size: 1rem;');
    txt = txt.concat('$table-cell-padding: 0.1em 0.3em;');
    txt = txt.concat('$table-cell-padding-sm: $table-cell-padding;');
    txt = txt.concat('$table-head-bg: #fff;');
    txt = txt.concat('$table-head-color: $th-color;');
    txt = txt.concat('$table-striped-order: even;');
    txt = txt.concat('$table-accent-bg: #dfdfdf;');
    txt = txt.concat('$table-hover-color: $browse-pointer-color;');
    txt = txt.concat('$table-border-color: #fff;');
    txt = txt.concat('$table-border-width: 0;');
    txt = txt.concat('$table-text-shadow: ' + colorPalette['Table Row Background'] + ';');
    txt = txt.concat('$enable-gradients: true;');
    txt = txt.concat('$enable-shadows: true;');
    txt = txt.concat('$enable-transitions: false;');
    txt = txt.concat('$primary: #ddd;');
    txt = txt.concat('$secondary: #ddd;');
    txt = txt.concat('$btn-border-radius: 0.85rem;');
    txt = txt.concat('$btn-line-height: 1.15;');
    txt = txt.concat('$dropdown-padding-y: 0;');
    txt = txt.concat('$dropdown-item-padding-y: 0;');
    txt = txt.concat('$dropdown-item-padding-x: 0;');
    txt = txt.concat('$form-check-input-margin-y: 0.1rem;');
    txt = txt.concat('$nav-tabs-border-color: #aaa;');
    txt = txt.concat('$nav-tabs-link-active-border-color: #aaa #aaa #fff;');
    txt = txt.concat('$nav-tabs-link-hover-border-color: $bg-two $bg-two #aaa;');
    txt = txt.concat('$nav-tabs-link-active-color: #000;');
    txt = txt.concat('$enable-caret: false;');
    txt = txt.concat('$navbar-padding-y: 0;');
    txt = txt.concat('$navbar-padding-x: 0;');
    txt = txt.concat('$navbar-light-color: #235a81;');
    txt = txt.concat('$navbar-light-hover-color: #235a81;');
    txt = txt.concat('$navbar-light-active-color: #235a81;');
    txt = txt.concat('$navbar-light-disabled-color: #235a81;');
    txt = txt.concat('$pagination-active-color: #235a81;');
    txt = txt.concat('$pagination-border-color: #aaa;');
    txt = txt.concat('$pagination-hover-border-color: #aaa;');
    txt = txt.concat('$pagination-active-border-color: #aaa;');
    txt = txt.concat('$pagination-disabled-border-color: #aaa;');
    txt = txt.concat('$card-border-color: #aaa;');
    txt = txt.concat('$card-bg: #eee;');
    txt = txt.concat('$card-cap-bg: #fff;');
    txt = txt.concat('$card-text-shadow: ' + colorPalette['Background Colour'] + ';');
    txt = txt.concat('$breadcrumb-padding-y: 0.1rem;');
    txt = txt.concat('$breadcrumb-margin-bottom: 0;');
    txt = txt.concat('$breadcrumb-bg: ' + colorPalette.Header + ';');
    txt = txt.concat('$breadcrumb-divider-color: #fff;');
    txt = txt.concat('$breadcrumb-divider: quote("»");');
    txt = txt.concat('$breadcrumb-border-radius: 0;');
    txt = txt.concat('$modal-inner-padding: 0.75rem;');
    txt = txt.concat('$modal-footer-margin-between: 0.1rem;');
    txt = txt.concat('$modal-header-padding-y: 0.4rem;');
    txt = txt.concat('$alert-margin-bottom: 0.5em;');
    txt = txt.concat('$alert-border-radius: 5px;');
    txt = txt.concat('$list-group-bg: inherit;');
    txt = txt.concat('$list-group-item-padding-x: 0;');
    txt = txt.concat('$list-group-item-padding-y: 0;');
    return txt;
}
