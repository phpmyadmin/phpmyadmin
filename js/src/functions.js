import { AJAX } from './ajax';
/**
 * Here we register a function that will remove the onsubmit event from all
 * forms that will be handled by the generic page loader. We then save this
 * event handler in the "jQuery data", so that we can fire it up later in
 * AJAX.requestHandler().
 *
 * See bug #3583316
 */
export function onload () {
    // Registering the onload event for functions.js
    // ensures that it will be fired for all pages
    $('form').not('.ajax').not('.disableAjax').each(function () {
        if ($(this).attr('onsubmit')) {
            $(this).data('onsubmit', this.onsubmit).attr('onsubmit', '');
        }
    });

    var $page_content = $('#page_content');
    /**
     * Workaround for passing submit button name,value on ajax form submit
     * by appending hidden element with submit button name and value.
     */
    $page_content.on('click', 'form input[type=submit]', function () {
        var buttonName = $(this).attr('name');
        if (typeof buttonName === 'undefined') {
            return;
        }
        $(this).closest('form').append($('<input/>', {
            'type' : 'hidden',
            'name' : buttonName,
            'value': $(this).val()
        }));
    });

    /**
     * Attach event listener to events when user modify visible
     * Input,Textarea and select fields to make changes in forms
     */
    $page_content.on(
        'keyup change',
        'form.lock-page textarea, ' +
        'form.lock-page input[type="text"], ' +
        'form.lock-page input[type="number"], ' +
        'form.lock-page select',
        { value:1 },
        AJAX.lockPageHandler
    );
    $page_content.on(
        'change',
        'form.lock-page input[type="checkbox"], ' +
        'form.lock-page input[type="radio"]',
        { value:2 },
        AJAX.lockPageHandler
    );
    /**
     * Reset lock when lock-page form reset event is fired
     * Note: reset does not bubble in all browser so attach to
     * form directly.
     */
    $('form.lock-page').on('reset', function (event) {
        AJAX.resetLock();
    });
}
/**
 * Unbind all event handlers before tearing down a page
 */
export function teardown1 () {
    $(document).off('click', 'a.themeselect');
    $(document).off('change', '.autosubmit');
    $('a.take_theme').off('click');
}

export function onload1 () {
    /**
     * Theme selector.
     */
    $(document).on('click', 'a.themeselect', function (e) {
        window.open(
            e.target,
            'themes',
            'left=10,top=20,width=510,height=350,scrollbars=yes,status=yes,resizable=yes'
        );
        return false;
    });

    /**
     * Automatic form submission on change.
     */
    $(document).on('change', '.autosubmit', function (e) {
        e.preventDefault();
        debugger;
        console.log($(this).closest('form').submit());
        $(this).closest('form').submit();
        debugger;
    });

    /**
     * Theme changer.
     */
    $('a.take_theme').on('click', function (e) {
        var what = this.name;
        if (window.opener && window.opener.document.forms.setTheme.elements.set_theme) {
            window.opener.document.forms.setTheme.elements.set_theme.value = what;
            window.opener.document.forms.setTheme.submit();
            window.close();
            return false;
        }
        return true;
    });
}
