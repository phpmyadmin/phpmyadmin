/* vim: set expandtab sw=4 ts=4 sts=4: */

import { isStorageSupported, updatePrefsDate, offerPrefsAutoimport,
    savePrefsToLocalStorage, setupRestoreField, getFieldType,
    setFieldValue, setupConfigTabs, adjustPrefsNotification, setTab,
    setupValidation } from './functions/config';


import { defaultValues } from './variables/get_config';

/**
 * Functions used in configuration forms and on user preferences pages
 */

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardown1 () {
    $('.optbox input[id], .optbox select[id], .optbox textarea[id]').off('change').off('keyup');
    $('.optbox input[type=button][name=submit_reset]').off('click');
    $('div.tabs_contents').off();
    $('#import_local_storage, #export_local_storage').off('click');
    $('form.prefs-form').off('change').off('submit');
    $(document).off('click', 'div.click-hide-message');
    $('#prefs_autoload').find('a').off('click');
}

export function onload1 () {
    var $topmenu_upt = $('#topmenu2.user_prefs_tabs');
    $topmenu_upt.find('li.active a').attr('rel', 'samepage');
    $topmenu_upt.find('li:not(.active) a').attr('rel', 'newpage');
}

// ------------------------------------------------------------------
// Form validation and field operations
//

export function onload2 () {
    setupValidation();
}

//
// END: Form validation and field operations
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Tabbed forms
//

export function onload3 () {
    setupConfigTabs();
    adjustPrefsNotification();

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
                    setTab(hash);
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

export function onload4 () {
    $('.optbox input[type=button][name=submit_reset]').on('click', function () {
        var fields = $(this).closest('fieldset').find('input, select, textarea');
        for (var i = 0, imax = fields.length; i < imax; i++) {
            setFieldValue(fields[i], getFieldType(fields[i]), defaultValues[fields[i].id]);
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
    setupRestoreField();
}

//
// END: "Restore default" and "set value" buttons
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// User preferences import/export
//

export function onload6 () {
    offerPrefsAutoimport();
    var $radios = $('#import_local_storage, #export_local_storage');
    if (!$radios.length) {
        return;
    }

    // enable JavaScript dependent fields
    $radios
        .prop('disabled', false)
        .add('#export_text_file, #import_text_file')
        .on('click', function () {
            var enable_id = $(this).attr('id');
            var disable_id;
            if (enable_id.match(/local_storage$/)) {
                disable_id = enable_id.replace(/local_storage$/, 'text_file');
            } else {
                disable_id = enable_id.replace(/text_file$/, 'local_storage');
            }
            $('#opts_' + disable_id).addClass('disabled').find('input').prop('disabled', true);
            $('#opts_' + enable_id).removeClass('disabled').find('input').prop('disabled', false);
        });

    // detect localStorage state
    var ls_supported = isStorageSupported('localStorage', true);
    var ls_exists = ls_supported ? (window.localStorage.config || false) : false;
    $('div.localStorage-' + (ls_supported ? 'un' : '') + 'supported').hide();
    $('div.localStorage-' + (ls_exists ? 'empty' : 'exists')).hide();
    if (ls_exists) {
        updatePrefsDate();
    }
    $('form.prefs-form').on('change', function () {
        var $form = $(this);
        var disabled = false;
        if (!ls_supported) {
            disabled = $form.find('input[type=radio][value$=local_storage]').prop('checked');
        } else if (!ls_exists && $form.attr('name') === 'prefs_import' &&
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
            savePrefsToLocalStorage($form);
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
