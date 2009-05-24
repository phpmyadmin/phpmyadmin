/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation frame
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
 *
 * @param   string  id          id of the element in the DOM
 * @param   boolean only_open   do not close/hide element
 */
function toggle(id, only_open) {
    var el = document.getElementById('subel' + id);
    if (! el) {
        return false;
    }

    var img = document.getElementById('el' + id + 'Img');

    if (el.style.display == 'none' || only_open) {
        el.style.display = '';
        if (img) {
            img.src = image_minus;
            img.alt = '-';
        }
    } else {
        el.style.display = 'none';
        if (img) {
            img.src = image_plus;
            img.alt = '+';
        }
    }
    return true;
}

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
        pma_navi_width = parseInt(parent.document.getElementById('mainFrameset').cols)
    } else {
        pma_navi_width = parent.document.getElementById('mainFrameset').cols.match(/\d+$/) 
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
    if (pma_navi_width != null) {
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
 * @param   string  name    name of the value to retrieve
 * @return  string  value   value for the given name from cookie
 */
function PMA_getCookie(name) {
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
 * @param   string  name    name of value
 * @param   string  value   value to be stored
 * @param   Date    expires expire time
 * @param   string  path
 * @param   string  domain
 * @param   boolean secure
 */
function PMA_setCookie(name, value, expires, path, domain, secure) {
    document.cookie = name + "=" + escape(value) +
        ( (expires) ? ";expires=" + expires.toGMTString() : "") +
        ( (path)    ? ";path=" + path : "") +
        ( (domain)  ? ";domain=" + domain : "") +
        ( (secure)  ? ";secure" : "");
}

/**
 * hide all LI elements with second A tag which doesn`t contain requested value
 *
 *	@param   string  value    requested value
 *
 */
function fast_filter(value){
	var oTarget = document.getElementById("subel0");
	if(!oTarget || !document.getElementById('fast_filter')) return false;
	if(value!=document.getElementById('fast_filter').value) return false;
	document.getElementById('fast_filter').disabled=true;
	for(var iCh in oTarget.childNodes){
		var oCh = oTarget.childNodes.item(iCh);
		if(!oCh) continue;
		if(oCh.nodeName=="LI"){
			if(value=="") oCh.style.display="";
			else{
				var i=0;
				for(var iA in oCh.childNodes){
					var oA = oCh.childNodes.item(iA);
					if(!oA) continue;
					if(oA.nodeName=="A"){
						if(i==0) i = 1;
						else{
							if(oA.innerHTML.indexOf(value)==-1) oCh.style.display="none";
							else oCh.style.display="";
						}
					}
				}
			}
		}
	}
	document.getElementById('fast_filter').disabled=false;
}