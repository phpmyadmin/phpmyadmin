
/**
 *functions for find and replace feature
 *
 *
 * @requires    jQuery
 *
 */

/**
 * jqery function for hilighting a range of a text area
 * 
 */
$.fn.selectRange = function(start, end) {
    return this.each(function() {
        if (this.setSelectionRange) {
            this.focus();
            this.setSelectionRange(start, end);
        } else if (this.createTextRange) {
            var range = this.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    });
};

/*
*for stoping the execution of a function
*at any moment
*/
function exit(){
  throw "";
}  

/*
*for getting find option
*
*@return string: 'bystring', 'byword', 'bycolumn'
*/
function getOption(){
	return $('input[name=option]:checked', '#find_replace_form').val();
}

/*
*for getting column name checked
*
*@return string: checked column name in radio buttons
*/
function getColumn(){
	var column = $('input[name=column]:checked', '#find_replace_form').val();
	if (column==null){
		alert("please select a column first");
		return false;
	}
	return column;
}


/*
*for getting column number checked
*
*@return int: checked column number in radio buttons
*/
function getClmnNo(){
	var count;
	count=1;
	$('input[name*="column"]').each(function() {
		if ($(this).val()==getColumn()){
			return false;
		}
		else if(count>getClmnCount()){
			return false;
		}
		else{
			count++;
		}
	});
	if (count>getClmnCount())
		return null;
	else
		return count;
}

/*
*for getting no of columns
*
*@return int: no of columns of a table
*/
function getClmnCount(){
	return $('input[name*="column"]').length;
}

/*
*for checking if option and column are set
*at radio buttons
*
*@return bool
*/
function isAllSet(){
	if(!getOption() || !getColumn() || !getClmnCount())
		return false;
	return true;
}



/*
*for getting all the row objects
*related to option, column checked
*and the text in the "find what" field
*
*@return array of jquery $(type).val() objects
*/
function getRows(){
	var correntClmnNo;
	var values=new Array();
	var findwhat=$('#find_text').val();
	var words;
	var id_values;
	var temp;
	$('.textfield,textarea').each(function() {
		
		temp=($(this).attr('id'));
		if (temp!=null){
			id_values=temp.split('_');
			correntClmnNo=parseInt(id_values[1])%getClmnCount();
			if (correntClmnNo==0){
				correntClmnNo=getClmnCount();
			}
		}
		if (temp==null ||(id_values[0]).indexOf("field")==(-1) || id_values[2]!='3');
		else if (getOption()=='bycolumn' && $(this).val()==findwhat && correntClmnNo==getClmnNo()){
				values[values.length]=$(this);
		}
		
		else if (getOption()=='byword' && correntClmnNo==getClmnNo()){
			words=($(this).val()).split(' ');
			if (words.indexOf(findwhat)!=(-1)){
				values[values.length]=$(this);
			}
		}
		
		else if (getOption()=='bystring' && correntClmnNo==getClmnNo()){
			if (($(this).val()).indexOf(findwhat)!=(-1)){
				values[values.length]=$(this);
			}
		}
	});
	return values;
}


/*
*for printing count when user click on "count" button
*
*/
function countNow(){
	if (countEntries()!==false)
		alert( countEntries() +" entrie(s) found");
}

/*
*counting the text in "find" text field
*
*/
function countEntries(){
		
		if (!isAllSet())
			return false;
		
		if (getOption()=='bycolumn'){
			count=getRows().length;
		}
		
		else if (getOption()=='byword'){
			var findwhat=$('#find_text').val();
			var count=0;
			$(getRows()).each(function() {
				var words=($(this).val()).split(' ');
				for (var i=0; i<words.length; i++) {
					if (words[i]==findwhat)
						count++;
				}
			});
		}
		
		else if (getOption()=='bystring'){
			var findwhat=$('#find_text').val();
			var count=0;
			$(getRows()).each(function() {
				for(var i=0; i<($(this).val()).length; i++){
					if (($(this).val()).indexOf(findwhat,i)==i)
						count++;
				}
				
			});
		}
		
		
		return count;
	}


/*
* called by browseClmn() function
*
* searching in a single row for a value from end to start
*
* @param jquery $(type).val() object
* @return bool: false if nothing more to browse
*/
function browseRowUp(obj){
	var findwhat=$('#find_text').val();
	if (($(obj).attr('id')).indexOf('[hit]')==(-1)){
		$(obj).attr('id','[hit]'+$(obj).attr('id'));
	}
	
	var lastCurserPos=(($(obj).attr('id')).split('[hit]')[0]);
	if (lastCurserPos=="")
		lastCurserPos=($(obj).val()).length;
	lastCurserPos=parseInt(lastCurserPos);
	
	var count=0;
	var indexStart=0;
	var nextIndexStart=-1;
	var nextIndexEnd;
	
	if (getOption()=='byword'){
		var words=($(obj).val()).split(' ');
		for(var i=0; i<words.length; i++){
			if (indexStart>=lastCurserPos-1)
				break;
			indexStart=indexStart+words[i].length;
			if(i!=0)
				indexStart++;
			
			if (words[i]==findwhat){
					nextIndexEnd=indexStart;
					nextIndexStart=indexStart-words[i].length;
					
			}
		}
	}
	
	else if (getOption()=='bystring'){
		var value=($(obj).val());
		indexStart=-1;
		var temp=-1;
		var noOfEntries=countEntries();
		for(var i=0; i<=noOfEntries; i++){
			
			indexStart=value.indexOf(findwhat,indexStart+1);
			if (indexStart>=lastCurserPos || indexStart==(-1)){
				nextIndexStart=temp;
				nextIndexEnd=temp+findwhat.length;
				break;
			}
				
			temp=indexStart;
			
		}
	}
	
	if (nextIndexStart==-1)
		return false;
	else{
		var temp=(($(obj).attr('id')).split('[hit]')[1]);
		$(obj).attr('id',nextIndexStart+'[hit]'+temp);
		$(obj).selectRange(nextIndexStart,nextIndexEnd);
		exit();
	}
}


/*
* called by browseClmn() function
*
* searching in a single row for a value from start to end
*
* @param jquery $(type).val() object
* @return bool: false if nothing more to browse
*/
function browseRowDown(obj){
	var findwhat=$('#find_text').val();
	if (($(obj).attr('id')).indexOf('[hit]')==(-1)){
		$(obj).attr('id','[hit]'+$(obj).attr('id'));
	}
	
	var lastCurserPos=(($(obj).attr('id')).split('[hit]')[0]);
	if (lastCurserPos=="")
		lastCurserPos=-1;
	lastCurserPos=parseInt(lastCurserPos);
	var count=0;
	var indexStart=0;
	var nextIndexStart=-1;
	var nextIndexEnd;
	var foundLast=false;
	
	if (getOption()=='byword'){
		var words=($(obj).val()).split(' ');
		for(var i=0; i<words.length; i++){
			
			if (words[i]==findwhat && indexStart>lastCurserPos){
				if(i!=0)
					indexStart++;
				nextIndexStart=indexStart;
				nextIndexEnd=nextIndexStart+words[i].length;
				break;
			}
				indexStart=indexStart+words[i].length;
				if(i!=0)
					indexStart++;	
				
		}
	}
	
	else if (getOption()=='bystring'){
		var value=($(obj).val());
		indexStart=-1;
		var noOfEntries=countEntries();
		for(var i=0; i<noOfEntries; i++){
			indexStart=value.indexOf(findwhat,indexStart+1);
			
			if (indexStart>lastCurserPos){
				nextIndexStart=indexStart;
				nextIndexEnd=indexStart+findwhat.length;
				break;
			}
		}
	}
	
	if (nextIndexStart==-1)
		return false;
	else{
		var temp=(($(obj).attr('id')).split('[hit]')[1]);
		$(obj).attr('id',nextIndexStart+'[hit]'+temp);
		$(obj).selectRange(nextIndexStart,nextIndexEnd);
		exit();
	}
	
}

/*
* called by goround() function
* when a row is to be searched for a value
*
* @param jquery $(type).val() object, String: 'up', 'down'
*/
function browseClmn(obj,directn){
	if (directn=='up'){
			browseRowUp(obj);
		}
	else if (directn='down')
		browseRowDown(obj);
}


/*
* called by goUp() and goDown() function
* when a column is to be searched for a value
*
* @param jquery $(type).val() object, String: 'up', 'down'
*/
function goround(values, directn){
			var findwhat=$('#find_text').val();
			var foundLast=false;
			var temp;
			$(values).each(function() {
				
				if (($(this).attr('id')).indexOf('[hit]')!=(-1)){
					temp=($(this).attr('id')).split('[hit]');
					if (getOption()=='bycolumn'){
						$(this).attr('id',($(this).attr('id')).replace(temp[0]+'[hit]', ''));
						foundLast=true;
					}
					else if (getOption()=='byword' || getOption()=='bystring'){
						if (!browseClmn($(this),directn)){
							temp=($(this).attr('id')).split('[hit]');
							$(this).attr('id',($(this).attr('id')).replace(temp[0]+'[hit]', ''));
							foundLast=true;
						}
					}
				}
				
				else if(foundLast){
					$(this).attr('id','[hit]'+$(this).attr('id'));
					if (getOption()=='bycolumn'){
						$(this).select();
						return false;
					}
					else if (getOption()=='byword' || getOption()=='bystring'){
							browseClmn($(this),directn);
							return false;
					}
					
				}
				
			});
			
			if (!foundLast && $(values).length!=0){
				$(values[0]).attr('id','[hit]'+$(values[0]).attr('id'));
				if (getOption()=='bycolumn'){
					$(values[0]).select();
					return false;
				}
				
				else if (getOption()=='byword' || getOption()=='bystring'){
					browseClmn(values[0],directn);
					return false;
				}
			
			}
			
			else{
				alert ("reached the end");
			}
			
}

/*
*searching upwards of a column for a value
*
*when user clicks on Find:Up button
*/
function goUp(){

	if (!isAllSet())
		return false;
	goround(($(getRows()).get().reverse()),'up');
}


/*
*searching upwards of a column for a value
*
*when user clicks on Find:Down button
*/
function goDown(){

	if (!isAllSet())
		return false;
	goround(getRows(), 'down');
}


/*
* called by replaceOnce() function
* when a value in a row is to be replaced
*
* @param jquery $(type).val() object
*/
function replaceByword(obj){
	var findwhat=$('#find_text').val();
	var replacewith=$('#replace_text').val();
	var lastCurserPos=(($(obj).attr('id')).split('[hit]')[0]);
	lastCurserPos=parseInt(lastCurserPos);
	var count=0;
	
	if(getOption()=='byword'){
		var words=($(obj).val()).split(' ');
		for(var i=0; i<words.length; i++){
			if(words[i]==findwhat && count==lastCurserPos){
				words[i]=replacewith;
			}
			if (words[i]!=null)
				count=count+words[i].length;
			count++;
		}
		$(obj).val(words.join(" "));
	}
	
	else if (getOption()=='bystring'){
		var word1=($(obj).val()).substring(0,lastCurserPos);
		var word2=($(obj).val()).substring(lastCurserPos);
		word2=word2.replace(findwhat,replacewith);
		$(obj).val(word1+word2);
		
	}
	
	$(obj).selectRange(lastCurserPos,lastCurserPos+replacewith.length);
}


/*
* when a value in columns is to be replaced
* when a user clicks on replace_once button
*/
function replaceOnce(){
	
	if (!isAllSet())
		return false;
	var findwhat=$('#find_text').val();
	var replacewith=$('#replace_text').val();
	$(getRows()).each(function() {
		if (($(this).attr('id')).indexOf('[hit]')!=(-1)){
			if (getOption()=='bycolumn'){
				$(this).val(replacewith);
				$(this).select();
			}
			
			else if (getOption()=='byword' || getOption()=='bystring'){
			
				replaceByword($(this));
			}
		}
		
	});
	
	
}

/*
* when a value in columns is to be replaced
* when a user clicks on replace_all button
*/
function replaceAll(){

	if (!isAllSet())
		return false;
var findwhat=$('#find_text').val();
var replacewith=$('#replace_text').val();
var values= getRows();
var count=0;
	$(values).each(function() {
		if (getOption()=='bycolumn'){
			$(this).val(replacewith);
			count++;
		}
		
		else if (getOption()=='byword'){
			var words=($(this).val()).split(' ');
			for(var i=0; i<words.length; i++){
				if(words[i]==findwhat){
					words[i]=replacewith;
					count++;
				}
			}
			$(this).val(words.join(" "))
		}
		
		else if (getOption()=='bystring'){
		
			count=count+($(this).val()).match(new RegExp(findwhat, 'g')).length;
			$(this).val(($(this).val()).replace(new RegExp(findwhat, 'g'),replacewith));
		}
	});
	
alert("replaced: " + count + " entrie(s)");
}

/*
* toggle form from shown to hidden (minimize and maximize)
* and viseversa
*
*/
function minmax(){
var temp=$('#find_replace_div').slideToggle('slow', function() {
if ($('#find_replace_div').css("display") == "none"){
	var height=$('#find_replace_div').css("top");
    $('#find_replace_button').css("padding-top","0px");
	$('#change_row_dialog').css("padding-top","0px");
	$('#find_replace_button').html("find & replace: SHOW");
	}
else{
	var height=parseInt($('#find_replace_div').css("height"));
	height=height+parseInt($('#find_replace_div').css("top"));
	height=height+"px";
	$('#change_row_dialog').css("padding-top",height);
	$('#find_replace_button').html("find & replace: HIDE");
	}
});
}

//minimize when document is ready
$(document).ready(function() {
  minmax();
  
});

/*
* reseting all the values
* 
*
*/
function resetAll(){

if (!isAllSet())
	return false;
$('#insertForm').each (function(){
  this.reset();
});
}
