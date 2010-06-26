var history_array = [];
var tab_array = [];
function panel() {
	$(".toggle_container").hide(); 
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
		str +='<div class="toggle_container">\n';
		while((history_array[i].get_tab() + '.' + history_array[i].get_obj_no()) == temp) {
			str +='<div class="block"> <table>';
			str += '<thead><tr><td>' + history_array[i].get_column_name() + '<td></tr></thead><tr><td>';
			str += history_array[i].get_type() + '</td><td><img src=""/></td><td><img src="pmd/style/default/images/minus.png"></td></tr>';
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
		return relation_opearator;
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
