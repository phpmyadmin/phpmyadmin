import $ from 'jquery';
import { Config } from '../modules/config.ts';

/**
 * Functions used in Setup configuration forms
 */

// show this window in top frame
if (top !== self) {
    // @ts-ignore
    window.top.location.href = location;
}

// ------------------------------------------------------------------
// Messages
//

$(function () {
    if (window.location.protocol === 'https:') {
        $('#no_https').remove();
    } else {
        $('#no_https a').on('click', function () {
            const oldLocation = window.location;
            window.location.href = 'https:' + oldLocation.href.substring(oldLocation.protocol.length);

            return false;
        });
    }

    const hiddenMessages = $('.hiddenmessage');

    if (hiddenMessages.length > 0) {
        hiddenMessages.hide();
        const link = $('#show_hidden_messages');
        link.on('click', function (e) {
            e.preventDefault();
            hiddenMessages.show();
            $(this).remove();
        });

        link.html(link.html().replace('#MSG_COUNT', hiddenMessages.length.toString()));
        link.show();
    }
});

// set document width
$(function () {
    let width = 0;
    $('ul.tabs li').each(function () {
        width += $(this).width() + 10;
    });

    const contentWidth = width;
    width += 250;
    $('body').css('min-width', width);
    $('.tabs_contents').css('min-width', contentWidth);
});

//
// END: Messages
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Form validation and field operations
//

/**
 * Calls server-side validation procedures
 *
 * @param {Element} parent  input field in <fieldset> or <fieldset>
 * @param {string}  id      validator id
 * @param {object}  values  values hash {element1_id: value, ...}
 *
 * @return {boolean|void}
 */
function ajaxValidate (parent, id, values) {
    let $parent = $(parent);
    // ensure that parent is a fieldset
    if ($parent.attr('tagName') !== 'FIELDSET') {
        $parent = $parent.closest('fieldset');
        if ($parent.length === 0) {
            return false;
        }
    }

    if ($parent.data('ajax') !== null) {
        $parent.data('ajax').abort();
    }

    $parent.data('ajax', $.ajax({
        url: '../setup/index.php?route=/setup/validate',
        cache: false,
        type: 'POST',
        data: {
            token: $parent.closest('form').find('input[name=token]').val(),
            id: id,
            values: JSON.stringify(values)
        },
        success: function (response) {
            if (response === null) {
                return;
            }

            const error = {};
            if (typeof response !== 'object') {
                // @ts-ignore
                error[$parent.id] = [response];
            } else if (typeof response.error !== 'undefined') {
                // @ts-ignore
                error[$parent.id] = [response.error];
            } else {
                for (let key in response) {
                    const value = response[key];
                    error[key] = Array.isArray(value) ? value : [value];
                }
            }

            Config.displayErrors(error);
        },
        complete: function () {
            $parent.removeData('ajax');
        }
    }));

    return true;
}

/**
 * Automatic form submission on change.
 */
$(document).on('change', '.autosubmit', function (e) {
    e.target.form.submit();
});

$.extend(true, window.validators, {
    // field validators
    field: {
        /**
         * hide_db field
         *
         * @param {boolean} isKeyUp
         *
         * @return {true}
         */
        hide_db: function (isKeyUp) { // eslint-disable-line camelcase
            if (! isKeyUp && this.value !== '') {
                const data = {};
                data[this.id] = this.value;
                ajaxValidate(this, 'Servers/1/hide_db', data);
            }

            return true;
        },
        /**
         * TrustedProxies field
         *
         * @param {boolean} isKeyUp
         *
         * @return {true}
         */
        TrustedProxies: function (isKeyUp) {
            if (! isKeyUp && this.value !== '') {
                const data = {};
                data[this.id] = this.value;
                ajaxValidate(this, 'TrustedProxies', data);
            }

            return true;
        }
    },
    // fieldset validators
    fieldset: {
        /**
         * Validates Server fieldset
         *
         * @param {boolean} isKeyUp
         *
         * @return {true}
         */
        Server: function (isKeyUp) {
            if (! isKeyUp) {
                ajaxValidate(this, 'Server', Config.getAllValues());
            }

            return true;
        },
        /**
         * Validates Server_login_options fieldset
         *
         * @param {boolean} isKeyUp
         *
         * @return {true}
         */
        Server_login_options: function (isKeyUp) { // eslint-disable-line camelcase
            // @ts-ignore
            return window.validators.fieldset.Server.apply(this, [isKeyUp]);
        },
        /**
         * Validates Server_pmadb fieldset
         *
         * @param {boolean} isKeyUp
         *
         * @return {true}
         */
        Server_pmadb: function (isKeyUp) { // eslint-disable-line camelcase
            if (isKeyUp) {
                return true;
            }

            const prefix = Config.getIdPrefix($(this).find('input'));
            if ($('#' + prefix + 'pmadb').val() !== '') {
                ajaxValidate(this, 'Server_pmadb', Config.getAllValues());
            }

            return true;
        }
    }
});

//
// END: Form validation and field operations
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// User preferences allow/disallow UI
//

$(function () {
    $('.userprefs-allow').on('click', function (e) {
        if (this !== e.target) {
            return;
        }

        const el = $(this).find('input');
        if (el.prop('disabled')) {
            return;
        }

        el.prop('checked', ! el.prop('checked'));
    });
});

//
// END: User preferences allow/disallow UI
// ------------------------------------------------------------------

$(function () {
    $('.delete-server').on('click', function (e) {
        e.preventDefault();
        const $this = $(this);
        $.post($this.attr('href'), $this.attr('data-post'), function () {
            window.location.replace('../setup/index.php?route=/setup');
        });
    });
});
