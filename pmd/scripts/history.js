var history_array = []; // Global array to store history objects
var select_field = [];
var g_index;

/**
 * J-query function for panel, hides and shows toggle_container <div>
 *
 * @param	index	has value 1 or 0,decides wheter to hide toggle_container on load. 
**/

function panel(index) {
	if (!index) {
		$(".toggle_container").hide(); 
	}
		$("h2.tiger").click(function(){
		$(this).toggleClass("active").next().slideToggle("slow");
		});
}

/**
 * Sorts history_array[] first then generates the HTML code for history tab,clubbing all objects of same tables together
 * This function is called whenever changes are made in history_array[]
 *
 * @uses	and_or()
 * @uses	history_edit()
 * @uses	history_delete()
 * 	
 * @param	init	starting index of unsorted array   
 * @param	fianl	last index of unsorted array
 *
**/

function display(init,final) {
	 var str,i,j,k,sto;
	 for (i = init;i < final;i++) {
		sto = history_array[i];
		var temp = history_array[i].get_tab() + '.' + history_array[i].get_obj_no();
		for(j = 0;j < i;j++){
			if(temp > (history_array[j].get_tab() + '.' + history_array[j].get_obj_no())) {
				for(k = i;k > j;k--) {
					history_array[k] = history_array[k-1];
				}
				history_array[j] = sto;
				break;
			}
		}
	 }
	 str =''; // string to store Html code for history tab
	 for ( var i=0; i < history_array.length; i++){
		var temp = history_array[i].get_tab() + '.' + history_array[i].get_obj_no();
		str += '<h2 class="tiger"><a href="#">' + temp + '</a></h2>';  				
		str += '<div class="toggle_container">\n';
		while((history_array[i].get_tab() + '.' + history_array[i].get_obj_no()) == temp) {
			str +='<div class="block"> <table width ="250">';
			str += '<thead><tr><td>';
			if(history_array[i].get_and_or()){
				str +='<img src="pmd/images/or_icon.png" onclick="and_or('+i+')" title="OR"/></td>';
			}
			else {
				str +='<img src="pmd/images/and_icon.png" onclick="and_or('+i+')" title="AND"/></td>';
			}
			str +='<td style="padding-left: 5px;" align="right"><img src="./themes/original/img/b_sbrowse.png" title="column name"/></td><td width="175" 	  style="padding-left: 5px">' + history_array[i].get_column_name();
			if (history_array[i].get_type() == "GroupBy" || history_array[i].get_type() == "OrderBy") {
				str += '</td><td align="center"><img src="themes/original/img/b_info.png" title="'+detail(i)+'"/><td title="' + detail(i) +'">' + history_array[i].get_type() + '</td></td><td onmouseover="this.className=\'history_table\';" onmouseout="this.className=\'history_table2\'" onclick=history_delete('+ i +')><img src="themes/original/img/b_drop.png" title="Delete"></td></tr></thead>';
			}
			else {
				str += '</td><td align="center"><img src="themes/original/img/b_info.png" title="'+detail(i)+'"/></td><td title="' + detail(i) +'">' + history_array[i].get_type() + '</td><td <td onmouseover="this.className=\'history_table\';" onmouseout="this.className=\'history_table2\'" onclick=history_edit('+ i +')><img src="themes/original/img/b_edit.png" title="Edit"/></td><td onmouseover="this.className=\'history_table\';" onmouseout="this.className=\'history_table2\'" onclick=history_delete('+ i +')><img src="themes/original/img/b_drop.png" title="Delete"></td></tr></thead>'; 
			}
			i++;
			if(i >= history_array.length) {
				break;
			}
			str += '</table></div><br/>';
		}
		i--;
	 	str += '</div><br/>';
	 }
	 return str;
}

/**
 * To change And/Or relation in history tab 
 * 
 * @uses	panel()
 *
 * @param	index	index of history_array where change is to be made
 *
**/

function and_or(index) {
	if (history_array[index].get_and_or()) {
		history_array[index].set_and_or(0);
	}
	else {
		history_array[index].set_and_or(1);
	}
	var existingDiv = document.getElementById('ab');
	existingDiv.innerHTML = display(0,0);
	panel(1);
}

/**
 * To display details of obects(where,rename,aggregate,groupby,orderby)
 * 
 * @param	index	index of history_array where change is to be made
 *
**/

function detail (index) {
	var type = history_array[index].get_type();
	var str;
	if (type == "Where") {
		str = 'Where ' + history_array[index].get_column_name() + history_array[index].get_obj().getrelation_operator() + history_array[index].get_obj().getquery();
	}
	if (type == "Rename") {
		str = 'Rename ' + history_array[index].get_column_name() + ' To ' + history_array[index].get_obj().getrename_to();
	}
	if (type == "Aggregate") {
		str = 'Select ' + history_array[index].get_obj().get_operator() + '( ' + history_array[index].get_column_name() + ' )';
	}
	if (type == "GroupBy") {
		str = 'GroupBy ' + history_array[index].get_column_name() ;
	}
	if (type == "OrderBy") {
		str = 'OrderBy ' + history_array[index].get_column_name() ;
	}
	return str;
}

/**
 * Deletes entry in history_array
 *
 * @uses	panel()
 * @uses	display()
 * @param	index	index of history_array[] which is to be deleted
 *
**/

function history_delete(index) {
	history_array.splice(index,1);
	var existingDiv = document.getElementById('ab');
	existingDiv.innerHTML = display(0,0);
	panel(1);
}

/**
 * To show where,rename,aggregate forms to edit a object
 * 
 * @param	index	index of history_array where change is to be made
 *
**/

function history_edit(index) {
	g_index = index;
	var type = history_array[index].get_type();
	if (type == "Where") {
		document.getElementById('eQuery').value = history_array[index].get_obj().getquery();
		document.getElementById('erel_opt').value = history_array[index].get_obj().getrelation_operator();
		document.getElementById('query_where').style.left =  '230px';
     	document.getElementById('query_where').style.top  = '330px';
		document.getElementById('query_where').style.position  = 'absolute';
		document.getElementById('query_where').style.zIndex = '9';
		document.getElementById('query_where').style.visibility = 'visible';
	}
	if (type == "Rename") {
     	document.getElementById('query_rename_to').style.left =  '230px';
     	document.getElementById('query_rename_to').style.top  = '330px';
		document.getElementById('query_rename_to').style.position  = 'absolute';
		document.getElementById('query_rename_to').style.zIndex = '9';
		document.getElementById('query_rename_to').style.visibility = 'visible';
	}
	if (type == "Aggregate") {
		document.getElementById('query_Aggregate').style.left = '530px';
     	document.getElementById('query_Aggregate').style.top  = '130px';
		document.getElementById('query_Aggregate').style.position  = 'absolute';
		document.getElementById('query_Aggregate').style.zIndex = '9';
		document.getElementById('query_Aggregate').style.visibility = 'visible';
	}
}

/**
 * Make changes in history_array when Edit is clicked
 * 
 * @uses	panel()
 * @uses	display()
 *
 * @param	index	index of history_array where change is to be made
**/

function edit(type) {
	if (type == "Rename") {
		if (document.getElementById('e_rename').value != "") {
			history_array[g_index].get_obj().setrename_to(document.getElementById('e_rename').value);
			document.getElementById('e_rename').value = "";
		}
		document.getElementById('query_rename_to').style.visibility = 'hidden';
	}
	if (type == "Aggregate") {
		if (document.getElementById('e_operator').value != '---') {
			history_array[g_index].get_obj().set_operator(document.getElementById('e_operator').value);
			document.getElementById('e_operator').value = '---';
		}
		document.getElementById('query_Aggregate').style.visibility = 'hidden';
	}
	if (type == "Where") {
		if (document.getElementById('erel_opt').value != '--' && document.getElementById('eQuery').value !="") {
			history_array[g_index].get_obj().setquery(document.getElementById('eQuery').value);
			history_array[g_index].get_obj().setrelation_operator(document.getElementById('erel_opt').value);
		}
		document.getElementById('query_where').style.visibility = 'hidden';
	}
	var existingDiv = document.getElementById('ab');
	existingDiv.innerHTML = display(0,0);
	panel(1);
}

/**
 * history object closure  
 *
 * @param	ncolumn_name	name of the column on which conditions are put
 * @param	nobj			object details(where,rename,orderby,groupby,aggregate)
 * @param	ntab			table name of the column on which conditions are applied
 * @param	nobj_no			object no used for inner join
 * @param	ntype			type of object
 *
**/

function history(ncolumn_name,nobj,ntab,nobj_no,ntype) {
	var and_or;
	var obj;
	var tab;
	var column_name;
	var obj_no;
	var type;
	this.set_column_name = function (ncolumn_name) {
		column_name = ncolumn_name;
	};
	this.get_column_name = function() {
		return column_name;
	};
	this.set_and_or = function(nand_or) {
		and_or = nand_or;
	};
	this.get_and_or = function() {
		return and_or;
	}
	this.get_relation = function() {
		return and_or;
	};
	this.set_obj = function(nobj) {
		obj = nobj;
	};
	this.get_obj = function() {
		return obj;
	};
	this.set_tab = function(ntab) {
		tab = ntab;
	};
	this.get_tab = function() {
		return tab;
	};
	this.set_obj_no = function(nobj_no) {
		obj_no = nobj_no;
	};
	this.get_obj_no = function() {
		return obj_no;
	};
	this.set_type = function(ntype) {
		type = ntype;
	}
	this.get_type = function() {
		return type;
	}
	this.set_obj_no(nobj_no);
	this.set_tab(ntab);
	this.set_and_or(0);
	this.set_obj(nobj);
	this.set_column_name(ncolumn_name);
	this.set_type(ntype);
};

/**
 * where object closure, makes an object with all information of where
 *
 * @param	nrelation_operator	type of relation operator to be applied
 * @param	nquery				stores value of value/sub-query 
 *
**/

var where = function (nrelation_operator,nquery) {
	var relation_operator;
	var query;
	this.setrelation_operator = function(nrelation_operator) {
		relation_operator = nrelation_operator;
	};
	this.setquery = function(nquery) {
		query = nquery;
	};
	this.getquery = function() {
		return query;
	};
	this.getrelation_operator = function() {
		return relation_operator;
	};
	this.setquery(nquery);
	this.setrelation_operator(nrelation_operator);
};

/**
 * rename object closure,makes an object with all information of rename
 *
 * @param	nrename_to	new name information
 *
**/

var rename = function(nrename_to) {
	var rename_to;
	this.setrename_to = function(nrename_to) {
		rename_to = nrename_to;
	};
	this.getrename_to =function() {
		return rename_to;
	};
	this.setrename_to(nrename_to);
};

/**
 * aggregate object closure
 *
 * @param	noperator	aggregte operator
 *
**/

var aggregate = function(noperator) {
	var operator;
	this.set_operator = function(noperator) {
		operator = noperator;
	};
	this.get_operator = function() {
		return operator;
	};
	this.set_operator(noperator);
};

function build_query() {
	var q_select = "SELECT ";
	var temp;
	for(i = 0;i < select_field.length; i++) {
		temp = check_aggregate(select_field[i]);
		if (temp != "") {
			q_select += temp;
			temp = check_rename(select_field[i]);
			q_select += temp + ",";
		}
		else {
			temp = check_rename(select_field[i]);
			q_select += select_field[i] + temp +","; 
		}
	}
	q_select = q_select.substring(0,q_select.length - 1); //PDF_save()
	document.getElementById('hint').innerHTML = q_select;
	document.getElementById('hint').style.visibility = "visible";
}

function check_aggregate(id_this) {
	var i = 0;
	for(i;i < history_array.length;i++) {
		var temp = '\'' + history_array[i].get_tab() + '\'.\'' +history_array[i].get_column_name() +'\'';
		if(temp == id_this && history_array[i].get_type() == "Aggregate") {
			return history_array[i].get_obj().get_operator() + '(' + id_this +')';
		}
	}
	return "";
}

function check_rename(id_this) {
	var i = 0;
	for (i;i < history_array.length;i++) {
		var temp = '\'' + history_array[i].get_tab() + '\'.\'' +history_array[i].get_column_name() +'\'';
		if(temp == id_this && history_array[i].get_type() == "Rename") {
			return  " AS \'" + history_array[i].get_obj().getrename_to() +"\',";
		}
	}
	return "";
}