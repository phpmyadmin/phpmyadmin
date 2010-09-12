/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in table data manipulation pages
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Modify from controls when the "NULL" checkbox is selected
 *
 * @param   string   the MySQL field type
 * @param   string   the urlencoded field name - OBSOLETE
 * @param   string   the md5 hashed field name
 * @param   string   the multi_edit row sequence number
 *
 * @return  boolean  always true
 */
function nullify(theType, urlField, md5Field, multi_edit)
{
    var rowForm = document.forms['insertForm'];

    if (typeof(rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']']) != 'undefined') {
        rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']'].selectedIndex = -1;
    }

    // "SET" field , "ENUM" field with more than 20 characters
    // or foreign key field (drop-down)
    if (theType == 1 || theType == 3 || theType == 4) {
        rowForm.elements['field_' + md5Field + multi_edit + '[]'].selectedIndex = -1;
    }
    // Other "ENUM" field
    else if (theType == 2) {
        var elts     = rowForm.elements['field_' + md5Field + multi_edit + '[]'];
        // when there is just one option in ENUM:
        if (elts.checked) {
            elts.checked = false;
        } else {
            var elts_cnt = elts.length;
            for (var i = 0; i < elts_cnt; i++ ) {
                elts[i].checked = false;
            } // end for

        } // end if
    }
    // foreign key field (with browsing icon for foreign values)
    else if (theType == 6) {
        rowForm.elements['field_' + md5Field + multi_edit + '[]'].value = '';
    }
    // Other field types
    else /*if (theType == 5)*/ {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function


/**
 * javascript DateTime format validation.
 * its used to prevent adding default (0000-00-00 00:00:00) to database when user enter wrong values
 * Start of validation part
 */
//function checks the number of days in febuary
function daysInFebruary (year){
    return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}
//function to convert single digit to double digit
function fractionReplace(num)
{
    num=parseInt(num);
    var res="00";
    switch(num)
    {
        case 1:res= "01";break;
        case 2:res= "02";break;
        case 3:res= "03";break;
        case 4:res= "04";break;
        case 5:res= "05";break;
        case 6:res= "06";break;
        case 7:res= "07";break;
        case 8:res= "08";break;
        case 9:res= "09";break;
        }
    return res;
}

/* function to check the validity of date
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2001-12-23
* 2) 2001-1-2
* 3) 02-12-23
* 4) And instead of using '-' the following punctuations can be used (+,.,*,^,@,/) All these are accepted by mysql as well. Therefore no issues
*/
function isDate(val,tmstmp)
{
    val=val.replace(/[.|*|^|+|//|@]/g,'-');
    var arrayVal=val.split("-");
    for(var a=0;a<arrayVal.length;a++)
    {
        if(arrayVal[a].length==1)
            arrayVal[a]=fractionReplace(arrayVal[a]);
    }
    val=arrayVal.join("-");
    var pos=2;
            dtexp=new RegExp(/^([0-9]{4})-(((01|03|05|07|08|10|12)-((0[0-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)-((0[0-9])|([1-2][0-9])|30)))$/);
        if(val.length==8)
        {
            dtexp=new RegExp(/^([0-9]{2})-(((01|03|05|07|08|10|12)-((0[0-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)-((0[0-9])|([1-2][0-9])|30)))$/);
            pos=0;
        }
        if(dtexp.test(val))
        {
            var month=parseInt(val.substring(pos+3,pos+5));
            var day=parseInt(val.substring(pos+6,pos+8));
            var year=parseInt(val.substring(0,pos+2));
            if(month==2&&day>daysInFebruary(year))
                return false;
            if(val.substring(0,pos+2).length==2)
            {
                if(val.substring(0,pos+2).length==2)
                    year=parseInt("20"+val.substring(0,pos+2));
                else
                    year=parseInt("19"+val.substring(0,pos+2));
            }
            if(tmstmp==true)
            {
                if(year<1978) return false;
                if(year>2038||(year>2037&&day>19&&month>=1)||(year>2037&&month>1)) return false;
                }
        }
        else
            return false;
        return true;
}

/* function to check the validity of time
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2:3:4
* 2) 2:23:43
*/
function isTime(val)
{
    var arrayVal=val.split(":");
    for(var a=0;a<arrayVal.length;a++)
    {
        if(arrayVal[a].length==1)
            arrayVal[a]=fractionReplace(arrayVal[a]);
    }
    val=arrayVal.join(":");
    tmexp=new RegExp(/^(([0-1][0-9])|(2[0-3])):((0[0-9])|([1-5][0-9])):((0[0-9])|([1-5][0-9]))$/);
        if(!tmexp.test(val))
            return false;
        return true;
}
//validate the datetime and integer
function Validator(urlField, multi_edit,theType){
    var rowForm = document.forms['insertForm'];
    var evt = window.event || arguments.callee.caller.arguments[0];
    var target = evt.target || evt.srcElement;
    unNullify(urlField, multi_edit);

    if(target.name.substring(0,6)=="fields")
    {
        var dt=rowForm.elements['fields[multi_edit][' + multi_edit + '][' + urlField + ']'];
        // validate for date time
        if(theType=="datetime"||theType=="time"||theType=="date"||theType=="timestamp")
        {
            if(theType=="date"){
                if(!isDate(dt.value))
                    {
                        dt.className="invalid_value";
                        return false;
                    }
            }
            else if(theType=="time")
            {
                if(!isTime(dt.value))
                {
                    dt.className="invalid_value";
                    return false;
                }
            }
            else if(theType=="datetime"||theType=="timestamp")
            {
                tmstmp=false;
                if(dt.value=="CURRENT_TIMESTAMP")
                {
                    dt.className="";
                    return true;
                }
                if(theType=="timestamp")
                {
                    tmstmp=true;
                }
                if(dt.value=="0000-00-00 00:00:00")
                    return true;
                var dv=dt.value.indexOf(" ");
                if(dv==-1)
                {
                    dt.className="invalid_value";
                    return false;
                }
                else
                {
                    if(!(isDate(dt.value.substring(0,dv),tmstmp)&&isTime(dt.value.substring(dv+1))))
                    {
                        dt.className="invalid_value";
                        return false;
                    }
                }
            }
        }
        //validate for integer type
        if(theType.substring(0,3)=="int"){

            if(isNaN(dt.value)){
                    dt.className="invalid_value";
                    return false;
            }
        }
    }
 }
 /* End of datetime validation*/

/**
 * Unchecks the "NULL" control when a function has been selected or a value
 * entered
 *
 * @param   string   the urlencoded field name
 * @param   string   the multi_edit row sequence number
 *
 * @return  boolean  always true
 */
function unNullify(urlField, multi_edit)
{
    var rowForm = document.forms['insertForm'];

    if (typeof(rowForm.elements['fields_null[multi_edit][' + multi_edit + '][' + urlField + ']']) != 'undefined') {
        rowForm.elements['fields_null[multi_edit][' + multi_edit + '][' + urlField + ']'].checked = false
    } // end if

    if (typeof(rowForm.elements['insert_ignore_' + multi_edit]) != 'undefined') {
        rowForm.elements['insert_ignore_' + multi_edit].checked = false
    } // end if

    return true;
} // end of the 'unNullify()' function

/**
 * Ajax handlers for Change Table page
 *
 * Actions Ajaxified here:
 * Submit Data to be inserted into the table
 * Restart insertion with 'N' rows.
 */
$(document).ready(function() {

    /**
     * Handles all current checkboxes for Null 
     * 
     */
    $('.checkbox_null').bind('click', function(e) {
            nullify(
                // use hidden fields populated by tbl_change.php
                $(this).siblings('.nullify_code').val(),
                $(this).closest('tr').find('input:hidden').first().val(), 
                $(this).siblings('.hashed_field').val(),
                $(this).siblings('.multi_edit').val()
            );
    });

    /**
     * Submission of data to be inserted into table
     * 
     * @uses    PMA_ajaxShowMessage()
     */
    $("#insertForm").live('submit', function(event) {

        /**
         * @var the_form    Object referring to the insertion form
         */
        var the_form = $(this);
        event.preventDefault();

        PMA_ajaxShowMessage();
        $(the_form).append('<input type="hidden" name="ajax_request" value="true" />');

        $.post($(the_form).attr('action'), $(the_form).serialize(), function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);

                $("#topmenucontainer")
                .next('div')
                .remove()
                .end()
                .after(data.sql_query);

                //Remove the empty notice div generated due to a NULL query passed to PMA_showMessage()
                var notice_class = $("#topmenucontainer").next("div").find('.notice');
                if($(notice_class).text() == '') {
                    $(notice_class).remove();
                }

                //Clear the data in the forms
                $(the_form).find('input:reset').trigger('click');
            }
            else {
                PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : "+data.error, "7000");
            }
        })
    }) // end submission of data to be inserted into table

    /**
     * Continue Insertion form
     */
    $("#insert_rows").live('change', function(event) {
        event.preventDefault();

        /**
         * @var curr_rows   Number of current insert rows already on page
         */
        var curr_rows = $(".insertRowTable").length;
        /**
         * @var target_rows Number of rows the user wants
         */
        var target_rows = $("#insert_rows").val();

        if(curr_rows < target_rows ) {
            while( curr_rows < target_rows ) {

                /**
                 * @var last_row    Object referring to the last row
                 */
                var last_row = $("#insertForm").find(".insertRowTable:last");

                //Clone the insert tables
                $(last_row)
                .clone()
                .insertBefore("#insertForm > fieldset")
                .find('input[name*=multi_edit],select[name*=multi_edit]')
                .each(function() {

                    /**
                     * Extract the index from the name attribute for all input/select fields and increment it
                     * name is of format funcs[multi_edit][10][<long random string of alphanum chars>]
                     */

                    /**
                     * @var this_name   String containing name of the input/select elements
                     */
                    var this_name = $(this).attr('name');
                    /** split {@link this_name} at [10], so we have the parts that can be concatenated later */
                    var name_parts = this_name.split(/\[\d+\]/);
                    /** extract the [10] from  {@link name_parts} */
                    var old_row_index_string = this_name.match(/\[\d+\]/)[0];
                    /** extract 10 - had to split into two steps to accomodate double digits */
                    var old_row_index = parseInt(old_row_index_string.match(/\d+/)[0]);

                    /** calculate next index i.e. 11 */
                    var new_row_index = old_row_index + 1;
                    /** generate the new name i.e. funcs[multi_edit][11][foobarbaz] */
                    var new_name = name_parts[0] + '[' + new_row_index + ']' + name_parts[1];

                    var hashed_field = name_parts[1].match(/\[(.+)\]/)[1];
                    $(this).attr('name', new_name);

                    $(this).filter('.textfield')
                        .attr('value', '')
                        .unbind('change')
                        .attr('onchange', null)
                        .bind('change', function(e) {
                            Validator(
                                hashed_field, 
                                new_row_index, 
                                $(this).closest('tr').find('span.column_type').html()
                                );
                        })
                        .end();

                    $(this).filter('.checkbox_null')
                        .bind('click', function(e) {
                                nullify(
                                    $(this).siblings('.nullify_code').val(),
                                    $(this).closest('tr').find('input:hidden').first().val(), 
                                    hashed_field, 
                                    '[multi_edit][' + new_row_index + ']'
                                    );
                        }) 
                        .end();

                });

                //Insert/Clone the ignore checkboxes
                if(curr_rows == 1 ) {
                    $('<input id="insert_ignore_1" type="checkbox" name="insert_ignore_1" checked="checked" />')
                    .insertBefore(".insertRowTable:last")
                    .after('<label for="insert_ignore_1">' + PMA_messages['strIgnore'] + '</label>');
                }
                else {

                    /**
                     * @var last_checkbox   Object reference to the last checkbox in #insertForm
                     */
                    var last_checkbox = $("#insertForm").children('input:checkbox:last');

                    /** name of {@link last_checkbox} */
                    var last_checkbox_name = $(last_checkbox).attr('name');
                    /** index of {@link last_checkbox} */
                    var last_checkbox_index = parseInt(last_checkbox_name.match(/\d+/));
                    /** name of new {@link last_checkbox} */
                    var new_name = last_checkbox_name.replace(/\d+/,last_checkbox_index+1);

                    $(last_checkbox)
                    .clone()
                    .attr({'id':new_name, 'name': new_name, 'checked': true})
                    .add('label[for^=insert_ignore]:last')
                    .clone()
                    .attr('for', new_name)
                    .before('<br />')
                    .insertBefore(".insertRowTable:last");
                }
                curr_rows++;
            }
        }
        else if( curr_rows > target_rows) {
            while(curr_rows > target_rows) {
                $("input[id^=insert_ignore]:last")
                .nextUntil("fieldset")
                .andSelf()
                .remove();
                curr_rows--;
            }
        }
    })
}, 'top.frame_content'); //end $(document).ready()
