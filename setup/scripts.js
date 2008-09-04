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
    if (field.tagName == 'INPUT') {
        return field.getProperty('type');
    } else if (field.tagName == 'SELECT') {
        return 'select';
    } else if (field.tagName == 'TEXTAREA') {
        return 'text';
    }
    return '';
}

/**
 * Sets field value
 *
 * value must be of type:
 * o undefined (omitted) - restore default value (form default, not PMA default)
 * o String - if type is 'text'
 * o boolean - if type is 'checkbox'
 * o Array of values - if type is 'select'
 *
 * @param Element field
 * @param String  field_type  see getFieldType
 * @param mixed   value
 */
function setFieldValue(field, field_type, value) {
    switch (field_type) {
        case 'text':
            field.value = $defined(value) ? value : field.defaultValue;
            break;
        case 'checkbox':
            field.checked = $defined(value) ? value : field.defaultChecked;
            break;
        case 'select':
            var i, imax = field.options.length;
            if (!$defined(value)) {
                for (i = 0; i < imax; i++) {
                    field.options[i].selected = field.options[i].defaultSelected;
                }
            } else {
                for (i = 0; i < imax; i++) {
                    field.options[i].selected = (value.indexOf(field.options[i].value) != -1);
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
    switch (field_type) {
        case 'text':
            return field.value;
        case 'checkbox':
            return field.checked;
        case 'select':
            var i, imax = field.options.length, items = [];
            for (i = 0; i < imax; i++) {
                if (field.options[i].selected) {
                    items.push(field.options[i].value);
                }
            }
            return items;
    }
}

/**
 * Returns values for all fields in fieldsets
 */
function getAllValues() {
    var elements = $$('fieldset input, fieldset select, fieldset textarea');
    var values = {}
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
    if (!$defined(defaultValues[field.id])) {
        return true;
    }
    var isDefault = true
    var currentValue = getFieldValue(field, type);
    if (type != 'select') {
        isDefault = currentValue == defaultValues[field.id];
    } else {
        // compare arrays, will work for our representation of select values
        if (currentValue.length != defaultValues[field.id].length) {
            isDefault = false;
        }
        else {
            for (var i = 0; i < currentValue.length; i++) {
                if (currentValue[i] != defaultValues[field.id][i]) {
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
    return element.id.replace(/[^-]+$/, '');
}

// ------------------------------------------------------------------
// Messages
//

// stores hidden message ids
var hiddenMessages = [];

window.addEvent('domready', function() {
    var hidden = hiddenMessages.length;
    for (var i = 0; i < hidden; i++) {
        $(hiddenMessages[i]).style.display = 'none';
    }
    if (hidden > 0) {
        var link = $('show_hidden_messages');
        link.addEvent('click', function(e) {
            e.stop();
            for (var i = 0; i < hidden; i++) {
                $(hiddenMessages[i]).style.display = '';
            }
            this.dispose();
        });
        link.set('html', link.get('html').replace('#MSG_COUNT', hidden));
        link.style.display = '';
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
    /**
     * Validates positive number
     *
     * @param boolean isKeyUp
     */
    validate_positive_number: function (isKeyUp) {
        var result = this.value.test('^[0-9]*$') && this.value != '0';
        return result ? true : PMA_messages['error_nan_p'];
    },
    /**
     * Validates non-negative number
     *
     * @param boolean isKeyUp
     */
    validate_non_negative_number: function (isKeyUp) {
        var result = this.value.test('^[0-9]*$');
        return result ? true : PMA_messages['error_nan_nneg'];
    },
    /**
     * Validates port number
     *
     * @param boolean isKeyUp
     */
    validate_port_number: function(isKeyUp) {
        var result = this.value.test('^[0-9]*$') && this.value != '0';
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
        },
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
            return validators._fieldset.Server.bind(this)(isKeyUp);
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

            var prefix = getIdPrefix(this.getElement('input'));
            var pmadb_active = $(prefix + 'pmadb').value != '';
            if (pmadb_active) {
                ajaxValidate(this, 'Server_pmadb', getAllValues());
            }

            return true;
        }
    }
}

/**
 * Calls server-side validation procedures
 *
 * @param Element parent  input field in <fieldset> or <fieldset>
 * @param String id       validator id
 * @param Object values   values hash (element_id: value)
 */
function ajaxValidate(parent, id, values) {
    // ensure that parent is a fieldset
    if (parent.tagName != 'FIELDSET') {
        parent = parent.getParent('fieldset');
        if (!parent) {
            return false;
        }
    }
    // ensure that we have a Request object
    if (typeof parent.request == 'undefined') {
        parent.validate = {
            request: new Request.JSON({
                url: 'validate.php',
                autoCancel: true,
                onSuccess: function(response) {
                    if (response == null) {
                        return;
                    }
                    var error = {};
                    if ($type(response) != 'object') {
                        error[parent.id] = [response];
                    } else if (typeof response['error'] != 'undefined') {
                        error[parent.id] = [response['error']];
                    } else {
                        $each(response, function(value, key) {
                            error[key] = $type(value) == 'array' ? value : [value];
                        });
                    }
                    displayErrors(error);
                }}),
            token: parent.getParent('form').token.value
        };
    }

    parent.validate.request.send({
        data: {
            token: parent.validate.token,
            id: id,
            values: JSON.encode(values)}
    });

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
function displayErrors(errors) {
    $each(errors, function(errors, field_id) {
        var field = $(field_id);
        var isFieldset = field.tagName == 'FIELDSET';
        var errorCnt = isFieldset
            ? field.getElement('dl.errors')
            : field.getNext('.inline_errors');

        // remove empty errors (used to clear error list)
        errors = errors.filter(function(item) {
            return item != '';
        });

        if (errors.length) {
            // if error container doesn't exist, create it
            if (errorCnt === null) {
                if (isFieldset) {
                    errorCnt = new Element('dl', {
                        'class': 'errors'
                    });
                    errorCnt.inject(field.getElement('table'), 'before');
                } else {
                    errorCnt = new Element('dl', {
                        'class': 'inline_errors'
                    });
                    errorCnt.inject(field.getParent('td'), 'bottom');
                }
            }

            var html = '';
            for (var i = 0, imax = errors.length; i < imax; i++) {
                html += '<dd>' + errors[i] + '</dd>';
            }
            errorCnt.set('html', html);
        } else if (errorCnt !== null) {
            // remove useless error container
            errorCnt.dispose();
        }
    });
}

/**
 * Validates fieldset and puts errors in 'errors' object
 *
 * @param Element field
 * @param boolean isKeyUp
 * @param Object  errors
 */
function validate_fieldset(fieldset, isKeyUp, errors) {
    if (fieldset && typeof validators._fieldset[fieldset.id] != 'undefined') {
        var fieldset_errors = validators._fieldset[fieldset.id].bind(fieldset)(isKeyUp);
        $each(fieldset_errors, function(field_errors, field_id) {
            if (typeof errors[field_id] == 'undefined') {
                errors[field_id] = [];
            }
            errors[field_id][$type(field_errors) == 'array' ? 'extend' : 'push'](field_errors);
        });
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
    errors[field.id] = [];
    var functions = getFieldValidators(field.id, isKeyUp);
    for (var i = 0; i < functions.length; i++) {
        var result = functions[i][0].bind(field)(isKeyUp, functions[i][1]);
        if (result !== true) {
            errors[field.id][$type(result) == 'array' ? 'extend' : 'push'](result);
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
    var errors = {};
    validate_field(field, isKeyUp, errors);
    validate_fieldset(field.getParent('fieldset'), isKeyUp, errors);
    displayErrors(errors);
}

/**
 * Marks field depending on its value (system default or custom)
 *
 * @param Element field
 */
function markField(field) {
    var type = getFieldType(field);
    var isDefault = checkFieldDefault(field, type);

    // checkboxes uses parent <span> for marking
    var fieldMarker = (type == 'checkbox') ? field.getParent() : field;
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
    var td = field.getParent('td');
    if (!td) return;
    var el = td.getElement('.restore-default');
    if (!el) return;
    el.style.display = (display ? '' : 'none');
}

window.addEvent('domready', function() {
    var elements = $$('input[id], select[id], textarea[id]');
    var elements_count = elements.length;

    // register validators and mark custom values
    for (var i = 0; i < elements_count; i++) {
        var el = elements[i];
        markField(el);
        el.addEvent('change', function(e) {
            validate_field_and_fieldset(this, false);
            markField(this);
        });
        // text fields can be validated after each change
        if (el.tagName == 'INPUT' && el.type == 'text') {
            el.addEvent('keyup', function(e) {
                validate_field_and_fieldset(this, true);
                markField(el);
            });
        }
        // disable textarea spellcheck
        if (el.tagName == 'TEXTAREA') {
            el.setProperty('spellcheck', false)
        }
    }

	// check whether we've refreshed a page and browser remembered modified
	// form values
	var check_page_refresh = $('check_page_refresh');
	if (!check_page_refresh || check_page_refresh.value == '1') {
		// run all field validators
		var errors = {};
		for (var i = 0; i < elements_count; i++) {
			validate_field(elements[i], false, errors);
		}
		// run all fieldset validators
		$$('fieldset').each(function(el){
			validate_fieldset(el, false, errors);
		});
		
		displayErrors(errors);
	} else if (check_page_refresh) {
		check_page_refresh.value = '1';
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
    var tabs_menu = tab_link.getParent('.tabs');

    var links = tabs_menu.getElements('a');
    var contents;
    for (var i = 0, imax = links.length; i < imax; i++) {
        contents = $(links[i].getProperty('href').substr(1));
        if (links[i] == tab_link) {
            links[i].addClass('active');
            contents.style.display = 'block';
        } else {
            links[i].removeClass('active');
            contents.style.display = 'none';
        }
    }
    location.hash = 'tab_' + tab_link.getProperty('href').substr(1);
}

window.addEvent('domready', function() {
    var tabs = $$('.tabs');
    var url_tab = location.hash.match(/^#tab_.+/)
        ? $$('a[href$="' + location.hash.substr(5) + '"]') : null;
    if (url_tab) {
        url_tab = url_tab[0];
    }
    // add tabs events and activate one tab (the first one or indicated by location hash)
    for (var i = 0, imax = tabs.length; i < imax; i++) {
        var links = tabs[i].getElements('a');
        var selected_tab = links[0];
        for (var j = 0, jmax = links.length; j < jmax; j++) {
            links[j].addEvent('click', function(e) {
                e.stop();
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
    (function() {
        if (location.hash != prev_hash) {
            prev_hash = location.hash;
            var url_tab = location.hash.match(/^#tab_.+/)
                ? $$('a[href$="' + location.hash.substr(5) + '"]') : null;
            if (url_tab) {
                setTab(url_tab[0]);
            }
        }
    }).periodical(200);
});

//
// END: Tabbed forms
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// Form reset buttons
//

window.addEvent('domready', function() {
    var buttons = $$('input[type=button]');
    for (var i = 0, imax = buttons.length; i < imax; i++) {
        buttons[i].addEvent('click', function(e) {
            var fields = this.getParent('fieldset').getElements('input, select, textarea');
            for (var i = 0, imax = fields.length; i < imax; i++) {
                setFieldValue(fields[i], getFieldType(fields[i]));
            }
        });
    }
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
    var field = $(field_id);
    if (!field || !$defined(defaultValues[field_id])) {
        return;
    }
    setFieldValue(field, getFieldType(field), defaultValues[field_id]);
}

window.addEvent('domready', function() {
    var buttons = $$('.restore-default, .set-value');
    var fixIE = Browser.Engine.name == 'trident' && Browser.Engine.version == 4;
    for (var i = 0, imax = buttons.length; i < imax; i++) {
        buttons[i].set('opacity', 0.25);
        if (!buttons[i].hasClass('restore-default')) {
            // restore-default is handled by markField
            buttons[i].style.display = '';
        }
        buttons[i].addEvents({
            mouseenter: function(e) {this.set('opacity', 1);},
            mouseleave: function(e) {this.set('opacity', 0.25);},
            click: function(e) {
                e.stop();
                var href = this.getProperty('href').substr(1);
                var field_id;
                if (this.hasClass('restore-default')) {
                    field_id = href;
                    restoreField(field_id);
                } else {
                    field_id = href.match(/^[^=]+/)[0];
                    var value = href.match(/=(.+)$/)[1];
                    setFieldValue($(field_id), 'text', value);
                }
                $(field_id).fireEvent('change');
            }
        });
        // fix IE showing <img> alt text instead of link title
        if (fixIE) {
            buttons[i].getChildren('img')[0].alt = buttons[i].title;
        }
    }
});

//
// END: "Restore default" and "set value" buttons
// ------------------------------------------------------------------
