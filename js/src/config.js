/* vim: set expandtab sw=4 ts=4 sts=4: */
import * as Config from './functions/config';
import { defaultValues } from './variables/get_config';

/**
 * @package PhpMyAdmin
 *
 * Config
 */

/**
 * Unbind all event handlers before tearing down a page
 */
function teardownConfig () {
    $('.optbox input[id], .optbox select[id], .optbox textarea[id]').off('change').off('keyup');
    $('.optbox input[type=button][name=submit_reset]').off('click');
    $('div.tabs_contents').off();
    $('#import_local_storage, #export_local_storage').off('click');
    $('form.prefs-form').off('change').off('submit');
    $(document).off('click', 'div.click-hide-message');
    $('#prefs_autoload').find('a').off('click');
}

function onloadConfigPrefsTab () {
    var $topmenu_upt = $('#topmenu2.user_prefs_tabs');
    $topmenu_upt.find('li.active a').attr('rel', 'samepage');
    $topmenu_upt.find('li:not(.active) a').attr('rel', 'newpage');
}

// ------------------------------------------------------------------
// Form validation and field operations
//

export function onload2 () {
    Config.setupValidation();
}

//
// END: Form validation and field operations
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Tabbed forms
//

export function onload3 () {
    Config.setupConfigTabs();
    Config.adjustPrefsNotification();

    // tab links handling, check each 200ms
    // (works with history in FF, further browser support here would be an overkill)
    var prev_hash;
    var tab_check_fnc = function () {
        if (location.hash !== prev_hash) {
            prev_hash = location.hash;
            if (prev_hash.match(/^#tab_[a-zA-Z0-9_]+$/)) {
                // session ID is sometimes appended here
                var hash = prev_hash.substr(5).split('&')[0];
                if ($('#' + hash).length) {
                    Config.setTab(hash);
                }
            }
        }
    };
    tab_check_fnc();
    setInterval(tab_check_fnc, 200);
}

//
// END: Tabbed forms
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Form reset buttons
//

function onloadConfigResetDefault () {
    $('.optbox input[type=button][name=submit_reset]').on('click', function () {
        var fields = $(this).closest('fieldset').find('input, select, textarea');
        for (var i = 0, imax = fields.length; i < imax; i++) {
            Config.setFieldValue(fields[i], Config.getFieldType(fields[i]), defaultValues[fields[i].id]);
        }
    });
}

//
// END: Form reset buttons
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// "Restore default" and "set value" buttons
//

export function onload5 () {
    Config.setupRestoreField();
}

//
// END: "Restore default" and "set value" buttons
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// User preferences import/export
//

export function onload6 () {
    Config.offerPrefsAutoimport();
    var $radios = $('#import_local_storage, #export_local_storage');
    if (!$radios.length) {
        return;
    }

    // enable JavaScript dependent fields
    $radios
        .prop('disabled', false)
        .add('#export_text_file, #import_text_file')
        .on('click', function () {
            var enableId = $(this).attr('id');
            var disableId;
            if (enableId.match(/local_storage$/)) {
                disableId = enableId.replace(/local_storage$/, 'text_file');
            } else {
                disableId = enableId.replace(/text_file$/, 'local_storage');
            }
            $('#opts_' + disableId).addClass('disabled').find('input').prop('disabled', true);
            $('#opts_' + enableId).removeClass('disabled').find('input').prop('disabled', false);
        });

    // detect localStorage state
    var ls_supported = Config.isStorageSupported('localStorage', true);
    var ls_exists = ls_supported ? (window.localStorage.config || false) : false;
    $('div.localStorage-' + (ls_supported ? 'un' : '') + 'supported').hide();
    $('div.localStorage-' + (ls_exists ? 'empty' : 'exists')).hide();
    if (ls_exists) {
        Config.updatePrefsDate();
    }
    $('form.prefs-form').on('change', function () {
        var $form = $(this);
        var disabled = false;
        if (!lsSupported) {
            disabled = $form.find('input[type=radio][value$=local_storage]').prop('checked');
        } else if (!lsExists && $form.attr('name') === 'prefs_import' &&
            $('#import_local_storage')[0].checked
        ) {
            disabled = true;
        }
        $form.find('input[type=submit]').prop('disabled', disabled);
    }).submit(function (e) {
        var $form = $(this);
        if ($form.attr('name') === 'prefs_export' && $('#export_local_storage')[0].checked) {
            e.preventDefault();
            // use AJAX to read JSON settings and save them
            Config.savePrefsToLocalStorage($form);
        } else if ($form.attr('name') === 'prefs_import' && $('#import_local_storage')[0].checked) {
            // set 'json' input and submit form
            $form.find('input[name=json]').val(window.localStorage.config);
        }
    });

    $(document).on('click', 'div.click-hide-message', function () {
        $(this)
            .hide()
            .parent('.group')
            .css('height', '')
            .next('form')
            .show();
    });
}

//
// END: User preferences import/export
// ------------------------------------------------------------------

/**
 * Module export
 */
export {
    teardownConfig,
    onloadConfigPrefsTab,
    onloadConfigResetDefault,
    onloadConfigRestore,
    onloadConfigTabs,
    onloadConfigValidations,
    onloadPreferenceExport
};
