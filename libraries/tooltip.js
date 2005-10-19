/* $Id$ */


/**
 * Displays the Tooltips (hints), if we have some
 * 2005-01-20 added by Michael Keck (mkkeck)
 */

var ttXpos = 0, ttYpos = 0;
var ttXadd = 10, ttYadd = -10;
var ttDisplay = 0, ttHoldIt = 0;
// Check if browser does support dynamic content and dhtml
var ttNS4 = (document.layers) ? 1 : 0;           // the old Netscape 4
var ttIE4 = (document.all) ? 1 : 0;              // browser wich uses document.all
var ttDOM = (document.getElementById) ? 1 : 0;   // DOM-compatible browsers
if (ttDOM) { // if DOM-compatible, set the others to false
    ttNS4 = 0;
    ttIE4 = 0;
}

if ( (ttDOM) || (ttIE4) || (ttNS4) ) {
    // reference to TooltipContainer
    if (ttNS4) {
        var myTooltipContainer = document.TooltipContainer;
    } else if (ttIE4) {
        var myTooltipContainer = document.all('TooltipContainer');
    } else if (ttDOM) {
        var myTooltipContainer = document.getElementById('TooltipContainer');
    }
    // mouse-event
    if ( ttNS4 ) {
        document.captureEvents(Event.MOUSEMOVE);
    } else {
        document.onmousemove = mouseMove;
    }
}

/**
 * init the tooltip and write the text into it
 *
 * @param string theText tooltip content
 */
function textTooltip(theText) {
    if	(ttDOM || ttIE4) {                   // document.getEelementById || document.all
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
function swapTooltip(stat) {
    if (ttHoldIt!=1) {
        if (stat!='default') {
            if (stat=='true')
                showTooltip(true);
            else if (stat=='false')
                showTooltip(false);
        } else {
            if (ttDisplay)
                ttTimerID = setTimeout("showTooltip(false);",500);
            else
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
function showTooltip(stat) {
    if (stat==false) {
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
function holdTooltip() {
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
function moveTooltip(posX, posY) {
    if (ttDOM || ttIE4) {
        myTooltipContainer.style.left	=	posX + "px";
        myTooltipContainer.style.top  =	posY + "px";
    } else if (ttNS4) {
        myTooltipContainer.left = posX;
        myTooltipContainer.top  = posY;
    }
}

/**
 * build the tooltip
 *
 * @param    string    theText    tooltip content
 */
function pmaTooltip(theText) {
    var plusX=0, plusY=0, docX=0; docY=0;
    var divHeight = myTooltipContainer.clientHeight;
    var divWidth  = myTooltipContainer.clientWidth;
    if (navigator.appName.indexOf("Explorer")!=-1) {
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
    
    textTooltip(theText);
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
}
