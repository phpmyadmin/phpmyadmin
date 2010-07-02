var history_array = [];
var tab_array = [];
var g_index;
function panel(index) {
	if (!index) {
		$(".toggle_container").hide(); 
	}
		$("h2.tiger").click(function(){
		$(this).toggleClass("active").next().slideToggle("slow");
		});
}

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
	 str ='';
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
function history_delete(index) {
	history_array.splice(index,1);
	var existingDiv = document.getElementById('ab');
	existingDiv.innerHTML = display(0,0);
	panel(1);
}
 
function history_edit(index) {
	g_index = index;
	var type = history_array[index].get_type();
	if (type == "Where") {
		document.getElementById('eQuery').value = history_array[index].get_obj().getquery();
		document.getElementById('erel_opt').value = history_array[index].get_obj().getrelation_operator();
		document.getElementById('query_where').style.left =  '230px';
     	document.getElementById('query_where').style.top  = '330px';
		document.getElementById('query_where').style.visibility = 'visible';
	}
	if (type == "Rename") {
		//var left = screen.availWidth/2 ;
     	document.getElementById('query_rename_to').style.left =  '230px';
     	document.getElementById('query_rename_to').style.top  = '330px';
		document.getElementById('query_rename_to').style.visibility = 'visible';
	}
	if (type == "Aggregate") {
		var left = Glob_X - (document.getElementById('query_Aggregate').offsetWidth>>1);
     	document.getElementById('query_Aggregate').style.left = left + 'px';
     	document.getElementById('query_Aggregate').style.top  = (screen.height / 4) + 'px';
		document.getElementById('query_Aggregate').style.visibility = 'visible';
	}
}
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
			document.getElementById('eQuery').value = "";
			document.getElementById('erel_opt').value = '--';
		}
		document.getElementById('query_where').style.visibility = 'hidden';
	}
	var existingDiv = document.getElementById('ab');
	existingDiv.innerHTML = display(0,0);
	panel(1);
}
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
var aggregate = function(noperator) {
	var operator;
	this.set_operator = function(noperator) {
		operator=noperator;
	};
	this.get_operator = function() {
		return operator;
	};
	this.set_operator(noperator);
};
