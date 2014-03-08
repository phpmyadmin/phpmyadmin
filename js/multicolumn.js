/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    Implements the shiftkey + mouse click event for multicolumn sorting
 * @name            multi column
 *
 * @requires    jQuery
 */

//Global variables that tell if its the first click so far and if there has been indeed a click
var firstClicked = false,clicked = false;    
//A map array which contains column name to column order mapping
var columnsSelectedSoFar = {};
//Holds the beggining and ending part of the url. 
var URL = {};

/**
 * This function gets the starting and ending part of the url.
 *
 * @param object   url HTMLAnchor element
 * @return Array    URL The beginning and ending parts of the url
 */

function captureURL(url)
{
    var URL = {};
    url = '' + url;
    // Exclude the url part till HTTP
    url = url.substr(url.search("sql.php"), url.length);
    // The url part between ORDER BY and &session_max_rows needs to be replaced.
    URL['head'] = url.substr(0, url.search("ORDER") + 9);
    URL['tail'] = url.substr(url.search("&session_max_rows"), url.length);
    return URL;       
}

/**
 * This function is for capturing columns
 *
 * @param object   event data
 */

function captureColumns(e)
{
	// Mouse is already pressed so check if shift was pressed too.
	if (e.shiftKey) {
            e.preventDefault();
	// If this is the first time that a column has been selected in this way, capture the url
            if (!firstClicked){
                firstClicked = true;
                URL = captureURL(e.target);                
            }        
            clicked = true;
            var linkText = e.target.innerHTML;
	    //From the place where the mouse was pressed get the column name.
            var cutPoint = linkText.indexOf('<') === -1 ? linkText.length : linkText.indexOf('<');
            var columnName = linkText.substr(0, cutPoint);
	    // If a column is clicked once order it in ascending order, twice descending order
	    // thrice, leave that column
            if (columnsSelectedSoFar[columnName] === undefined){
                columnsSelectedSoFar[columnName] = 'ASC';
            }
            else {
                columnsSelectedSoFar[columnName] = columnsSelectedSoFar[columnName] == 'ASC' ? 'DESC' : undefined;
            }
        }
}

/**
 * This function is for navigating to the generated URL
 *
 * @param object   url HTMLAnchor element
 */

function redirect(e)
{	
	// Check if its just a shift button pressed
	if(clicked){
	// 16 is the shift key 
            if(e.which == 16){
                var url = "";
		// From the column name hashmap generated, generate the middle part of the url
                for( columnName in columnsSelectedSoFar) {
		      // Undefined means that column be left out from the middle part
                      if(columnsSelectedSoFar[columnName] === undefined)
                            continue;
                      columnNameTrim = $.trim(columnName);
		      // %60 is a tick (`) %2C is a comman (,)
                      url += '%60'+columnNameTrim+'%60+'+columnsSelectedSoFar[columnName]+'+%2C+';
                }
		// If none of the columns were selected, don't navigate
                if (url.length != 0){
		    // -4 is for removing the last ,
                    url = url.substr(0, url.length - 4);
                    url = URL['head'] + url;
                    url +=  URL['tail'];
                    window.location.replace(url);
                }
            }
        }
}


AJAX.registerOnload('keyhandler.js', function () {  
    $("th.draggable.column_heading.pointer.marker").live('click', function (event) {
	captureColumns(event.originalEvent);
    }); 
    $(document).live('keyup', function (event) {
       redirect(event.originalEvent);
    });
});

AJAX.registerTeardown('keyhandler.js', function () {
    $("th.draggable.column_heading.pointer.marker").die('click');
    $(document).die('keyup');
});
