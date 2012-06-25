/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation frame
 *
 * @package phpMyAdmin-Navigation
 */

/**
 * init
 */
var today = new Date();
var expires = new Date(today.getTime() + (56 * 86400000));
var pma_navi_width;
var pma_saveframesize_timeout = null;

/**
 * opens/closes (hides/shows) tree elements
 * loads data via ajax
 */
$(document).ready(function() {
	$('#pma_navigation_tree a.expander').live('click', function(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        var $this = $(this);
        var $children = $this.closest('li').children('div.list_container');
        var $icon = $this.parent().find('img');
        if ($this.hasClass('loaded')) {
	        if ($icon.is('.ic_b_plus')) {
		        $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
		        $children.show('fast');
	        } else {
		        $icon.removeClass('ic_b_minus').addClass('ic_b_plus');
		        $children.hide('fast');
	        }
        } else {
            var $destination = $this.closest('li');
            var $throbber = $('.throbber').first().clone().show();
            $icon.hide();
            $throbber.insertBefore($icon);
            $.get($this.attr('href'), {ajax_request: true}, function (data) {
                if (data.success === true) {
                    $this.addClass('loaded');
                    $destination.find('div.list_container').remove(); // FIXME: Hack, there shouldn't be a list container there
                    $destination.append(data.message);
	                $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
	                $destination.children('div.list_container').show('fast');
                    if ($destination.find('ul > li').length == 1) {
                        $destination.find('ul > li').find('a.expander.container').click();
                    }
                }
                $icon.show();
                $throbber.remove();
            });
        }
        $(this).blur();
	});
});

function PMA_callFunctionDelayed(myfunction, delay)
{
    if (typeof pma_saveframesize_timeout == "number") {
         window.clearTimeout(pma_saveframesize_timeout);
        pma_saveframesize_timeout = null;
    }
}

/**
 * saves current navigation frame width in a cookie
 * usally called on resize of the navigation frame
 */
function PMA_saveFrameSizeReal()
{
    if (parent.text_dir == 'ltr') {
        pma_navi_width = parseInt(parent.document.getElementById('mainFrameset').cols);
    } else {
        pma_navi_width = parent.document.getElementById('mainFrameset').cols.match(/\d+$/);
    }
    if ((pma_navi_width > 0) && (pma_navi_width != PMA_getCookie('pma_navi_width'))) {
        PMA_setCookie('pma_navi_width', pma_navi_width, expires);
    }
}

/**
 * calls PMA_saveFrameSizeReal with delay
 */
function PMA_saveFrameSize()
{
    //alert(typeof(pma_saveframesize_timeout) + ' : ' + pma_saveframesize_timeout);

    if (typeof pma_saveframesize_timeout == "number") {
        window.clearTimeout(pma_saveframesize_timeout);
        pma_saveframesize_timeout = null;
    }

    pma_saveframesize_timeout = window.setTimeout(PMA_saveFrameSizeReal, 2000);
}

/**
 * sets navigation frame width to the value stored in the cookie
 * usally called on document load
 */
function PMA_setFrameSize()
{
    pma_navi_width = PMA_getCookie('pma_navi_width');
    //alert('from cookie: ' + typeof(pma_navi_width) + ' : ' + pma_navi_width);
    if (pma_navi_width != null && parent.document != document) {
        if (parent.text_dir == 'ltr') {
            parent.document.getElementById('mainFrameset').cols = pma_navi_width + ',*';
        } else {
            parent.document.getElementById('mainFrameset').cols = '*,' + pma_navi_width;
        }
        //alert('framesize set');
    }
}

/**
 * retrieves a named value from cookie
 *
 * @param string  name    name of the value to retrieve
 * @return string  value   value for the given name from cookie
 */
function PMA_getCookie(name)
{
    var start = document.cookie.indexOf(name + "=");
    var len = start + name.length + 1;
    if ((!start) && (name != document.cookie.substring(0, name.length))) {
        return null;
    }
    if (start == -1) {
        return null;
    }
    var end = document.cookie.indexOf(";", len);
    if (end == -1) {
        end = document.cookie.length;
    }
    return unescape(document.cookie.substring(len,end));
}

/**
 * stores a named value into cookie
 *
 * @param string  name    name of value
 * @param string  value   value to be stored
 * @param Date    expires expire time
 * @param string  path
 * @param string  domain
 * @param boolean secure
 */
function PMA_setCookie(name, value, expires, path, domain, secure)
{
    document.cookie = name + "=" + escape(value) +
        ( (expires) ? ";expires=" + expires.toGMTString() : "") +
        ( (path)    ? ";path=" + path : "") +
        ( (domain)  ? ";domain=" + domain : "") +
        ( (secure)  ? ";secure" : "");
}

/* Performed on load */
$(function(){

    $('#pma_navigation_tree div.pageselector a.ajax').live('click', function (e) {
        e.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        $.get($(this).attr('href'), {ajax_request: true, full: true}, function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (data.success) {
                $('#pma_navigation_tree').html(data.message).children('div').show();
            }
        });
    });

    // Node highlighting
	$('#navigation_tree.highlight li:not(.fast_filter)').live('mouseover', function () {
        if ($('li:visible', this).length == 0) {
            $(this).css('background', '#ddd');
        }
    });
	$('#navigation_tree.highlight li:not(.fast_filter)').live('mouseout', function () {
        $(this).css('background', '');
    });

    // FIXME: reintegrate ajax table filtering
    // when the table pagination is implemented

    // Bind "clear fast filter"
    $('li.fast_filter > span').live('click', function () {
        // Clear the input and apply the fast filter with empty input
        var value = $(this).prev()[0].defaultValue;
        $(this).prev().val(value).trigger('keyup');
    });
    // Bind "fast filter"
    $('li.fast_filter > input').live('focus', function () {
        if ($(this).val() == this.defaultValue) {
            $(this).val('');
        } else {
            $(this).select();
        }
    });
    $('li.fast_filter > input').live('blur', function () {
        if ($(this).val() == '') {
            $(this).val(this.defaultValue);
        }
    });
    $('li.fast_filter > input').live('keyup', function () {
        var $obj = $(this).parent().parent();
        var str = '';
        if ($(this).val() != this.defaultValue) {
            str = $(this).val().toLowerCase();
        }
        $obj.find('li > a').not('.container').each(function () {
            if ($(this).text().toLowerCase().indexOf(str) != -1) {
                $(this).parent().show().removeClass('hidden');
            } else {
                $(this).parent().hide().addClass('hidden');
            }
        });
        var container_filter = function ($curr, str) {
            $curr.children('li').children('a.container').each(function () {
                var $group = $(this).parent().children('ul');
                if ($group.children('li').children('a.container').length > 0) {
                    container_filter($group); // recursive
                }
                $group.parent().show().removeClass('hidden');
                if ($group.children().not('.hidden').length == 0) {
                    $group.parent().hide().addClass('hidden');
                }
            });
        };
        container_filter($obj, str);
    });

    // Jump to recent table
    $('#recentTable').change(function() {
        if (this.value != '') {
            var arr = jQuery.parseJSON(this.value);
            var $form = $(this).closest('form');
            $form.find('input[name=db]').val(arr['db']);
            $form.find('input[name=table]').val(arr['table']);
            $form.submit();
        }
    });
});//end of document get ready

