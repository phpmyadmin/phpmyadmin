/* $Id$ */


// These scripts were originally found on cooltype.com.
// Modified 01/01/1999 by Tobias Ratschiller for linuxapps.com

// Modified 7th June 2000 by Brian Birtles for Mozilla 5.0
// compatibility for phpMyAdmin

// Rewritten and put in a libray 2nd May 2001 by Loïc Chapeaux

// Test passed with:
// - Mozilla 0.8.1, 0.9.0, 0.9.1, 0.9.2 for Windows (js enabled
//    & disabled)
// - IE5, 5.01, 5.5 for Windows
// - Netscape 4.75 for Windows

// Test failed (crappy DOM implementations) with:
// - Opera 5.02 for windows: 'getElementsByTagName' is unsupported
// - Opera 5.10 to 5.12 for windows, Opera 5+ for Linux: 'style.display' can't
//   be changed
// - Konqueror 2+: 'style.display' can't be changed


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
  if (!capable || !isServer)
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
    var whichIm = document.images.item(el + 'Img');
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
                 + 'div {color: #000000;}'
                 + '.heada {font-family: ' + fontFamily + '; font-size: 10pt}'
                 + '.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration:none; display: block}'
                 + '.child {font-family: ' + fontFamily + '; font-size: 8pt; color: #333399; text-decoration:none; display: none}'
                 + '.item, .item:active, .item:hover, .tblItem, .tblItem:active {color: #333399; text-decoration: none; font-size: 8pt;}'
                 + '.tblItem:hover {color: #FF0000; text-decoration: underline}'
                 + '<\/style>';
      write(lstyle);
    }
    else {
      write('<style type="text/css">');
      write('div {color: #000000; }');
      write('.heada {font-family: ' + fontFamily + '; font-size: 10pt}');
      if (isIE4) {
        write('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none; display: block}');
        write('.child {font-family: ' + fontFamily + '; font-size: 8pt; color: #333399; text-decoration: none; display: none}');
        write('.item, .item:active, .item:hover, .tblItem, .tblItem:active {color: #333399; text-decoration: none; font-size: 8pt}');
        write('.tblItem:hover {color: #FF0000; text-decoration: underline}');
      }
      else {
        write('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none; position: absolute; visibility: hidden}');
        write('.child {font-family: ' + fontFamily + '; font-size: 8pt; color: #333399; position: absolute; visibility: hidden}');
        write('.item, .tblItem {color: #333399; text-decoration: none}');
      }
      write('<\/style>');
    }
  }
}
else {
  with (document) {
    write('<style type="text/css">');
    write('div {color: #000000; }');
    write('.heada {font-family: ' + fontFamily + '; font-size: 10pt}');
    write('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none}');
    write('.child {font-family: ' + fontFamily + '; font-size: 8pt; color: #333399; text-decoration: none}');
    write('.item, .item:active, .item:hover, .tblItem, .tblItem:active {color: #333399; text-decoration: none}');
    write('.tblItem:hover {color: #FF0000; text-decoration: underline}');
    write('<\/style>');
  }
} // end of adding styles


onload = initIt;
