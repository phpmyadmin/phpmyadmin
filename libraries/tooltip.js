/* $Id$ */


/**
  * Displays the Tooltips / tooltips, if we have some
  * 2005-01-20 added by Michael Keck (mkkeck)
  */


var ttXpos = 0, ttYpos = 0;
var ttXadd = 10, ttYadd = -10;
var ttDisplay = 0, ttHoldIt = 0;
// Check if browser does support divContaiber / Tooltips
var ttNS4 = (document.layers) ? 1 : 0;           // the old Netscape 4
var ttIE4 = (document.all) ? 1 : 0;              // browser wich uses document.all
var ttDOM = (document.getElementById) ? 1 : 0;   // DOM-compatible browsers
if (ttDOM) {   // if DOM-compatible, the the others to false
    ttNS4	=	0;
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
    document.onmousemove = mouseMove;
    if (ttNS4)
        document.captureEvents(Event.MOUSEMOVE);

}

/**
  * init the Tooltip and write the text into it
  */
function textTooltip(theText) {
    //show(myTooltipContainer);
    if	(ttDOM || ttIE4) { // document.getEelementById || document.all
        myTooltipContainer.innerHTML = ""; // we should empty it first
        myTooltipContainer.innerHTML = theText;
    } else if (ttNS4) { // document.layers
        var layerNS4 = myTooltipContainer.document;
        layerNS4.write(theText);
        layerNS4.close();
    }
}    

/**
  * swap the Tooltip // show and hide
  */
var ttTimerID = 0;
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
  * show / hide the Tooltip
  */
function holdTooltip() {
    ttHoldIt = 1;
    swapTooltip('true');
    ttHoldIt = 0;
}

/**
  * move the Tooltip to mouse position
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
  * build the Tooltip
  */
function pmaTooltip(theText) {
    textTooltip(theText);
    moveTooltip((ttXpos + ttXadd), (ttYpos + ttYadd));
    holdTooltip();
}

/**
  * register mouse moves
  */
function mouseMove(e) {
    var x=0, y=0, plusX=0, plusY=0, docX=0; docY=0;
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
        x = event.x + plusX;
        y = event.y + plusY;
    } else {
        x = e.pageX;
        y = e.pageY;
        docX = document.body.clientWidth;
        docY = document.body.clientHeight;
    }
    ttXpos = x;
    ttYpos = y;
    if ((ttXpos + divWidth) > docX)
        ttXpos = ttXpos - (divWidth + (ttXadd * 2));
    if ((ttYpos + divHeight) > docY)
        ttYpos = ttYpos - (divHeight + (ttYadd * 2));

}