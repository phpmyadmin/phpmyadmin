/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in Setup configuration forms
 */

// show this window in top frame
if (top != self) {
    window.top.location.href = location;
}

// ------------------------------------------------------------------
// Messages
//

// stores hidden message ids
var hiddenMessages = [];

$(function () {
    var hidden = hiddenMessages.length;
    for (var i = 0; i < hidden; i++) {
        $('#' + hiddenMessages[i]).css('display', 'none');
    }
    if (hidden > 0) {
        var link = $('#show_hidden_messages');
        link.click(function (e) {
            e.preventDefault();
            for (var i = 0; i < hidden; i++) {
                $('#' + hiddenMessages[i]).show(500);
            }
            $(this).remove();
        });
        link.html(link.html().replace('#MSG_COUNT', hidden));
        link.css('display', '');
    }
});

//set document width
$(document).ready(function(){
    width = 0;
    $('ul.tabs li').each(function(){
        width += $(this).width() + 10;
    });
    var contentWidth = width;
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
 * @param {String}  id      validator id
 * @param {Object}  values  values hash {element1_id: value, ...}
 */
function ajaxValidate(parent, id, values)
{
    parent = $(parent);
    // ensure that parent is a fieldset
    if (parent.attr('tagName') != 'FIELDSET') {
        parent = parent.closest('fieldset');
        if (parent.length === 0) {
            return false;
        }
    }

    if (parent.data('ajax') !== null) {
        parent.data('ajax').abort();
    }

    parent.data('ajax', $.ajax({
        url: 'validate.php',
        cache: false,
        type: 'POST',
        data: {
            token: parent.closest('form').find('input[name=token]').val(),
            id: id,
            values: JSON.stringify(values)
        },
        success: function (response) {
            if (response === null) {
                return;
            }

            var error = {};
            if (typeof response != 'object') {
                error[parent.id] = [response];
            } else if (typeof response.error != 'undefined') {
                error[parent.id] = [response.error];
            } else {
                for (var key in response) {
                    var value = response[key];
                    error[key] = jQuery.isArray(value) ? value : [value];
                }
            }
            displayErrors(error);
        },
        complete: function () {
            parent.removeData('ajax');
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

$.extend(true, validators, {
    // field validators
    _field: {
        /**
         * hide_db field
         *
         * @param {boolean} isKeyUp
         */
        hide_db: function (isKeyUp) {
            if (!isKeyUp && this.value !== '') {
                var data = {};
                data[this.id] = this.value;
                ajaxValidate(this, 'Servers/1/hide_db', data);
            }
            return true;
        },
        /**
         * TrustedProxies field
         *
         * @param {boolean} isKeyUp
         */
        TrustedProxies: function (isKeyUp) {
            if (!isKeyUp && this.value !== '') {
                var data = {};
                data[this.id] = this.value;
                ajaxValidate(this, 'TrustedProxies', data);
            }
            return true;
        }
    },
    // fieldset validators
    _fieldset: {
        /**
         * Validates Server fieldset
         *
         * @param {boolean} isKeyUp
         */
        Server: function (isKeyUp) {
            if (!isKeyUp) {
                ajaxValidate(this, 'Server', getAllValues());
            }
            return true;
        },
        /**
         * Validates Server_login_options fieldset
         *
         * @param {boolean} isKeyUp
         */
        Server_login_options: function (isKeyUp) {
            return validators._fieldset.Server.apply(this, [isKeyUp]);
        },
        /**
         * Validates Server_pmadb fieldset
         *
         * @param {boolean} isKeyUp
         */
        Server_pmadb: function (isKeyUp) {
            if (isKeyUp) {
                return true;
            }

            var prefix = getIdPrefix($(this).find('input'));
            if ($('#' + prefix + 'pmadb').val() !== '') {
                ajaxValidate(this, 'Server_pmadb', getAllValues());
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
    $('.userprefs-allow').click(function (e) {
        if (this != e.target) {
            return;
        }
        var el = $(this).find('input');
        if (el.prop('disabled')) {
            return;
        }
        el.prop('checked', !el.prop('checked'));
    });
});

//
// END: User preferences allow/disallow UI
// ------------------------------------------------------------------
