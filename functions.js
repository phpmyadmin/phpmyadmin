<!--

var isFormElementInRange;

function checkFormElementInRange (form, name, min, max ) {

    isFormElementInRange  =  true;
    var val = parseInt( eval( "form." + name + ".value"  ));

    if(isNaN(val)) {
        isFormElementInRange = false;
        return false;
    }
    if (val < min || val > max )  {
        alert( val +" is not a valid row number!" );
        isFormElementInRange = false;
        eval( "form."+ name + ".focus()");
        eval( "form."+ name + ".select()");
    }else {
    eval( "form."+ name + ".value = val" );
    }
    return true;
}       

//-->

