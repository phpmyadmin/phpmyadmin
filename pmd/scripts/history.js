var history_array = [];
 function history(ncolumn_name,nobj,ntab,nobj_no) {
	var and_or;
	var obj;
	var tab;
	var column_name;
	var obj_no;
	this.setcolumn_name = function (ncolumn_name) {
		column_name = ncolumn_name;
	};
	this.getcolumn_name = function() {
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
		return obj;
	};
	this.set_obj_no = function(nobj_no) {
		obj_no = nobj_no;
	};
	this.get_obj_no = function() {
		return obj_no;
	};

	this.set_obj_no(nobj_no);
	this.set_tab(ntab);
	this.set_and_or(0);
	this.set_obj(nobj);
	this.setcolumn_name(ncolumn_name);
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
	this.set_operator(noperator);
};
