/**
 * functions used in setup script
 * 
 * @version $Id$
 */

// show this window in top frame
if (top != self) {
    window.top.location.href = location;
}

// default values for fields
var defaultValues = {};

// language strings
var PMA_messages = {};

/**
 * Returns field type
 *
 * @param Element field
 */
function getFieldType(field) {
	field = $(field);
    var tagName = field.attr('tagName');
	if (tagName == 'INPUT') {
        return field.attr('type');
    } else if (tagName == 'SELECT') {
        return 'select';
    } else if (tagName == 'TEXTAREA') {
        return 'text';
    }
    return '';
}

/**
 * Sets field value
 *
 * value must be of type:
 * o undefined (omitted) - restore default value (form default, not PMA default)
 * o String - if field_type is 'text'
 * o boolean - if field_type is 'checkbox'
 * o Array of values - if field_type is 'select'
 *
 * @param Element field
 * @param String  field_type  see getFieldType
 * @param mixed   value
 */
function setFieldValue(field, field_type, value) {
	field = $(field);
    switch (field_type) {
        case 'text':
            field.attr('value', (value != undefined ? value : field.attr('defaultValue')));
            break;
        case 'checkbox':
            field.attr('checked', (value != undefined ? value : field.attr('defaultChecked')));
            break;
        case 'select':
            var options = field.attr('options');
        	var i, imax = options.length;
            if (value == undefined) {
                for (i = 0; i < imax; i++) {
                	options[i].selected = options[i].defaultSelected;
                }
            } else {
                for (i = 0; i < imax; i++) {
                	options[i].selected = (value.indexOf(options[i].value) != -1);
                }
            }
            break;
    }
    markField(field);
}

/**
 * Gets field value
 *
 * Will return one of:
 * o String - if type is 'text'
 * o boolean - if type is 'checkbox'
 * o Array of values - if type is 'select'
 *
 * @param Element field
 * @param String  field_type  see getFieldType
 * @return mixed
 */
function getFieldValue(field, field_type) {
	field = $(field);
    switch (field_type) {
        case 'text':
            return field.attr('value');
        case 'checkbox':
            return field.attr('checked');
        case 'select':
        	var options = field.attr('options');
            var i, imax = options.length, items = [];
            for (i = 0; i < imax; i++) {
                if (options[i].selected) {
                    items.push(options[i].value);
                }
            }
            return items;
    }
}

/**
 * Returns values for all fields in fieldsets
 */
function getAllValues() {
    var elements = $('fieldset input, fieldset select, fieldset textarea');
    var values = {};
    var type, value;
    for (var i = 0; i < elements.length; i++) {
        type = getFieldType(elements[i]);
        value = getFieldValue(elements[i], type);
        if (typeof value != 'undefined') {
            // we only have single selects, fatten array
            if (type == 'select') {
                value = value[0];
            }
            values[elements[i].name] = value;
        }
    }
    return values;
}

/**
 * Checks whether field has its default value
 *
 * @param Element field
 * @param String  type
 * @return boolean
 */
function checkFieldDefault(field, type) {
    field = $(field);
    var field_id = field.attr('id');
    if (typeof defaultValues[field_id] == 'undefined') {
        return true;
    }
    var isDefault = true;
    var currentValue = getFieldValue(field, type);
    if (type != 'select') {
        isDefault = currentValue == defaultValues[field_id];
    } else {
        // compare arrays, will work for our representation of select values
        if (currentValue.length != defaultValues[field_id].length) {
            isDefault = false;
        }
        else {
            for (var i = 0; i < currentValue.length; i++) {
                if (currentValue[i] != defaultValues[field_id][i]) {
                    isDefault = false;
                    break;
                }
            }
        }
    }
    return isDefault;
}

/**
 * Returns element's id prefix
 * @param Element element
 */
function getIdPrefix(element) {
    return $(element).attr('id').replace(/[^-]+$/, '');
}

// ------------------------------------------------------------------
// Messages
//

// stores hidden message ids
var hiddenMessages = [];

$(function() {
    var hidden = hiddenMessages.length;
    for (var i = 0; i < hidden; i++) {
        $('#'+hiddenMessages[i]).css('display', 'none');
    }
    if (hidden > 0) {
        var link = $('#show_hidden_messages');
        link.click(function(e) {
            e.preventDefault();
            for (var i = 0; i < hidden; i++) {
                $('#'+hiddenMessages[i]).show(500);
            }
            $(this).remove();
        });
        link.html(link.html().replace('#MSG_COUNT', hidden));
        link.css('display', '');
    }
});

//
// END: Messages
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Form validation and field operations
//

// form validator assignments
var validate = {};

// form validator list
var validators = {
    // regexp: numeric value
    _regexp_numeric: new RegExp('^[0-9]*$'),
    /**
     * Validates positive number
     *
     * @param boolean isKeyUp
     */
    validate_positive_number: function (isKeyUp) {
        var result = this.value != '0' && validators._regexp_numeric.test(this.value);
        return result ? true : PMA_messages['error_nan_p'];
    },
    /**
     * Validates non-negative number
     *
     * @param boolean isKeyUp
     */
    validate_non_negative_number: function (isKeyUp) {
        var result = validators._regexp_numeric.test(this.value);
        return result ? true : PMA_messages['error_nan_nneg'];
    },
    /**
     * Validates port number
     *
     * @param boolean isKeyUp
     */
    validate_port_number: function(isKeyUp) {
        var result = validators._regexp_numeric.test(this.value) && this.value != '0';
        if (!result || this.value > 65536) {
            result = PMA_messages['error_incorrect_port'];
        }
        return result;
    },
    // field validators
    _field: {
        /**
         * hide_db field
         *
         * @param boolean isKeyUp
         */
        hide_db: function(isKeyUp) {
            if (!isKeyUp && this.value != '') {
                var data = {};
                data[this.id] = this.value;
                ajaxValidate(this, 'Servers/1/hide_db', data);
            }
            return true;
        },
		/**
         * TrustedProxies field
         *
         * @param boolean isKeyUp
         */
        TrustedProxies: function(isKeyUp) {
            if (!isKeyUp && this.value != '') {
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
         * @param boolean isKeyUp
         */
        Server: function(isKeyUp) {
            if (!isKeyUp) {
                ajaxValidate(this, 'Server', getAllValues());
            }
            return true;
        },
        /**
         * Validates Server_login_options fieldset
         *
         * @param boolean isKeyUp
         */
        Server_login_options: function(isKeyUp) {
            return validators._fieldset.Server.apply(this, [isKeyUp]);
        },
        /**
         * Validates Server_pmadb fieldset
         *
         * @param boolean isKeyUp
         */
        Server_pmadb: function(isKeyUp) {
            if (isKeyUp) {
                return true;
            }

            var prefix = getIdPrefix($(this).find('input'));
            var pmadb_active = $('#' + prefix + 'pmadb').val() != '';
            if (pmadb_active) {
                ajaxValidate(this, 'Server_pmadb', getAllValues());
            }

            return true;
        }
    }
};

/**
 * Calls server-side validation procedures
 *
 * @param Element parent  input field in <fieldset> or <fieldset>
 * @param String id       validator id
 * @param Object values   values hash (element_id: value)
 */
function ajaxValidate(parent, id, values) {
	parent = $(parent);
    // ensure that parent is a fieldset
    if (parent.attr('tagName') != 'FIELDSET') {
        parent = parent.closest('fieldset');
        if (parent.length == 0) {
            return false;
        }
    }
 
    if (parent.data('ajax') != null) {
    	parent.data('ajax').abort();
    }

    parent.data('ajax', $.ajax({
        url: 'validate.php',
        cache: false,
        type: 'POST',
        data: {
            token: parent.closest('form').find('input[name=token]').val(),
            id: id,
            values: $.toJSON(values)
        },
        success: function(response) {
            if (response == null) {
                return;
            }

            var error = {};
            if (typeof response != 'object') {
                error[parent.id] = [response];
            } else if (typeof response['error'] != 'undefined') {
                error[parent.id] = [response['error']];
            } else {
                for (key in response) {
                	var value = response[key];
                    error[key] = jQuery.isArray(value) ? value : [value];
                }
            }
            displayErrors(error);
        },
        complete: function() {
            parent.removeData('ajax');
        }
    }));

    return true;
}

/**
 * Registers validator for given field
 *
 * @param String  id       field id
 * @param String  type     validator (key in validators object)
 * @param boolean onKeyUp  whether fire on key up
 * @param mixed   params   validation function parameters
 */
function validateField(id, type, onKeyUp, params) {
    if (typeof validators[type] == 'undefined') {
        return;
    }
    if (typeof validate[id] == 'undefined') {
        validate[id] = [];
    }
    validate[id].push([type, params, onKeyUp]);
}

/**
 * Returns valdiation functions associated with form field
 *
 * @param  String  field_id     form field id
 * @param  boolean onKeyUpOnly  see validateField
 * @return Array  array of [function, paramseters to be passed to function]
 */
function getFieldValidators(field_id, onKeyUpOnly) {
    // look for field bound validator
    var name = field_id.match(/[^-]+$/)[0];
    if (typeof validators._field[name] != 'undefined') {
        return [[validators._field[name], null]];
    }

    // look for registered validators
    var functions = [];
    if (typeof validate[field_id] != 'undefined') {
        // validate[field_id]: array of [type, params, onKeyUp]
        for (var i = 0, imax = validate[field_id].length; i < imax; i++) {
            if (onKeyUpOnly && !validate[field_id][i][2]) {
                continue;
            }
            functions.push([validators[validate[field_id][i][0]], validate[field_id][i][1]]);
        }
    }

    return functions;
}

/**
 * Displays errors for given form fields
 *
 * WARNING: created DOM elements must be identical with the ones made by
 * display_input() in FormDisplay.tpl.php!
 *
 * @param Object  error list (key: field id, value: error array)
 */
function displayErrors(error_list) {
    for (field_id in error_list) {
        var errors = error_list[field_id];
        var field = $('#'+field_id);
        var isFieldset = field.attr('tagName') == 'FIELDSET';
        var errorCnt = isFieldset
            ? field.find('dl.errors')
            : field.siblings('.inline_errors');

        // remove empty errors (used to clear error list)
        errors = $.grep(errors, function(item) {
            return item != '';
        });

        if (errors.length) {
            // if error container doesn't exist, create it
            if (errorCnt.length == 0) {
                if (isFieldset) {
                    errorCnt = $('<dl class="errors" />');
                    field.find('table').before(errorCnt);
                } else {
                    errorCnt = $('<dl class="inline_errors" />');
                    field.closest('td').append(errorCnt);
                }
            }

            var html = '';
            for (var i = 0, imax = errors.length; i < imax; i++) {
                html += '<dd>' + errors[i] + '</dd>';
            }
            errorCnt.html(html);
        } else if (errorCnt !== null) {
            // remove useless error container
            errorCnt.remove();
        }
    }
}

/**
 * Validates fieldset and puts errors in 'errors' object
 *
 * @param Element field
 * @param boolean isKeyUp
 * @param Object  errors
 */
function validate_fieldset(fieldset, isKeyUp, errors) {
	fieldset = $(fieldset);
    if (fieldset.length && typeof validators._fieldset[fieldset.attr('id')] != 'undefined') {
        var fieldset_errors = validators._fieldset[fieldset.attr('id')].apply(fieldset[0], [isKeyUp]);
        for (field_id in fieldset_errors) {
            if (typeof errors[field_id] == 'undefined') {
                errors[field_id] = [];
            }
            if (typeof fieldset_errors[field_id] == 'string') {
                fieldset_errors[field_id] = [fieldset_errors[field_id]];
            }
            $.merge(errors[field_id], fieldset_errors[field_id]);
        }
    }
}

/**
 * Validates form field and puts errors in 'errors' object
 *
 * @param Element field
 * @param boolean isKeyUp
 * @param Object  errors
 */
function validate_field(field, isKeyUp, errors) {
	field = $(field);
	var field_id = field.attr('id');
    errors[field_id] = [];
    var functions = getFieldValidators(field_id, isKeyUp);
    for (var i = 0; i < functions.length; i++) {
        var result = functions[i][0].apply(field[0], [isKeyUp, functions[i][1]]);
        if (result !== true) {
            if (typeof result == 'string') {
            	result = [result];
            }
            $.merge(errors[field_id], result);
        }
    }
}

/**
 * Validates form field and parent fieldset
 *
 * @param Element field
 * @param boolean isKeyUp
 */
function validate_field_and_fieldset(field, isKeyUp) {
	field = $(field);
    var errors = {};
    validate_field(field, isKeyUp, errors);
    validate_fieldset(field.closest('fieldset'), isKeyUp, errors);
    displayErrors(errors);
}

/**
 * Marks field depending on its value (system default or custom)
 *
 * @param Element field
 */
function markField(field) {
	field = $(field);
    var type = getFieldType(field);
    var isDefault = checkFieldDefault(field, type);

    // checkboxes uses parent <span> for marking
    var fieldMarker = (type == 'checkbox') ? field.parent() : field;
    setRestoreDefaultBtn(field, !isDefault);
    fieldMarker[isDefault ? 'removeClass' : 'addClass']('custom');
}

/**
 * Enables or disables the "restore default value" button
 *
 * @param Element field
 * @param bool    display
 */
function setRestoreDefaultBtn(field, display) {
    var el = $(field).closest('td').find('.restore-default');
    el.css('display', (el.css('display') ? '' : 'none'));
}

$(function() {
    // register validators and mark custom values
	var elements = $('input[id], select[id], textarea[id]');
    $('input[id], select[id], textarea[id]').each(function(){
        markField(this);
        var el = $(this);
        el.bind('change', function() {
            validate_field_and_fieldset(this, false);
            markField(this);
        });
        var tagName = el.attr('tagName');
        // text fields can be validated after each change
        if (tagName == 'INPUT' && el.attr('type') == 'text') {
        	el.keyup(function() {
                validate_field_and_fieldset(el, true);
                markField(el);
            });
        }
        // disable textarea spellcheck
        if (tagName == 'TEXTAREA') {
        	el.attr('spellcheck', false);
        }    	
    });

	// check whether we've refreshed a page and browser remembered modified
	// form values
	var check_page_refresh = $('#check_page_refresh');
	if (check_page_refresh.length == 0 || check_page_refresh.val() == '1') {
		// run all field validators
		var errors = {};
		for (var i = 0; i < elements.length; i++) {
			validate_field(elements[i], false, errors);
		}
		// run all fieldset validators
		$('fieldset').each(function(){
			validate_fieldset(this, false, errors);
		});
		
		displayErrors(errors);
	} else if (check_page_refresh) {
		check_page_refresh.val('1');
	}
});

//
// END: Form validation and field operations
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Tabbed forms
//

/**
 * Sets active tab
 *
 * @param Element tab_link
 */
function setTab(tab_link) {
    var tabs_menu = $(tab_link).closest('.tabs');

    var links = tabs_menu.find('a');
    var contents, link;
    for (var i = 0, imax = links.length; i < imax; i++) {
    	link = $(links[i]);
        contents = $(link.attr('href'));
        if (links[i] == tab_link) {
        	link.addClass('active');
            contents.css('display', 'block');
        } else {
            link.removeClass('active');
            contents.css('display', 'none');
        }
    }
    location.hash = 'tab_' + $(tab_link).attr('href').substr(1);
}

$(function() {
    var tabs = $('.tabs');
    var url_tab = location.hash.match(/^#tab_.+/)
        ? $('a[href$="' + location.hash.substr(5) + '"]') : null;
    if (url_tab) {
        url_tab = url_tab[0];
    }
    // add tabs events and activate one tab (the first one or indicated by location hash)
    for (var i = 0, imax = tabs.length; i < imax; i++) {
        var links = $(tabs[i]).find('a');
        var selected_tab = links[0];
        for (var j = 0, jmax = links.length; j < jmax; j++) {
            $(links[j]).click(function(e) {
                e.preventDefault();
                setTab(this);
            });
            if (links[j] == url_tab) {
                selected_tab = links[j];
            }
        }
        setTab(selected_tab);
    }
    // tab links handling, check each 200ms
    // (works with history in FF, further browser support here would be an overkill)
    var prev_hash = location.hash;
    setInterval(function() {
        if (location.hash != prev_hash) {
            prev_hash = location.hash;
            var url_tab = location.hash.match(/^#tab_.+/)
                ? $('a[href$="' + location.hash.substr(5) + '"]') : null;
            if (url_tab) {
                setTab(url_tab[0]);
            }
        }
    }, 200);
});

//
// END: Tabbed forms
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Form reset buttons
//

$(function() {
    $('input[type=button]').click(function(e) {
        var fields = $(this).closest('fieldset').find('input, select, textarea');
        for (var i = 0, imax = fields.length; i < imax; i++) {
            setFieldValue(fields[i], getFieldType(fields[i]));
        }
    });
});

//
// END: Form reset buttons
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// "Restore default" and "set value" buttons
//

/**
 * Restores field's default value
 *
 * @param String field_id
 */
function restoreField(field_id) {
    var field = $('#'+field_id);
    if (field.length == 0 || defaultValues[field_id] == undefined) {
        return;
    }
    setFieldValue(field, getFieldType(field), defaultValues[field_id]);
}

$(function() {
    $('.restore-default, .set-value').each(function() {
        var link = $(this);
        link.css('opacity', 0.25);
        if (!link.hasClass('restore-default')) {
            // restore-default is handled by markField
        	link.css('display', '');
        }
        link.bind({
            mouseenter: function() {$(this).css('opacity', 1);},
            mouseleave: function() {$(this).css('opacity', 0.25);},
            click: function(e) {
                e.preventDefault();
                var href = $(this).attr('href').substr(1);
                var field_id;
                if ($(this).hasClass('restore-default')) {
                    field_id = href;
                    restoreField(field_id);
                } else {
                    field_id = href.match(/^[^=]+/)[0];
                    var value = href.match(/=(.+)$/)[1];
                    setFieldValue($('#'+field_id), 'text', value);
                }
                $('#'+field_id).trigger('change');
            }
        });
    });
});

//
// END: "Restore default" and "set value" buttons
// ------------------------------------------------------------------
