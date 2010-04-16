/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in table data manipulation pages
 *
 * @version $Id$
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
    
    dt.className="";
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

var day;
var month;
var year;
var hour;
var minute;
var second;
var clock_set = 0;

/**
 * Opens calendar window.
 *
 * @param   string      calendar.php parameters
 * @param   string      form name
 * @param   string      id of field name
 * @param   string      edit type - date/timestamp
 * @param   string      id of the corresponding checkbox for NULL 
 */
function openCalendar(params, form, field, type, fieldNull) {
    window.open("./calendar.php?" + params, "calendar", "width=400,height=200,status=yes");
    dateField = eval("document." + form + "." + field);
    dateType = type;
    if (fieldNull != '') {
        dateFieldNull = eval("document." + form + "." + fieldNull);
    }
}

/**
 * Formats number to two digits.
 *
 * @param   int number to format.
 * @param   string type of number
 */
function formatNum2(i, valtype) {
    f = (i < 10 ? '0' : '') + i;
    if (valtype && valtype != '') {
        switch(valtype) {
            case 'month':
                f = (f > 12 ? 12 : f);
                break;

            case 'day':
                f = (f > 31 ? 31 : f);
                break;

            case 'hour':
                f = (f > 24 ? 24 : f);
                break;

            default:
            case 'second':
            case 'minute':
                f = (f > 59 ? 59 : f);
                break;
        }
    }

    return f;
}

/**
 * Formats number to two digits.
 *
 * @param   int number to format.
 * @param   int default value
 * @param   string type of number
 */
function formatNum2d(i, default_v, valtype) {
    i = parseInt(i, 10);
    if (isNaN(i)) return default_v;
    return formatNum2(i, valtype)
}

/**
 * Formats number to four digits.
 *
 * @param   int number to format.
 */
function formatNum4(i) {
    i = parseInt(i, 10)
    return (i < 1000 ? i < 100 ? i < 10 ? '000' : '00' : '0' : '') + i;
}

/**
 * Initializes calendar window.
 */
function initCalendar() {
    if (!year && !month && !day) {
        /* Called for first time */
        if (window.opener.dateField.value) {
            value = window.opener.dateField.value;
            if (window.opener.dateType == 'datetime' || window.opener.dateType == 'date') {
                if (window.opener.dateType == 'datetime') {
                    parts   = value.split(' ');
                    value   = parts[0];

                    if (parts[1]) {
                        time    = parts[1].split(':');
                        hour    = parseInt(time[0],10);
                        minute  = parseInt(time[1],10);
                        second  = parseInt(time[2],10);
                    }
                }
                date        = value.split("-");
                day         = parseInt(date[2],10);
                month       = parseInt(date[1],10) - 1;
                year        = parseInt(date[0],10);
            } else {
                year        = parseInt(value.substr(0,4),10);
                month       = parseInt(value.substr(4,2),10) - 1;
                day         = parseInt(value.substr(6,2),10);
                hour        = parseInt(value.substr(8,2),10);
                minute      = parseInt(value.substr(10,2),10);
                second      = parseInt(value.substr(12,2),10);
            }
        }
        if (isNaN(year) || isNaN(month) || isNaN(day) || day == 0) {
            dt      = new Date();
            year    = dt.getFullYear();
            month   = dt.getMonth();
            day     = dt.getDate();
        }
        if (isNaN(hour) || isNaN(minute) || isNaN(second)) {
            dt      = new Date();
            hour    = dt.getHours();
            minute  = dt.getMinutes();
            second  = dt.getSeconds();
        }
    } else {
        /* Moving in calendar */
        if (month > 11) {
            month = 0;
            year++;
        }
        if (month < 0) {
            month = 11;
            year--;
        }
    }

    if (document.getElementById) {
        cnt = document.getElementById("calendar_data");
    } else if (document.all) {
        cnt = document.all["calendar_data"];
    }

    cnt.innerHTML = "";

    str = ""

    //heading table
    str += '<table class="calendar"><tr><th width="50%">';
    str += '<form method="NONE" onsubmit="return 0">';
    str += '<a href="javascript:month--; initCalendar();">&laquo;</a> ';
    str += '<select id="select_month" name="monthsel" onchange="month = parseInt(document.getElementById(\'select_month\').value); initCalendar();">';
    for (i =0; i < 12; i++) {
        if (i == month) selected = ' selected="selected"';
        else selected = '';
        str += '<option value="' + i + '" ' + selected + '>' + month_names[i] + '</option>';
    }
    str += '</select>';
    str += ' <a href="javascript:month++; initCalendar();">&raquo;</a>';
    str += '</form>';
    str += '</th><th width="50%">';
    str += '<form method="NONE" onsubmit="return 0">';
    str += '<a href="javascript:year--; initCalendar();">&laquo;</a> ';
    str += '<select id="select_year" name="yearsel" onchange="year = parseInt(document.getElementById(\'select_year\').value); initCalendar();">';
    for (i = year - 25; i < year + 25; i++) {
        if (i == year) selected = ' selected="selected"';
        else selected = '';
        str += '<option value="' + i + '" ' + selected + '>' + i + '</option>';
    }
    str += '</select>';
    str += ' <a href="javascript:year++; initCalendar();">&raquo;</a>';
    str += '</form>';
    str += '</th></tr></table>';

    str += '<table class="calendar"><tr>';
    for (i = 0; i < 7; i++) {
        str += "<th>" + day_names[i] + "</th>";
    }
    str += "</tr>";

    var firstDay = new Date(year, month, 1).getDay();
    var lastDay = new Date(year, month + 1, 0).getDate();

    str += "<tr>";

    dayInWeek = 0;
    for (i = 0; i < firstDay; i++) {
        str += "<td>&nbsp;</td>";
        dayInWeek++;
    }
    for (i = 1; i <= lastDay; i++) {
        if (dayInWeek == 7) {
            str += "</tr><tr>";
            dayInWeek = 0;
        }

        dispmonth = 1 + month;

        if (window.opener.dateType == 'datetime' || window.opener.dateType == 'date') {
            actVal = "" + formatNum4(year) + "-" + formatNum2(dispmonth, 'month') + "-" + formatNum2(i, 'day');
        } else {
            actVal = "" + formatNum4(year) + formatNum2(dispmonth, 'month') + formatNum2(i, 'day');
        }
        if (i == day) {
            style = ' class="selected"';
            current_date = actVal;
        } else {
            style = '';
        }
        str += "<td" + style + "><a href=\"javascript:returnDate('" + actVal + "');\">" + i + "</a></td>"
        dayInWeek++;
    }
    for (i = dayInWeek; i < 7; i++) {
        str += "<td>&nbsp;</td>";
    }

    str += "</tr></table>";

    cnt.innerHTML = str;

    // Should we handle time also?
    if (window.opener.dateType != 'date' && !clock_set) {

        if (document.getElementById) {
            cnt = document.getElementById("clock_data");
        } else if (document.all) {
            cnt = document.all["clock_data"];
        }

        str = '';
        init_hour = hour;
        init_minute = minute;
        init_second = second;
        str += '<fieldset>';
        str += '<form method="NONE" class="clock" onsubmit="returnDate(\'' + current_date + '\')">';
        str += '<input id="hour"    type="text" size="2" maxlength="2" onblur="this.value=formatNum2d(this.value, init_hour, \'hour\'); init_hour = this.value;" value="' + formatNum2(hour, 'hour') + '" />:';
        str += '<input id="minute"  type="text" size="2" maxlength="2" onblur="this.value=formatNum2d(this.value, init_minute, \'minute\'); init_minute = this.value;" value="' + formatNum2(minute, 'minute') + '" />:';
        str += '<input id="second"  type="text" size="2" maxlength="2" onblur="this.value=formatNum2d(this.value, init_second, \'second\'); init_second = this.value;" value="' + formatNum2(second, 'second') + '" />';
        str += '&nbsp;&nbsp;';
        str += '<input type="submit" value="' + submit_text + '"/>';
        str += '</form>';
        str += '</fieldset>';

        cnt.innerHTML = str;
        clock_set = 1;
    }

}

/**
 * Returns date from calendar.
 *
 * @param   string     date text
 */
function returnDate(d) {
    txt = d;
    if (window.opener.dateType != 'date') {
        // need to get time
        h = parseInt(document.getElementById('hour').value,10);
        m = parseInt(document.getElementById('minute').value,10);
        s = parseInt(document.getElementById('second').value,10);
        if (window.opener.dateType == 'datetime') {
            txt += ' ' + formatNum2(h, 'hour') + ':' + formatNum2(m, 'minute') + ':' + formatNum2(s, 'second');
        } else {
            // timestamp
            txt += formatNum2(h, 'hour') + formatNum2(m, 'minute') + formatNum2(s, 'second');
        }
    }

    window.opener.dateField.value = txt;
     window.opener.dateField.className='';
    if (typeof(window.opener.dateFieldNull) != 'undefined') {
        window.opener.dateFieldNull.checked = false;
    }
    window.close();
}
