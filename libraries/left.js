/* $Id$ */


// These scripts were originally found on cooltype.com.
// Modified 01/01/1999 by Tobias Ratschiller for linuxapps.com

// Modified 7th June 2000 by Brian Birtles for Mozilla 5.0
// compatibility for phpMyAdmin

// Rewritten and put in a libray 2nd May 2001 by Loïc Chapeaux

// Test passed with:
// - Mozilla 0.8.1-0.9.6 for Windows (js enabled & disabled)
// - IE5, 5.01, 5.5, 6.0 for Windows
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
  if (typeof(firstInd) != 'undefined' && firstInd != null) {
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
  if (!capable || !isServer || isLightMode)
    return;

  var tempColl    = null;
  var tempCollCnt = null;
  var i           = 0;

  if (isDOM) {
    tempColl    = document.getElementsByTagName('DIV');
    tempCollCnt = tempColl.length;
    for (i = 0; i < tempCollCnt; i++) {
      if (tempColl[i].id == expandedDb)
        tempColl[i].style.display = 'block';
      else if (tempColl[i].className == 'child')
        tempColl[i].style.display = 'none';
    }
  } // end of the DOM case
  else if (isIE4) {
    tempColl    = document.all.tags('DIV');
    tempCollCnt = tempColl.length;
    for (i = 0; i < tempCollCnt; i++) {
      if (tempColl(i).id == expandedDb)
        tempColl(i).style.display = 'block';
      else if (tempColl(i).className == 'child')
        tempColl(i).style.display = 'none';
    }
  } // end of the IE4 case
  else if (isNS4) {
    var theLayers  = document.layers;
    var layersCnt  = theLayers.length;
    for (i = 0; i < layersCnt; i++) {
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
 * @param  string  the name of the database to act on
 * @param  boolean whether to expand or to collapse the database content
 *
 * @access  public
 */
function expandBase(el, unexpand)
{
  if (!capable)
    return;

  var whichEl = null;
  var whichIm = null;

  if (isDOM) {
    whichEl = document.getElementById(el + 'Child');
    whichIm = document.getElementById(el + 'Img');
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
    whichEl = document.all(el + 'Child');
    whichIm = document.images.item(el + 'Img');
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
    whichEl = document.layers[el + 'Child'];
    whichIm = document.layers[el + 'Parent'].document.images['imEx'];
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
 * Hilight/un-hilight a database when the mouse pass over/out it
 *
 * @param  string  the name of the database to act on
 * @param  boolean the color to be used
 *
 * @access  public
 */
function hilightBase(el, theColor)
{
  if (!isDOM && !isIE4) {
    return null;
  }

  var whichDb     = null;
  var whichTables = null;

  if (isDOM) {
    whichDb       = document.getElementById(el + 'Parent');
    whichTables   = document.getElementById(el + 'Child');
  }
  else if (isIE4) {
    whichDb       = document.all(el + 'Parent');
    whichTables   = document.all(el + 'Child');
  }

  if (typeof(whichDb.style) == 'undefined') {
    return null;
  }
  else if (whichTables) {
    whichDb.style.backgroundColor     = theColor;
    whichTables.style.backgroundColor = theColor;
  }
  else {
    whichDb.style.backgroundColor     = theColor;
  }

  return true;
} // end of the 'hilightBase()' function


/**
 * Add styles for positioned layers
 */
if (capable) {
    // Brian Birtles : This is not the ideal method of doing this
    // but under the 7th June '00 Mozilla build (and many before
    // it) Mozilla did not treat text between <style> tags as
    // style information unless it was written with the one call
    // to write().
    if (isDOM) {
      var lstyle = '<style type="text\/css">'
                 + '<!--'
                 + 'div {color: #000000}'
                 + '.heada {font-family: ' + fontFamily + '; font-size: ' + fontSize + '; color: #000000}'
                 + '.headaCnt {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #000000}'
                 + '.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none; display: block}'
                 + '.child {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #333399; text-decoration: none; display: none}'
                 + '.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size: ' + fontSmall + '; color: #333399; text-decoration: none}'
                 + '.tblItem:hover {color: #FF0000; text-decoration: underline}'
                 + '\/\/-->'
                 + '<\/style>';
      document.write(lstyle);
    }
    else {
      document.writeln('<style type="text\/css">');
      document.writeln('<!--');
      document.writeln('div {color: #000000}');
      document.writeln('.heada {font-family: ' + fontFamily + '; font-size: ' + fontSize + '; color: #000000}');
      document.writeln('.headaCnt {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #000000}');
      if (isIE4) {
        document.writeln('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none; display: block}');
        document.writeln('.child {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #333399; text-decoration: none; display: none}');
        document.writeln('.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size: ' + fontSmall + '; color: #333399; text-decoration: none}');
        document.writeln('.tblItem:hover {color: #FF0000; text-decoration: underline}');
      }
      else { // NS4 case
      	if (isLightMode) {
          document.writeln('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none}');
          document.writeln('.child {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #333399}');
        } else {
          document.writeln('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none; position: absolute; visibility: hidden}');
          document.writeln('.child {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #333399; position: absolute; visibility: hidden}');
        }
        document.writeln('.item, .tblItem {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #333399; text-decoration: none}');
      }
      document.writeln('\/\/-->');
      document.writeln('<\/style>');
    }
}
else {
    document.writeln('<style type="text\/css">');
    document.writeln('<!--');
    document.writeln('div {color: #000000}');
    document.writeln('.heada {font-family: ' + fontFamily + '; font-size: ' + fontSize + '; color: #000000}');
    document.writeln('.headaCnt {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #000000}');
    document.writeln('.parent {font-family: ' + fontFamily + '; color: #000000; text-decoration: none}');
    document.writeln('.child {font-family: ' + fontFamily + '; font-size: ' + fontSmall + '; color: #333399; text-decoration: none}');
    document.writeln('.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size: ' + fontSmall + '; color: #333399; text-decoration: none}');
    document.writeln('.tblItem:hover {color: #FF0000; text-decoration: underline}');
    document.writeln('\/\/-->');
    document.writeln('<\/style>');
} // end of adding styles


window.onload = initIt;
