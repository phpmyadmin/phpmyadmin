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
