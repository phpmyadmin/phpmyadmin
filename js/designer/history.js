/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in this file builds history tab and generates query.
  *
  * @requires    jQuery
  * @requires    move.js
  * @version $Id$
  */

var historyArray = []; // Global array to store history objects
var selectField = [];  // Global array to store informaation for columns which are used in select clause
var gIndex;
var vqbEditor = null;

/**
 * To display details of objects(where,rename,Having,aggregate,groupby,orderby,having)
 *
 * @param index index of historyArray where change is to be made
 *
**/

function detail (index) {
    var type = historyArray[index].get_type();
    var str;
    if (type === 'Where') {
        str = 'Where ' + historyArray[index].get_column_name() + historyArray[index].get_obj().getrelation_operator() + historyArray[index].get_obj().getquery();
    }
    if (type === 'Rename') {
        str = 'Rename ' + historyArray[index].get_column_name() + ' To ' + historyArray[index].get_obj().getrename_to();
    }
    if (type === 'Aggregate') {
        str = 'Select ' + historyArray[index].get_obj().get_operator() + '( ' + historyArray[index].get_column_name() + ' )';
    }
    if (type === 'GroupBy') {
        str = 'GroupBy ' + historyArray[index].get_column_name();
    }
    if (type === 'OrderBy') {
        str = 'OrderBy ' + historyArray[index].get_column_name() + ' ' + historyArray[index].get_obj().get_order();
    }
    if (type === 'Having') {
        str = 'Having ';
        if (historyArray[index].get_obj().get_operator() !== 'None') {
            str += historyArray[index].get_obj().get_operator() + '( ' + historyArray[index].get_column_name() + ' )';
            str += historyArray[index].get_obj().getrelation_operator() + historyArray[index].get_obj().getquery();
        } else {
            str = 'Having ' + historyArray[index].get_column_name() + historyArray[index].get_obj().getrelation_operator() + historyArray[index].get_obj().getquery();
        }
    }
    return str;
}

/**
 * Sorts historyArray[] first,using table name as the key and then generates the HTML code for history tab,
 * clubbing all objects of same tables together
 * This function is called whenever changes are made in historyArray[]
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
        sto = historyArray[i];
        temp = historyArray[i].get_tab();// + '.' + historyArray[i].get_obj_no(); for Self JOINS
        for (j = 0; j < i; j++) {
            if (temp > (historyArray[j].get_tab())) {// + '.' + historyArray[j].get_obj_no())) { //for Self JOINS
                for (k = i; k > j; k--) {
                    historyArray[k] = historyArray[k - 1];
                }
                historyArray[j] = sto;
                break;
            }
        }
    }
    // this part generates HTML code for history tab.adds delete,edit,and/or and detail features with objects.
    str = ''; // string to store Html code for history tab
    for (i = 0; i < historyArray.length; i++) {
        temp = historyArray[i].get_tab(); // + '.' + historyArray[i].get_obj_no(); for Self JOIN
        str += '<h3 class="tiger"><a href="#">' + temp + '</a></h3>';
        str += '<div class="toggle_container">\n';
        while ((historyArray[i].get_tab()) === temp) { // + '.' + historyArray[i].get_obj_no()) === temp) {
            str += '<div class="block"> <table width ="250">';
            str += '<thead><tr><td>';
            if (historyArray[i].get_and_or()) {
                str += '<img src="' + pmaThemeImage + 'designer/or_icon.png" onclick="and_or(' + i + ')" title="OR"></td>';
            } else {
                str += '<img src="' + pmaThemeImage + 'designer/and_icon.png" onclick="and_or(' + i + ')" title="AND"></td>';
            }
            str += '<td style="padding-left: 5px;" class="right">' + Functions.getImage('b_sbrowse', 'column name') + '</td>' +
                '<td width="175" style="padding-left: 5px">' + historyArray[i].get_column_name() + '<td>';
            if (historyArray[i].get_type() === 'GroupBy' || historyArray[i].get_type() === 'OrderBy') {
                str += '<td class="center">' + Functions.getImage('s_info', detail(i)) + '</td>' +
                    '<td title="' + detail(i) + '">' + historyArray[i].get_type() + '</td>' +
                    '<td onclick=history_delete(' + i + ')>' + Functions.getImage('b_drop', Messages.strDelete) + '</td>';
            } else {
                str += '<td class="center">' + Functions.getImage('s_info', detail(i)) + '</td>' +
                    '<td title="' + detail(i) + '">' + historyArray[i].get_type() + '</td>' +
                    '<td onclick=history_edit(' + i + ')>' + Functions.getImage('b_edit', Messages.strEdit) + '</td>' +
                    '<td onclick=history_delete(' + i + ')>' + Functions.getImage('b_drop', Messages.strDelete) + '</td>';
            }
            str += '</tr></thead>';
            i++;
            if (i >= historyArray.length) {
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
 * @param {int} index of historyArray where change is to be made
 *
**/

function and_or (index) {
    if (historyArray[index].get_and_or()) {
        historyArray[index].set_and_or(0);
    } else {
        historyArray[index].set_and_or(1);
    }
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = display(0, 0);
    $('#ab').accordion('refresh');
}

/**
 * Deletes entry in historyArray
 *
 * @param index index of historyArray[] which is to be deleted
 *
**/

function history_delete (index) {
    for (var k = 0; k < fromArray.length; k++) {
        if (fromArray[k] === historyArray[index].get_tab()) {
            fromArray.splice(k, 1);
            break;
        }
    }
    historyArray.splice(index, 1);
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = display(0, 0);
    $('#ab').accordion('refresh');
}

/**
 * To show where,rename,aggregate,having forms to edit a object
 *
 * @param{int} index index of historyArray where change is to be made
 *
**/

function history_edit (index) {
    gIndex = index;
    var type = historyArray[index].get_type();
    if (type === 'Where') {
        document.getElementById('eQuery').value = historyArray[index].get_obj().getquery();
        document.getElementById('erel_opt').value = historyArray[index].get_obj().getrelation_operator();
        document.getElementById('query_where').style.left =  '530px';
        document.getElementById('query_where').style.top  = '130px';
        document.getElementById('query_where').style.position  = 'absolute';
        document.getElementById('query_where').style.zIndex = '103';
        document.getElementById('query_where').style.visibility = 'visible';
        document.getElementById('query_where').style.display = 'block';
    }
    if (type === 'Having') {
        document.getElementById('hQuery').value = historyArray[index].get_obj().getquery();
        document.getElementById('hrel_opt').value = historyArray[index].get_obj().getrelation_operator();
        document.getElementById('hoperator').value = historyArray[index].get_obj().get_operator();
        document.getElementById('query_having').style.left =  '530px';
        document.getElementById('query_having').style.top  = '130px';
        document.getElementById('query_having').style.position  = 'absolute';
        document.getElementById('query_having').style.zIndex = '103';
        document.getElementById('query_having').style.visibility = 'visible';
        document.getElementById('query_having').style.display = 'block';
    }
    if (type === 'Rename') {
        document.getElementById('e_rename').value = historyArray[index].get_obj().getrename_to();
        document.getElementById('query_rename_to').style.left =  '530px';
        document.getElementById('query_rename_to').style.top  = '130px';
        document.getElementById('query_rename_to').style.position  = 'absolute';
        document.getElementById('query_rename_to').style.zIndex = '103';
        document.getElementById('query_rename_to').style.visibility = 'visible';
        document.getElementById('query_rename_to').style.display = 'block';
    }
    if (type === 'Aggregate') {
        document.getElementById('e_operator').value = historyArray[index].get_obj().get_operator();
        document.getElementById('query_Aggregate').style.left = '530px';
        document.getElementById('query_Aggregate').style.top  = '130px';
        document.getElementById('query_Aggregate').style.position  = 'absolute';
        document.getElementById('query_Aggregate').style.zIndex = '103';
        document.getElementById('query_Aggregate').style.visibility = 'visible';
        document.getElementById('query_Aggregate').style.display = 'block';
    }
}

/**
 * Make changes in historyArray when Edit button is clicked
 * checks for the type of object and then sets the new value
 *
 * @param index index of historyArray where change is to be made
**/

function edit (type) {
    if (type === 'Rename') {
        if (document.getElementById('e_rename').value !== '') {
            historyArray[gIndex].get_obj().setrename_to(document.getElementById('e_rename').value);
            document.getElementById('e_rename').value = '';
        }
        document.getElementById('query_rename_to').style.visibility = 'hidden';
    }
    if (type === 'Aggregate') {
        if (document.getElementById('e_operator').value !== '---') {
            historyArray[gIndex].get_obj().set_operator(document.getElementById('e_operator').value);
            document.getElementById('e_operator').value = '---';
        }
        document.getElementById('query_Aggregate').style.visibility = 'hidden';
    }
    if (type === 'Where') {
        if (document.getElementById('erel_opt').value !== '--' && document.getElementById('eQuery').value !== '') {
            historyArray[gIndex].get_obj().setquery(document.getElementById('eQuery').value);
            historyArray[gIndex].get_obj().setrelation_operator(document.getElementById('erel_opt').value);
        }
        document.getElementById('query_where').style.visibility = 'hidden';
    }
    if (type === 'Having') {
        if (document.getElementById('hrel_opt').value !== '--' && document.getElementById('hQuery').value !== '') {
            historyArray[gIndex].get_obj().setquery(document.getElementById('hQuery').value);
            historyArray[gIndex].get_obj().setrelation_operator(document.getElementById('hrel_opt').value);
            historyArray[gIndex].get_obj().set_operator(document.getElementById('hoperator').value);
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
 * @param nColumnName  name of the column on which conditions are put
 * @param nObj          object details(where,rename,orderby,groupby,aggregate)
 * @param nTab          table name of the column on which conditions are applied
 * @param nObjNo       object no used for inner join
 * @param nType         type of object
 *
**/

function history_obj (nColumnName, nObj, nTab, nObjNo, nType) {
    var andOr;
    var obj;
    var tab;
    var columnName;
    var objNo;
    var type;
    this.set_column_name = function (nColumnName) {
        columnName = nColumnName;
    };
    this.get_column_name = function () {
        return columnName;
    };
    this.set_and_or = function (nAndOr) {
        andOr = nAndOr;
    };
    this.get_and_or = function () {
        return andOr;
    };
    this.get_relation = function () {
        return andOr;
    };
    this.set_obj = function (nObj) {
        obj = nObj;
    };
    this.get_obj = function () {
        return obj;
    };
    this.set_tab = function (nTab) {
        tab = nTab;
    };
    this.get_tab = function () {
        return tab;
    };
    this.set_obj_no = function (nObjNo) {
        objNo = nObjNo;
    };
    this.get_obj_no = function () {
        return objNo;
    };
    this.set_type = function (nType) {
        type = nType;
    };
    this.get_type = function () {
        return type;
    };
    this.set_obj_no(nObjNo);
    this.set_tab(nTab);
    this.set_and_or(0);
    this.set_obj(nObj);
    this.set_column_name(nColumnName);
    this.set_type(nType);
}

/**
 * where object closure, makes an object with all information of where
 *
 * @param nRelationOperator type of relation operator to be applied
 * @param nQuery             stores value of value/sub-query
 *
**/


var where = function (nRelationOperator, nQuery) {
    var relationOperator;
    var query;
    this.setrelation_operator = function (nRelationOperator) {
        relationOperator = nRelationOperator;
    };
    this.setquery = function (nQuery) {
        query = nQuery;
    };
    this.getquery = function () {
        return query;
    };
    this.getrelation_operator = function () {
        return relationOperator;
    };
    this.setquery(nQuery);
    this.setrelation_operator(nRelationOperator);
};

/**
 * Orderby object closure
 *
 * @param nOrder order, ASC or DESC
 */
var orderby = function (nOrder) {
    var order;
    this.set_order = function (nOrder) {
        order = nOrder;
    };
    this.get_order = function () {
        return order;
    };
    this.set_order(nOrder);
};

/**
 * Having object closure, makes an object with all information of where
 *
 * @param nRelationOperator type of relation operator to be applied
 * @param nQuery             stores value of value/sub-query
 * @param nOperator          operator
**/

var having = function (nRelationOperator, nQuery, nOperator) {
    var relationOperator;
    var query;
    var operator;
    this.set_operator = function (nOperator) {
        operator = nOperator;
    };
    this.setrelation_operator = function (nRelationOperator) {
        relationOperator = nRelationOperator;
    };
    this.setquery = function (nQuery) {
        query = nQuery;
    };
    this.getquery = function () {
        return query;
    };
    this.getrelation_operator = function () {
        return relationOperator;
    };
    this.get_operator = function () {
        return operator;
    };
    this.setquery(nQuery);
    this.setrelation_operator(nRelationOperator);
    this.set_operator(nOperator);
};

/**
 * rename object closure,makes an object with all information of rename
 *
 * @param nRenameTo new name information
 *
**/

var rename = function (nRenameTo) {
    var renameTo;
    this.setrename_to = function (nRenameTo) {
        renameTo = nRenameTo;
    };
    this.getrename_to = function () {
        return renameTo;
    };
    this.setrename_to(nRenameTo);
};

/**
 * aggregate object closure
 *
 * @param nOperator aggregte operator
 *
**/

var aggregate = function (nOperator) {
    var operator;
    this.set_operator = function (nOperator) {
        operator = nOperator;
    };
    this.get_operator = function () {
        return operator;
    };
    this.set_operator(nOperator);
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
    for (i = 0; i < historyArray.length; i++) {
        if (historyArray[i].get_type() === 'GroupBy') {
            str += '`' + historyArray[i].get_column_name() + '`, ';
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
    for (i = 0; i < historyArray.length; i++) {
        if (historyArray[i].get_type() === 'Having') {
            if (historyArray[i].get_obj().get_operator() !== 'None') {
                and += historyArray[i].get_obj().get_operator() + '(`' + historyArray[i].get_column_name() + '`) ' + historyArray[i].get_obj().getrelation_operator();
                and += ' ' + historyArray[i].get_obj().getquery() + ', ';
            } else {
                and += '`' + historyArray[i].get_column_name() + '` ' + historyArray[i].get_obj().getrelation_operator() + ' ' + historyArray[i].get_obj().getquery() + ', ';
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
    for (i = 0; i < historyArray.length; i++) {
        if (historyArray[i].get_type() === 'OrderBy') {
            str += '`' + historyArray[i].get_column_name() + '` ' +
                historyArray[i].get_obj().get_order() + ', ';
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
    for (i = 0; i < historyArray.length; i++) {
        if (historyArray[i].get_type() === 'Where') {
            if (historyArray[i].get_and_or() === 0) {
                and += '( `' + historyArray[i].get_column_name() + '` ' + historyArray[i].get_obj().getrelation_operator() + ' ' + historyArray[i].get_obj().getquery() + ')';
                and += ' AND ';
            } else {
                or += '( `' + historyArray[i].get_column_name() + '` ' + historyArray[i].get_obj().getrelation_operator() + ' ' + historyArray[i].get_obj().getquery() + ')';
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

function check_aggregate (idThis) {
    var i;
    for (i = 0; i < historyArray.length; i++) {
        var temp = '`' + historyArray[i].get_tab() + '`.`' + historyArray[i].get_column_name() + '`';
        if (temp === idThis && historyArray[i].get_type() === 'Aggregate') {
            return historyArray[i].get_obj().get_operator() + '(' + idThis + ')';
        }
    }
    return '';
}

function check_rename (idThis) {
    var i;
    for (i = 0; i < historyArray.length; i++) {
        var temp = '`' + historyArray[i].get_tab() + '`.`' + historyArray[i].get_column_name() + '`';
        if (temp === idThis && historyArray[i].get_type() === 'Rename') {
            return ' AS `' + historyArray[i].get_obj().getrename_to() + '`';
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
    var tabLeft = [];
    var tabUsed = [];
    var tTabUsed = [];
    var tTabLeft = [];
    var temp;
    var query = '';
    var quer = '';
    var parts = [];
    var tArray = [];
    tArray = fromArray;
    var K = 0;
    var k;
    var key;
    var key2;
    var key3;
    var parts1;

    // the constraints that have been used in the LEFT JOIN
    var constraintsAdded = [];

    for (i = 0; i < historyArray.length; i++) {
        fromArray.push(historyArray[i].get_tab());
    }
    fromArray = unique(fromArray);
    tabLeft = fromArray;
    temp = tabLeft.shift();
    quer = '`' + temp + '`';
    tabUsed.push(temp);

    // if master table (key2) matches with tab used get all keys and check if tab_left matches
    // after this check if master table (key2) matches with tab left then check if any foreign matches with master .
    for (i = 0; i < 2; i++) {
        for (K in contr) {
            for (key in contr[K]) {// contr name
                for (key2 in contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (found(tabUsed, parts[1]) > 0) {
                        for (key3 in contr[K][key][key2]) {
                            parts1 = contr[K][key][key2][key3][0].split('.');
                            if (found(tabLeft, parts1[1]) > 0) {
                                if (found(constraintsAdded, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` = ';
                                    query += '`' + parts[1] + '`.`' + key3 + '` ';

                                    constraintsAdded.push(key);
                                }
                                tTabLeft.push(parts[1]);
                            }
                        }
                    }
                }
            }
        }
        K = 0;
        tTabLeft = unique(tTabLeft);
        tabUsed = add_array(tTabLeft, tabUsed);
        tabLeft = remove_array(tTabLeft, tabLeft);
        tTabLeft = [];
        for (K in contr) {
            for (key in contr[K]) {
                for (key2 in contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (found(tabLeft, parts[1]) > 0) {
                        for (key3 in contr[K][key][key2]) {
                            parts1 = contr[K][key][key2][key3][0].split('.');
                            if (found(tabUsed, parts1[1]) > 0) {
                                if (found(constraintsAdded, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` = ';
                                    query += '`' + parts[1] + '`.`' + key3 + '` ';

                                    constraintsAdded.push(key);
                                }
                                tTabLeft.push(parts[1]);
                            }
                        }
                    }
                }
            }
        }
        tTabLeft = unique(tTabLeft);
        tabUsed = add_array(tTabLeft, tabUsed);
        tabLeft = remove_array(tTabLeft, tabLeft);
        tTabLeft = [];
    }
    for (k in tabLeft) {
        quer += ' , `' + tabLeft[k] + '`';
    }
    query = quer + query;
    fromArray = tArray;
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
 * @param formTitle title for the form
 * @param fadin
 */

function build_query (formTitle, fadin) {
    var qSelect = 'SELECT ';
    var temp;
    if (selectField.length > 0) {
        for (var i = 0; i < selectField.length; i++) {
            temp = check_aggregate(selectField[i]);
            if (temp !== '') {
                qSelect += temp;
                temp = check_rename(selectField[i]);
                qSelect += temp + ', ';
            } else {
                temp = check_rename(selectField[i]);
                qSelect += selectField[i] + temp + ', ';
            }
        }
        qSelect = qSelect.substring(0, qSelect.length - 2);
    } else {
        qSelect += '* ';
    }

    qSelect += '\nFROM ' + query_from();

    var qWhere = query_where();
    if (qWhere !== '') {
        qSelect += '\nWHERE ' + qWhere;
    }

    var qGroupBy = query_groupby();
    if (qGroupBy !== '') {
        qSelect += '\nGROUP BY ' + qGroupBy;
    }

    var qHaving = query_having();
    if (qHaving !== '') {
        qSelect += '\nHAVING ' + qHaving;
    }

    var qOrderBy = query_orderby();
    if (qOrderBy !== '') {
        qSelect += '\nORDER BY ' + qOrderBy;
    }

    /**
     * @var button_options Object containing options
     *                     for jQueryUI dialog buttons
     */
    var buttonOptions = {};
    buttonOptions[Messages.strClose] = function () {
        $(this).dialog('close');
    };
    buttonOptions[Messages.strSubmit] = function () {
        if (vqbEditor) {
            var $elm = $ajaxDialog.find('textarea');
            vqbEditor.save();
            $elm.val(vqbEditor.getValue());
        }
        $('#vqb_form').submit();
    };

    var $ajaxDialog = $('#box').dialog({
        appendTo: '#page_content',
        width: 500,
        buttons: buttonOptions,
        modal: true,
        title: 'SELECT'
    });
    // Attach syntax highlighted editor to query dialog
    /**
     * @var $elm jQuery object containing the reference
     *           to the query textarea.
     */
    var $elm = $ajaxDialog.find('textarea');
    if (! vqbEditor) {
        vqbEditor = Functions.getSqlEditor($elm);
    }
    if (vqbEditor) {
        vqbEditor.setValue(qSelect);
        vqbEditor.focus();
    } else {
        $elm.val(qSelect);
        $elm.focus();
    }
}

AJAX.registerTeardown('designer/history.js', function () {
    vqbEditor = null;
    historyArray = [];
    selectField = [];
    $('#ok_edit_rename').off('click');
    $('#ok_edit_having').off('click');
    $('#ok_edit_Aggr').off('click');
    $('#ok_edit_where').off('click');
});

AJAX.registerOnload('designer/history.js', function () {
    $('#ok_edit_rename').on('click', function () {
        edit('Rename');
    });
    $('#ok_edit_having').on('click', function () {
        edit('Having');
    });
    $('#ok_edit_Aggr').on('click', function () {
        edit('Aggregate');
    });
    $('#ok_edit_where').on('click', function () {
        edit('Where');
    });
    $('#ab').accordion({ collapsible : true, active : 'none' });
});
