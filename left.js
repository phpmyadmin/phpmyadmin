/* $Id$ */

// These scripts were originally found on cooltype.com.
// Modified 01/01/1999 by Tobias Ratschiller for linuxapps.com

// Modified 7th June 2000 by Brian Birtles for Mozilla 5.0
// compatibility for phpMyAdmin

// Rewritten and put in a libray 2nd May 2001 by Loïc Chapeaux

// Test passed with:
// - Mozilla 0.8.1 for Windows (js enabled & disabled)
// - IE5, 5.01, 5.5 for Windows
// - Netscape 4.75 for Windows
// - Opera 5.02 for windows (js disabled)

// Test failed with:
// - Opera 5.02 for windows with js enabled -> crappy DOM implementation
//   ('getElementsByTagName' is unsupported), nothing to do :(


var isExpanded   = false;

var imgOpened    = new Image(9,9);
imgOpened.src    = 'images/minus.gif';
var imgClosed    = new Image(9,9);
imgClosed.src    = 'images/plus.gif';


/**
 * Do reloads the frame if the window has been resized under Netscape4+
 *
 * @access  private
 */
function reDo() {
  if (innerWidth != origWidth || innerHeight != origHeight)
    location.reload(true);
} // end of the 'reDo()' function

/**
 * Positioned element resize bug under NS4+
 */
if (isNS4) {
  var origWidth  = innerWidth;
  var origHeight = innerHeight;
  onresize       = reDo;
}


/**
 * Specific stuffs for IE4
 */
function doDocumentOnMouseOver() {
  var eSrc = window.event.srcElement ;
  if (eSrc.className == 'item') {
    window.event.srcElement.className = 'highlight';
  }
} // end of the 'doDocumentOnMouseOver()' function

function doDocumentOnMouseOut() {
  var eSrc = window.event.srcElement ;
  if (eSrc.className == 'highlight') {
    window.event.srcElement.className = 'item';
  }
} // end of the 'doDocumentOnMouseOut()' function

if (isIE4) {
  document.onmouseover = doDocumentOnMouseOver ;
  document.onmouseout = doDocumentOnMouseOut ;
}


/**
 * Gets the id of the first collapsible room
 *
 * @param  string  the name of the first collapsible room
 *
 * @return  integer  the index number corresponding to this room
 *
 * @access  public
 */
function nsGetIndex(el) {
  var ind       = null;
  var theLayers = document.layers;
  var layersCnt = theLayers.length;
  for (var i = 0; i < layersCnt; i++) {
    if (theLayers[i].id == el) {
      ind = i;
      break;
    }
  }
  return ind;
} // end of the 'nsGetIndex()' function


/**
 * Positions layers under NS4+
 *
 * @access  public
 */
function nsArrangeList() {
  if (firstInd != null) {
    var theLayers = document.layers;
    var layersCnt = theLayers.length;
    var nextY     = theLayers[firstInd].pageY + theLayers[firstInd].document.height;
    for (var i = firstInd + 1; i < layersCnt; i++) {
      if (theLayers[i].visibility != 'hide') {
        theLayers[i].pageY = nextY;
        nextY              += theLayers[i].document.height;
      }
    }
  }
} // end of the 'nsArrangeList()' function


/**
 * Expand databases at startup
 *
 * @access  public
 */
function nsShowAll() {
  var theLayers = document.layers;
  var layersCnt = theLayers.length;
  for (i = firstInd; i < layersCnt; i++) {
    theLayers[i].visibility = 'show';
  }
} // end of the 'nsShowAll()' function


/**
 * Collapses databases at startup
 *
 * @access  public
 */
function initIt()
{
  if (!capable)
    return;

  if (isDOM) {
    var tempColl    = document.getElementsByTagName('DIV');
    var tempCollCnt = tempColl.length;
    for (var i = 0; i < tempCollCnt; i++) {
      if (tempColl[i].id == expandedDb)
        tempColl[i].style.display = 'block';
      else if (tempColl[i].className == 'child')
        tempColl[i].style.display = 'none';
    }
  } // end of the DOM case
  else if (isIE4) {
    tempColl        = document.all.tags('DIV');
    var tempCollCnt = tempColl.length;
    for (var i = 0; i < tempCollCnt; i++) {
      if (tempColl(i).id == expandedDb)
        tempColl(i).style.display = 'block';
      else if (tempColl(i).className == 'child')
        tempColl(i).style.display = 'none';
    }
  } // end of the IE4 case
  else if (isNS4) {
    var theLayers  = document.layers;
    var layersCnt  = theLayers.length;
    for (var i = 0; i < layersCnt; i++) {
      if (theLayers[i].id == expandedDb)
        theLayers[i].visibility   = 'show';
      else if (theLayers[i].id.indexOf('Child') != -1)
        theLayers[i].visibility   = 'hide';
      else
        theLayers[i].visibility   = 'show';
    }
    nsArrangeList();
  } // end of the NS4 case
} // end of the 'initIt()' function


/**
 * Collapses/expands a database when the user require this to be done
 *
 * @param  string  the  name of the room to act on
 * @param  boolean whether to expand or to collapse the database content
 *
 * @access  public
 */
function expandBase(el, unexpand)
{
  if (!capable)
    return;

  if (isDOM) {
    var whichEl = document.getElementById(el + 'Child');
    var whichIm = document.getElementById(el + 'Img');
    if (whichEl.style.display == 'none' && whichIm) {
      whichEl.style.display  = 'block';
      whichIm.src            = imgOpened.src;
    }
    else if (unexpand) {
      whichEl.style.display  = 'none';
      whichIm.src            = imgClosed.src;
    }
  } // end of the DOM case
  else if (isIE4) {
    var whichEl = document.all(el + 'Child');
    var whichIm = document.images.item(el + 'Child');
    if (whichEl.style.display == 'none') {
      whichEl.style.display  = 'block';
      whichIm.src            = imgOpened.src;
    }
    else if (unexpand) {
      whichEl.style.display  = 'none';
      whichIm.src            = imgClosed.src;
    }
  } // end of the IE4 case
  else if (isNS4) {
    var whichEl = document.layers[el + 'Child'];
    var whichIm = document.layers[el + 'Parent'].document.images['imEx'];
    if (whichEl.visibility == 'hide') {
      whichEl.visibility  = 'show';
      whichIm.src         = imgOpened.src;
    }
    else if (unexpand) {
      whichEl.visibility  = 'hide';
      whichIm.src         = imgClosed.src;
    }
    nsArrangeList();
  } // end of the NS4 case
} // end of the 'expandBase()' function


/**
 * Add styles for positioned layers
 */
if (capable) {
  with (document) {
    // Brian Birtles : This is not the ideal method of doing this
    // but under the 7th June '00 Mozilla build (and many before
    // it) Mozilla did not treat text between <style> tags as
    // style information unless it was written with the one call
    // to write().
    if (isDOM) {
      var lstyle = '<style type="text/css">'
                 + '.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration:none; display:block}'
                 + '.child {font-family: ' + fontFamily + '; color: #000000; text-decoration:none; display:none}'
                 + '.item { color: darkblue; text-decoration:none; font-size: 8pt;}'
                 + '.highlight { color: red; font-size: 8pt;}'
                 + '.heada { font: 12px\/13px; Times}'
                 + 'div { color:black; }'
                 + '<\/style>';
      write(lstyle);
    }
    else {
      write('<style type="text/css">');
      if (isIE4) {
        write('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration:none;}');
        write('.child {font-family: ' + fontFamily + '; color: #000000; text-decoration:none; display:none}');
        write('.item { color: darkblue; text-decoration:none; font-size: 8pt;}');
        write('.highlight { color: red; font-size: 8pt;}');
        write('.heada { font: 12px\/13px; Times}');
        write('div { color:black; }');
      }
      else {
        write('.parent {font-family:' + fontFamily + '; color: #000000; text-decoration:none; position:absolute; visibility:hidden; color: black;}');
        write('.child {font-family: ' + fontFamily + '; font-size: 8pt;color: #000000; position:absolute; visibility:hidden}');
        write('.item { color: darkblue; text-decoration:none;}');
        write('.regular {font-family: ' + fontFamily + '; position:absolute; visibility:hidden}');
        write('div { color:black; }');
      }
      write('<\/style>');
    }
  }
} // end of adding styles


onload = initIt;
