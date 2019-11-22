/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in this file builds history tab and generates query.
  *
  * @requires    jQuery
  * @requires    moves.js
  * @version $Id$
  */

var history_array = []; // Global array to store history objects
var select_field = [];  // Global array to store informaation for columns which are used in select clause
var g_index;
var vqb_editor = null;

/**
 * To display details of objects(where,rename,Having,aggregate,groupby,orderby,having)
 *
 * @param index index of history_array where change is to be made
 *
**/

function detail (index) {
    var type = history_array[index].get_type();
    var str;
    if (type === 'Where') {
        str = 'Where ' + history_array[index].get_column_name() + history_array[index].get_obj().getrelation_operator() + history_array[index].get_obj().getquery();
    }
    if (type === 'Rename') {
        str = 'Rename ' + history_array[index].get_column_name() + ' To ' + history_array[index].get_obj().getrename_to();
    }
    if (type === 'Aggregate') {
        str = 'Select ' + history_array[index].get_obj().get_operator() + '( ' + history_array[index].get_column_name() + ' )';
    }
    if (type === 'GroupBy') {
        str = 'GroupBy ' + history_array[index].get_column_name();
    }
    if (type === 'OrderBy') {
        str = 'OrderBy ' + history_array[index].get_column_name() + ' ' + history_array[index].get_obj().get_order();
    }
    if (type === 'Having') {
        str = 'Having ';
        if (history_array[index].get_obj().get_operator() !== 'None') {
            str += history_array[index].get_obj().get_operator() + '( ' + history_array[index].get_column_name() + ' )';
            str += history_array[index].get_obj().getrelation_operator() + history_array[index].get_obj().getquery();
        } else {
            str = 'Having ' + history_array[index].get_column_name() + history_array[index].get_obj().getrelation_operator() + history_array[index].get_obj().getquery();
        }
    }
    return str;
}

/**
 * Sorts history_array[] first,using table name as the key and then generates the HTML code for history tab,
 * clubbing all objects of same tables together
 * This function is called whenever changes are made in history_array[]
 *
 *
 * @param {int}  init starting index of unsorted array
 * @param {int} finit   last index of unsorted array
 *
**/

function display (init, finit) {
    var str;
    var i;
    var j;
    var k;
    var sto;
    var temp;
    // this part sorts the history array based on table name,this is needed for clubbing all object of same name together.
    for (i = init; i < finit; i++) {
        sto = history_array[i];
        temp = history_array[i].get_tab();// + '.' + history_array[i].get_obj_no(); for Self JOINS
        for (j = 0; j < i; j++) {
            if (temp > (history_array[j].get_tab())) {// + '.' + history_array[j].get_obj_no())) { //for Self JOINS
                for (k = i; k > j; k--) {
                    history_array[k] = history_array[k - 1];
                }
                history_array[j] = sto;
                break;
            }
        }
    }
    // this part generates HTML code for history tab.adds delete,edit,and/or and detail features with objects.
    str = ''; // string to store Html code for history tab
    for (i = 0; i < history_array.length; i++) {
        temp = history_array[i].get_tab(); // + '.' + history_array[i].get_obj_no(); for Self JOIN
        str += '<h3 class="tiger"><a href="#">' + temp + '</a></h3>';
        str += '<div class="toggle_container">\n';
        while ((history_array[i].get_tab()) === temp) { // + '.' + history_array[i].get_obj_no()) === temp) {
            str += '<div class="block"> <table width ="250">';
            str += '<thead><tr><td>';
            if (history_array[i].get_and_or()) {
                str += '<img src="' + pmaThemeImage + 'designer/or_icon.png" onclick="and_or(' + i + ')" title="OR"/></td>';
            } else {
                str += '<img src="' + pmaThemeImage + 'designer/and_icon.png" onclick="and_or(' + i + ')" title="AND"/></td>';
            }
            str += '<td style="padding-left: 5px;" class="right">' + PMA_getImage('b_sbrowse', PMA_messages.strColumnName) + '</td>' +
                '<td width="175" style="padding-left: 5px">' + $('<div/>').text(history_array[i].get_column_name()).html() + '<td>';
            if (history_array[i].get_type() === 'GroupBy' || history_array[i].get_type() === 'OrderBy') {
                var detailDesc = $('<div/>').text(detail(i)).html();
                str += '<td class="center">' + PMA_getImage('s_info', detail(i)) + '</td>' +
                    '<td title="' + detailDesc + '">' + history_array[i].get_type() + '</td>' +
                    '<td onclick=history_delete(' + i + ')>' + PMA_getImage('b_drop', PMA_messages.strDelete) + '</td>';
            } else {
                var detailDesc = $('<div/>').text(detail(i)).html();
                str += '<td class="center">' + PMA_getImage('s_info', detail(i)) + '</td>' +
                    '<td title="' + detailDesc + '">' + history_array[i].get_type() + '</td>' +
                    '<td onclick=history_edit(' + i + ')>' + PMA_getImage('b_edit', PMA_messages.strEdit) + '</td>' +
                    '<td onclick=history_delete(' + i + ')>' + PMA_getImage('b_drop', PMA_messages.strDelete) + '</td>';
            }
            str += '</tr></thead>';
            i++;
            if (i >= history_array.length) {
                break;
            }
            str += '</table></div>';
        }
        i--;
        str += '</div>';
    }
    return str;
}

/**
 * To change And/Or relation in history tab
 *
 *
 * @param {int} index of history_array where change is to be made
 *
**/

function and_or (index) {
    if (history_array[index].get_and_or()) {
        history_array[index].set_and_or(0);
    } else {
        history_array[index].set_and_or(1);
    }
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = display(0, 0);
    $('#ab').accordion('refresh');
}

/**
 * Deletes entry in history_array
 *
 * @param index index of history_array[] which is to be deleted
 *
**/

function history_delete (index) {
    for (var k = 0; k < from_array.length; k++) {
        if (from_array[k] === history_array[index].get_tab()) {
            from_array.splice(k, 1);
            break;
        }
    }
    history_array.splice(index, 1);
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = display(0, 0);
    $('#ab').accordion('refresh');
}

/**
 * To show where,rename,aggregate,having forms to edit a object
 *
 * @param{int} index index of history_array where change is to be made
 *
**/

function history_edit (index) {
    g_index = index;
    var type = history_array[index].get_type();
    if (type === 'Where') {
        document.getElementById('eQuery').value = history_array[index].get_obj().getquery();
        document.getElementById('erel_opt').value = history_array[index].get_obj().getrelation_operator();
        document.getElementById('query_where').style.left =  '530px';
        document.getElementById('query_where').style.top  = '130px';
        document.getElementById('query_where').style.position  = 'absolute';
        document.getElementById('query_where').style.zIndex = '103';
        document.getElementById('query_where').style.visibility = 'visible';
        document.getElementById('query_where').style.display = 'block';
    }
    if (type === 'Having') {
        document.getElementById('hQuery').value = history_array[index].get_obj().getquery();
        document.getElementById('hrel_opt').value = history_array[index].get_obj().getrelation_operator();
        document.getElementById('hoperator').value = history_array[index].get_obj().get_operator();
        document.getElementById('query_having').style.left =  '530px';
        document.getElementById('query_having').style.top  = '130px';
        document.getElementById('query_having').style.position  = 'absolute';
        document.getElementById('query_having').style.zIndex = '103';
        document.getElementById('query_having').style.visibility = 'visible';
        document.getElementById('query_having').style.display = 'block';
    }
    if (type === 'Rename') {
        document.getElementById('e_rename').value = history_array[index].get_obj().getrename_to();
        document.getElementById('query_rename_to').style.left =  '530px';
        document.getElementById('query_rename_to').style.top  = '130px';
        document.getElementById('query_rename_to').style.position  = 'absolute';
        document.getElementById('query_rename_to').style.zIndex = '103';
        document.getElementById('query_rename_to').style.visibility = 'visible';
        document.getElementById('query_rename_to').style.display = 'block';
    }
    if (type === 'Aggregate') {
        document.getElementById('e_operator').value = history_array[index].get_obj().get_operator();
        document.getElementById('query_Aggregate').style.left = '530px';
        document.getElementById('query_Aggregate').style.top  = '130px';
        document.getElementById('query_Aggregate').style.position  = 'absolute';
        document.getElementById('query_Aggregate').style.zIndex = '103';
        document.getElementById('query_Aggregate').style.visibility = 'visible';
        document.getElementById('query_Aggregate').style.display = 'block';
    }
}

/**
 * Make changes in history_array when Edit button is clicked
 * checks for the type of object and then sets the new value
 *
 * @param index index of history_array where change is to be made
**/

function edit (type) {
    if (type === 'Rename') {
        if (document.getElementById('e_rename').value !== '') {
            history_array[g_index].get_obj().setrename_to(document.getElementById('e_rename').value);
            document.getElementById('e_rename').value = '';
        }
        document.getElementById('query_rename_to').style.visibility = 'hidden';
    }
    if (type === 'Aggregate') {
        if (document.getElementById('e_operator').value !== '---') {
            history_array[g_index].get_obj().set_operator(document.getElementById('e_operator').value);
            document.getElementById('e_operator').value = '---';
        }
        document.getElementById('query_Aggregate').style.visibility = 'hidden';
    }
    if (type === 'Where') {
        if (document.getElementById('erel_opt').value !== '--' && document.getElementById('eQuery').value !== '') {
            history_array[g_index].get_obj().setquery(document.getElementById('eQuery').value);
            history_array[g_index].get_obj().setrelation_operator(document.getElementById('erel_opt').value);
        }
        document.getElementById('query_where').style.visibility = 'hidden';
    }
    if (type === 'Having') {
        if (document.getElementById('hrel_opt').value !== '--' && document.getElementById('hQuery').value !== '') {
            history_array[g_index].get_obj().setquery(document.getElementById('hQuery').value);
            history_array[g_index].get_obj().setrelation_operator(document.getElementById('hrel_opt').value);
            history_array[g_index].get_obj().set_operator(document.getElementById('hoperator').value);
        }
        document.getElementById('query_having').style.visibility = 'hidden';
    }
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = display(0, 0);
    $('#ab').accordion('refresh');
}

/**
 * history object closure
 *
 * @param ncolumn_name  name of the column on which conditions are put
 * @param nobj          object details(where,rename,orderby,groupby,aggregate)
 * @param ntab          table name of the column on which conditions are applied
 * @param nobj_no       object no used for inner join
 * @param ntype         type of object
 *
**/

function history_obj (ncolumn_name, nobj, ntab, nobj_no, ntype) {
    var and_or;
    var obj;
    var tab;
    var column_name;
    var obj_no;
    var type;
    this.set_column_name = function (ncolumn_name) {
        column_name = ncolumn_name;
    };
    this.get_column_name = function () {
        return column_name;
    };
    this.set_and_or = function (nand_or) {
        and_or = nand_or;
    };
    this.get_and_or = function () {
        return and_or;
    };
    this.get_relation = function () {
        return and_or;
    };
    this.set_obj = function (nobj) {
        obj = nobj;
    };
    this.get_obj = function () {
        return obj;
    };
    this.set_tab = function (ntab) {
        tab = ntab;
    };
    this.get_tab = function () {
        return tab;
    };
    this.set_obj_no = function (nobj_no) {
        obj_no = nobj_no;
    };
    this.get_obj_no = function () {
        return obj_no;
    };
    this.set_type = function (ntype) {
        type = ntype;
    };
    this.get_type = function () {
        return type;
    };
    this.set_obj_no(nobj_no);
    this.set_tab(ntab);
    this.set_and_or(0);
    this.set_obj(nobj);
    this.set_column_name(ncolumn_name);
    this.set_type(ntype);
}

/**
 * where object closure, makes an object with all information of where
 *
 * @param nrelation_operator type of relation operator to be applied
 * @param nquery             stores value of value/sub-query
 *
**/


var where = function (nrelation_operator, nquery) {
    var relation_operator;
    var query;
    this.setrelation_operator = function (nrelation_operator) {
        relation_operator = nrelation_operator;
    };
    this.setquery = function (nquery) {
        query = nquery;
    };
    this.getquery = function () {
        return query;
    };
    this.getrelation_operator = function () {
        return relation_operator;
    };
    this.setquery(nquery);
    this.setrelation_operator(nrelation_operator);
};

/**
 * Orderby object closure
 *
 * @param norder order, ASC or DESC
 */
var orderby = function (norder) {
    var order;
    this.set_order = function (norder) {
        order = norder;
    };
    this.get_order = function () {
        return order;
    };
    this.set_order(norder);
};

/**
 * Having object closure, makes an object with all information of where
 *
 * @param nrelation_operator type of relation operator to be applied
 * @param nquery             stores value of value/sub-query
 * @param noperator          operator
**/

var having = function (nrelation_operator, nquery, noperator) {
    var relation_operator;
    var query;
    var operator;
    this.set_operator = function (noperator) {
        operator = noperator;
    };
    this.setrelation_operator = function (nrelation_operator) {
        relation_operator = nrelation_operator;
    };
    this.setquery = function (nquery) {
        query = nquery;
    };
    this.getquery = function () {
        return query;
    };
    this.getrelation_operator = function () {
        return relation_operator;
    };
    this.get_operator = function () {
        return operator;
    };
    this.setquery(nquery);
    this.setrelation_operator(nrelation_operator);
    this.set_operator(noperator);
};

/**
 * rename object closure,makes an object with all information of rename
 *
 * @param nrename_to new name information
 *
**/

var rename = function (nrename_to) {
    var rename_to;
    this.setrename_to = function (nrename_to) {
        rename_to = nrename_to;
    };
    this.getrename_to = function () {
        return rename_to;
    };
    this.setrename_to(nrename_to);
};

/**
 * aggregate object closure
 *
 * @param noperator aggregte operator
 *
**/

var aggregate = function (noperator) {
    var operator;
    this.set_operator = function (noperator) {
        operator = noperator;
    };
    this.get_operator = function () {
        return operator;
    };
    this.set_operator(noperator);
};

/**
 * This function returns unique element from an array
 *
 * @param arrayName array from which duplicate elem are to be removed.
 * @return unique array
 */

function unique (arrayName) {
    var newArray = [];
    uniquetop:
    for (var i = 0; i < arrayName.length; i++) {
        for (var j = 0; j < newArray.length; j++) {
            if (newArray[j] === arrayName[i]) {
                continue uniquetop;
            }
        }
        newArray[newArray.length] = arrayName[i];
    }
    return newArray;
}

/**
 * This function takes in array and a value as input and returns 1 if values is present in array
 * else returns -1
 *
 * @param arrayName array
 * @param value  value which is to be searched in the array
 */

function found (arrayName, value) {
    for (var i = 0; i < arrayName.length; i++) {
        if (arrayName[i] === value) {
            return 1;
        }
    }
    return -1;
}

/**
 * This function concatenates two array
 *
 * @params add array elements of which are pushed in
 * @params arr array in which elements are added
 */
function add_array (add, arr) {
    for (var i = 0; i < add.length; i++) {
        arr.push(add[i]);
    }
    return arr;
}

/* This function removes all elements present in one array from the other.
 *
 * @params rem array from which each element is removed from other array.
 * @params arr array from which elements are removed.
 *
 */
function remove_array (rem, arr) {
    for (var i = 0; i < rem.length; i++) {
        for (var j = 0; j < arr.length; j++) {
            if (rem[i] === arr[j]) {
                arr.splice(j, 1);
            }
        }
    }
    return arr;
}

/**
 * This function builds the groupby clause from history object
 *
 */

function query_groupby () {
    var i;
    var str = '';
    for (i = 0; i < history_array.length;i++) {
        if (history_array[i].get_type() === 'GroupBy') {
            str += '`' + history_array[i].get_column_name() + '`, ';
        }
    }
    str = str.substr(0, str.length - 2);
    return str;
}

/**
 * This function builds the Having clause from the history object.
 *
 */

function query_having () {
    var i;
    var and = '(';
    for (i = 0; i < history_array.length;i++) {
        if (history_array[i].get_type() === 'Having') {
            if (history_array[i].get_obj().get_operator() !== 'None') {
                and += history_array[i].get_obj().get_operator() + '(`' + history_array[i].get_column_name() + '`) ' + history_array[i].get_obj().getrelation_operator();
                and += ' ' + history_array[i].get_obj().getquery() + ', ';
            } else {
                and += '`' + history_array[i].get_column_name() + '` ' + history_array[i].get_obj().getrelation_operator() + ' ' + history_array[i].get_obj().getquery() + ', ';
            }
        }
    }
    if (and === '(') {
        and = '';
    } else {
        and = and.substr(0, and.length - 2) + ')';
    }
    return and;
}


/**
 * This function builds the orderby clause from the history object.
 *
 */

function query_orderby () {
    var i;
    var str = '';
    for (i = 0; i < history_array.length;i++) {
        if (history_array[i].get_type() === 'OrderBy') {
            str += '`' + history_array[i].get_column_name() + '` ' +
                history_array[i].get_obj().get_order() + ', ';
        }
    }
    str = str.substr(0, str.length - 2);
    return str;
}


/**
 * This function builds the Where clause from the history object.
 *
 */

function query_where () {
    var i;
    var and = '(';
    var or = '(';
    for (i = 0; i < history_array.length;i++) {
        if (history_array[i].get_type() === 'Where') {
            if (history_array[i].get_and_or() === 0) {
                and += '( `' + history_array[i].get_column_name() + '` ' + history_array[i].get_obj().getrelation_operator() + ' ' + history_array[i].get_obj().getquery() + ')';
                and += ' AND ';
            } else {
                or += '( `' + history_array[i].get_column_name() + '` ' + history_array[i].get_obj().getrelation_operator() + ' ' + history_array[i].get_obj().getquery() + ')';
                or += ' OR ';
            }
        }
    }
    if (or !== '(') {
        or = or.substring(0, (or.length - 4)) + ')';
    } else {
        or = '';
    }
    if (and !== '(') {
        and = and.substring(0, (and.length - 5)) + ')';
    } else {
        and = '';
    }
    if (or !== '') {
        and = and + ' OR ' + or + ' )';
    }
    return and;
}

function check_aggregate (id_this) {
    var i;
    for (i = 0; i < history_array.length; i++) {
        var temp = '`' + history_array[i].get_tab() + '`.`' + history_array[i].get_column_name() + '`';
        if (temp === id_this && history_array[i].get_type() === 'Aggregate') {
            return history_array[i].get_obj().get_operator() + '(' + id_this + ')';
        }
    }
    return '';
}

function check_rename (id_this) {
    var i;
    for (i = 0; i < history_array.length; i++) {
        var temp = '`' + history_array[i].get_tab() + '`.`' + history_array[i].get_column_name() + '`';
        if (temp === id_this && history_array[i].get_type() === 'Rename') {
            return ' AS `' + history_array[i].get_obj().getrename_to() + '`';
        }
    }
    return '';
}

/**
  * This function builds from clause of query
  * makes automatic joins.
  *
  *
  */
function query_from () {
    var i;
    var tab_left = [];
    var tab_used = [];
    var t_tab_used = [];
    var t_tab_left = [];
    var temp;
    var query = '';
    var quer = '';
    var parts = [];
    var t_array = [];
    t_array = from_array;
    var K = 0;
    var k;
    var key;
    var key2;
    var key3;
    var parts1;

    // the constraints that have been used in the LEFT JOIN
    var constraints_added = [];

    for (i = 0; i < history_array.length; i++) {
        from_array.push(history_array[i].get_tab());
    }
    from_array = unique(from_array);
    tab_left = from_array;
    temp = tab_left.shift();
    quer = '`' + temp + '`';
    tab_used.push(temp);

    // if master table (key2) matches with tab used get all keys and check if tab_left matches
    // after this check if master table (key2) matches with tab left then check if any foreign matches with master .
    for (i = 0; i < 2; i++) {
        for (K in contr) {
            for (key in contr[K]) {// contr name
                for (key2 in contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (found(tab_used, parts[1]) > 0) {
                        for (key3 in contr[K][key][key2]) {
                            parts1 = contr[K][key][key2][key3][0].split('.');
                            if (found(tab_left, parts1[1]) > 0) {
                                if (found(constraints_added, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` = ';
                                    query += '`' + parts[1] + '`.`' + key3 + '` ';

                                    constraints_added.push(key);
                                }
                                t_tab_left.push(parts[1]);
                            }
                        }
                    }
                }
            }
        }
        K = 0;
        t_tab_left = unique(t_tab_left);
        tab_used = add_array(t_tab_left, tab_used);
        tab_left = remove_array(t_tab_left, tab_left);
        t_tab_left = [];
        for (K in contr) {
            for (key in contr[K]) {
                for (key2 in contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (found(tab_left, parts[1]) > 0) {
                        for (key3 in contr[K][key][key2]) {
                            parts1 = contr[K][key][key2][key3][0].split('.');
                            if (found(tab_used, parts1[1]) > 0) {
                                if (found(constraints_added, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` = ';
                                    query += '`' + parts[1] + '`.`' + key3 + '` ';

                                    constraints_added.push(key);
                                }
                                t_tab_left.push(parts[1]);
                            }
                        }
                    }
                }
            }
        }
        t_tab_left = unique(t_tab_left);
        tab_used = add_array(t_tab_left, tab_used);
        tab_left = remove_array(t_tab_left, tab_left);
        t_tab_left = [];
    }
    for (k in tab_left) {
        quer += ' , `' + tab_left[k] + '`';
    }
    query = quer + query;
    from_array = t_array;
    return query;
}

/**
 * This function is the main function for query building.
 * uses history object details for this.
 *
 * @ uses query_where()
 * @ uses query_groupby()
 * @ uses query_having()
 * @ uses query_orderby()
 *
 * @param formtitle title for the form
 * @param fadin
 */

function build_query (formtitle, fadin) {
    var q_select = 'SELECT ';
    var temp;
    if (select_field.length > 0) {
        for (var i = 0; i < select_field.length; i++) {
            temp = check_aggregate(select_field[i]);
            if (temp !== '') {
                q_select += temp;
                temp = check_rename(select_field[i]);
                q_select += temp + ', ';
            } else {
                temp = check_rename(select_field[i]);
                q_select += select_field[i] + temp + ', ';
            }
        }
        q_select = q_select.substring(0, q_select.length - 2);
    } else {
        q_select += '* ';
    }

    q_select += '\nFROM ' + query_from();

    var q_where = query_where();
    if (q_where !== '') {
        q_select += '\nWHERE ' + q_where;
    }

    var q_groupby = query_groupby();
    if (q_groupby !== '') {
        q_select += '\nGROUP BY ' + q_groupby;
    }

    var q_having = query_having();
    if (q_having !== '') {
        q_select += '\nHAVING ' + q_having;
    }

    var q_orderby = query_orderby();
    if (q_orderby !== '') {
        q_select += '\nORDER BY ' + q_orderby;
    }

    /**
     * @var button_options Object containing options
     *                     for jQueryUI dialog buttons
     */
    var button_options = {};
    button_options[PMA_messages.strClose] = function () {
        $(this).dialog('close');
    };
    button_options[PMA_messages.strSubmit] = function () {
        if (vqb_editor) {
            var $elm = $ajaxDialog.find('textarea');
            vqb_editor.save();
            $elm.val(vqb_editor.getValue());
        }
        $('#vqb_form').submit();
    };

    var $ajaxDialog = $('#box').dialog({
        appendTo: '#page_content',
        width: 500,
        buttons: button_options,
        modal: true,
        title: 'SELECT'
    });
    // Attach syntax highlighted editor to query dialog
    /**
     * @var $elm jQuery object containing the reference
     *           to the query textarea.
     */
    var $elm = $ajaxDialog.find('textarea');
    if (! vqb_editor) {
        vqb_editor = PMA_getSQLEditor($elm);
    }
    if (vqb_editor) {
        vqb_editor.setValue(q_select);
        vqb_editor.focus();
    } else {
        $elm.val(q_select);
        $elm.focus();
    }
}

AJAX.registerTeardown('designer/history.js', function () {
    vqb_editor = null;
    history_array = [];
    select_field = [];
    $('#ok_edit_rename').off('click');
    $('#ok_edit_having').off('click');
    $('#ok_edit_Aggr').off('click');
    $('#ok_edit_where').off('click');
});

AJAX.registerOnload('designer/history.js', function () {
    $('#ok_edit_rename').click(function () {
        edit('Rename');
    });
    $('#ok_edit_having').click(function () {
        edit('Having');
    });
    $('#ok_edit_Aggr').click(function () {
        edit('Aggregate');
    });
    $('#ok_edit_where').click(function () {
        edit('Where');
    });
    $('#ab').accordion({ collapsible : true, active : 'none' });
});
