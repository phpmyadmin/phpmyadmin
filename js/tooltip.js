/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays the Tooltips (hints), if we have some
 * 2005-01-20 added by Michael Keck (mkkeck)
 *
 * @version $Id$
 */

/**
 *
 */
var ttXpos = 0, ttYpos = 0;
var ttXadd = 10, ttYadd = -10;
var ttDisplay = 0, ttHoldIt = 0;

// Check if browser does support dynamic content and dhtml
if (document.getElementById) {
    // DOM-compatible browsers
    var ttDOM = 1;
} else {
    // the old Netscape 4
    var ttNS4 = (document.layers) ? 1 : 0;
    // browser wich uses document.all
    var ttIE4 = (document.all) ? 1 : 0;
}

var myTooltipContainer = null;

/**
 * initialize tooltip
 */
function PMA_TT_init()
{
    // get all 'light bubbles' on page
    var tooltip_icons = window.parent.getElementsByClassName('footnotemarker', document, 'sup');
    var tooltip_count = tooltip_icons.length;

    if (tooltip_count < 1) {
        // no 'bubbles' found
        return;
    }

    // insert tooltip container
    myTooltipContainer = document.createElement("div");
    myTooltipContainer.id = 'TooltipContainer';
    window.parent.addEvent(myTooltipContainer, 'mouseover', holdTooltip);
    window.parent.addEvent(myTooltipContainer, 'mouseout', swapTooltip);
    document.body.appendChild(myTooltipContainer);

    // capture mouse-events
    for (i = 0; i < tooltip_count; i++) {
        window.parent.addEvent(tooltip_icons[i], 'mousemove', mouseMove);
        window.parent.addEvent(tooltip_icons[i], 'mouseover', pmaTooltip);
        window.parent.addEvent(tooltip_icons[i], 'mouseout', swapTooltip);
    }
}

/**
 * init the tooltip and write the text into it
 *
 * @param string theText tooltip content
 */
function PMA_TT_setText(theText)
{
    if (ttDOM || ttIE4) {                   // document.getEelementById || document.all
        myTooltipContainer.innerHTML = "";  // we should empty it first
        myTooltipContainer.innerHTML = theText;
    } else if (ttNS4) {                     // document.layers
        var layerNS4 = myTooltipContainer.document;
        layerNS4.write(theText);
        layerNS4.close();
    }
}

/**
 * @var integer
 */
var ttTimerID = 0;

/**
 * swap the Tooltip // show and hide
 *
 * @param boolean stat view status
 */
function swapTooltip(stat)
{
    if (ttHoldIt != 1) {
        if (stat == 'true') {
            showTooltip(true);
        } else if (ttDisplay) {
            ttTimerID = setTimeout("showTooltip(false);", 500);
        } else {
            showTooltip(true);
        }
    } else {
        if (ttTimerID) {
           clearTimeout(ttTimerID);
           ttTimerID = 0;
        }
        showTooltip(true);
    }
}

/**
 * show / hide the Tooltip
 *
 * @param boolean stat view status
 */
function showTooltip(stat)
{
    if (stat == false) {
        if (ttNS4)
            myTooltipContainer.visibility = "hide";
        else
            myTooltipContainer.style.visibility = "hidden";
        ttDisplay = 0;
    } else {
        if (ttNS4)
            myTooltipContainer.visibility = "show";
        else
            myTooltipContainer.style.visibility = "visible";
        ttDisplay = 1;
    }
}

/**
 * hold it, if we create or move the mouse over the tooltip
 */
function holdTooltip()
{
    ttHoldIt = 1;
    swapTooltip('true');
    ttHoldIt = 0;
}

/**
 * move the tooltip to mouse position
 *
 * @param integer posX    horiz. position
 * @param integer posY    vert. position
 */
function moveTooltip(posX, posY)
{
    if (ttDOM || ttIE4) {
        myTooltipContainer.style.left = posX + "px";
        myTooltipContainer.style.top  = posY + "px";
    } else if (ttNS4) {
        myTooltipContainer.left = posX;
        myTooltipContainer.top  = posY;
    }
}

/**
 * build the tooltip
 * usally called from eventhandler
 *
 * @param    string    theText    tooltip content
 */
function pmaTooltip(e)
{
    var theText = document.getElementById(this.getAttribute('name')).innerHTML;

    var plusX = 0, plusY = 0, docX = 0, docY = 0;
    var divHeight = myTooltipContainer.clientHeight;
    var divWidth  = myTooltipContainer.clientWidth;

    if (navigator.appName.indexOf("Explorer") != -1) {
        // IE ...
        if (document.documentElement && document.documentElement.scrollTop) {
            plusX = document.documentElement.scrollLeft;
            plusY = document.documentElement.scrollTop;
            docX = document.documentElement.offsetWidth + plusX;
            docY = document.documentElement.offsetHeight + plusY;
        } else {
            plusX = document.body.scrollLeft;
            plusY = document.body.scrollTop;
            docX = document.body.offsetWidth + plusX;
            docY = document.body.offsetHeight + plusY;
        }
    } else {
        docX = document.body.clientWidth;
        docY = document.body.clientHeight;
    }

    ttXpos = ttXpos + plusX;
    ttYpos = ttYpos + plusY;

    if ((ttXpos + divWidth) > docX)
        ttXpos = ttXpos - (divWidth + (ttXadd * 2));
    if ((ttYpos + divHeight) > docY)
        ttYpos = ttYpos - (divHeight + (ttYadd * 2));

    PMA_TT_setText(theText);
    moveTooltip((ttXpos + ttXadd), (ttYpos + ttYadd));
    holdTooltip();
}

/**
 * register mouse moves
 *
 * @param    event    e
 */
function mouseMove(e) {
    if ( typeof( event ) != 'undefined' ) {
        ttXpos = event.x;
        ttYpos = event.y;
    } else {
        ttXpos = e.pageX;
        ttYpos = e.pageY;
    }
    moveTooltip((ttXpos + ttXadd), (ttYpos + ttYadd));
}
