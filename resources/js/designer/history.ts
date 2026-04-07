import $ from 'jquery';
import { getSqlEditor } from '../modules/functions.ts';
import getImageTag from '../modules/functions/getImageTag.ts';
import { DesignerConfig } from './config.ts';

/**
 * @fileoverview    function used in this file builds history tab and generates query.
 *
 * @requires    jQuery
 * @requires    move.js
 */
let gIndex;

/**
 * To display details of objects(where,rename,Having,aggregate,groupby,orderby,having)
 *
 * @param {number} index index of DesignerHistory.historyArray where change is to be made
 * @return {string}
 */
const detail = function (index) {
    const type = DesignerHistory.historyArray[index].getType();
    let str;
    if (type === 'Where') {
        str = 'Where ' + DesignerHistory.historyArray[index].getColumnName() + DesignerHistory.historyArray[index].getObj().getRelationOperator() + DesignerHistory.historyArray[index].getObj().getQuery();
    } else if (type === 'Rename') {
        str = 'Rename ' + DesignerHistory.historyArray[index].getColumnName() + ' To ' + DesignerHistory.historyArray[index].getObj().getRenameTo();
    } else if (type === 'Aggregate') {
        str = 'Select ' + DesignerHistory.historyArray[index].getObj().getOperator() + '( ' + DesignerHistory.historyArray[index].getColumnName() + ' )';
    } else if (type === 'GroupBy') {
        str = 'GroupBy ' + DesignerHistory.historyArray[index].getColumnName();
    } else if (type === 'OrderBy') {
        str = 'OrderBy ' + DesignerHistory.historyArray[index].getColumnName() + ' ' + DesignerHistory.historyArray[index].getObj().getOrder();
    } else if (type === 'Having') {
        str = 'Having ';
        if (DesignerHistory.historyArray[index].getObj().getOperator() !== 'None') {
            str += DesignerHistory.historyArray[index].getObj().getOperator() + '( ' + DesignerHistory.historyArray[index].getColumnName() + ' )';
            str += DesignerHistory.historyArray[index].getObj().getRelationOperator() + DesignerHistory.historyArray[index].getObj().getQuery();
        } else {
            str = 'Having ' + DesignerHistory.historyArray[index].getColumnName() + DesignerHistory.historyArray[index].getObj().getRelationOperator() + DesignerHistory.historyArray[index].getObj().getQuery();
        }
    }

    return str;
};

/**
 * Sorts DesignerHistory.historyArray[] first,using table name as the key and then generates the HTML code for history tab,
 * clubbing all objects of same tables together
 * This function is called whenever changes are made in DesignerHistory.historyArray[]
 *
 *
 * @param {number} init starting index of unsorted array
 * @param {number} finit last index of unsorted array
 * @return {string}
 */
const display = function (init, finit) {
    let str;
    let i;
    let j;
    let k;
    let sto;
    let temp;
    // this part sorts the history array based on table name,this is needed for clubbing all object of same name together.
    for (i = init; i < finit; i++) {
        sto = DesignerHistory.historyArray[i];
        temp = DesignerHistory.historyArray[i].getTab();// + '.' + DesignerHistory.historyArray[i].getObjNo(); for Self JOINS
        for (j = 0; j < i; j++) {
            if (temp > (DesignerHistory.historyArray[j].getTab())) {// + '.' + DesignerHistory.historyArray[j].getObjNo())) { //for Self JOINS
                for (k = i; k > j; k--) {
                    DesignerHistory.historyArray[k] = DesignerHistory.historyArray[k - 1];
                }

                DesignerHistory.historyArray[j] = sto;
                break;
            }
        }
    }

    // this part generates HTML code for history tab.adds delete,edit,and/or and detail features with objects.
    str = ''; // string to store Html code for history tab
    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        temp = DesignerHistory.historyArray[i].getTab(); // + '.' + DesignerHistory.historyArray[i].getObjNo(); for Self JOIN
        str += '<h3 class="tiger"><a href="#">' + temp + '</a></h3>';
        str += '<div class="toggle_container">\n';
        while ((DesignerHistory.historyArray[i].getTab()) === temp) { // + '.' + DesignerHistory.historyArray[i].getObjNo()) === temp) {
            str += '<div class="block"> <table class="table table-sm w-auto mb-0">';
            str += '<thead><tr><td>';
            if (DesignerHistory.historyArray[i].getAndOr()) {
                str += '<img src="' + window.themeImagePath + 'designer/or_icon.png" onclick="DesignerHistory.andOr(' + i + ')" title="OR"></td>';
            } else {
                str += '<img src="' + window.themeImagePath + 'designer/and_icon.png" onclick="DesignerHistory.andOr(' + i + ')" title="AND"></td>';
            }

            str += '<td style="padding-left: 5px;" class="text-end">' + getImageTag('b_sbrowse', window.Messages.strColumnName) + '</td>' +
                '<td width="175" style="padding-left: 5px">' + $('<div/>').text(DesignerHistory.historyArray[i].getColumnName()).html() + '<td>';

            if (DesignerHistory.historyArray[i].getType() === 'GroupBy' || DesignerHistory.historyArray[i].getType() === 'OrderBy') {
                const detailDescGroupBy = $('<div/>').text(DesignerHistory.detail(i)).html();
                str += '<td class="text-center">' + getImageTag('s_info', DesignerHistory.detail(i)) + '</td>' +
                    '<td title="' + detailDescGroupBy + '">' + DesignerHistory.historyArray[i].getType() + '</td>' +
                    '<td onclick=DesignerHistory.historyDelete(' + i + ')>' + getImageTag('b_drop', window.Messages.strDelete) + '</td>';
            } else {
                const detailDesc = $('<div/>').text(DesignerHistory.detail(i)).html();
                str += '<td class="text-center">' + getImageTag('s_info', DesignerHistory.detail(i)) + '</td>' +
                    '<td title="' + detailDesc + '">' + DesignerHistory.historyArray[i].getType() + '</td>' +
                    '<td onclick=DesignerHistory.historyEdit(' + i + ')>' + getImageTag('b_edit', window.Messages.strEdit) + '</td>' +
                    '<td onclick=DesignerHistory.historyDelete(' + i + ')>' + getImageTag('b_drop', window.Messages.strDelete) + '</td>';
            }

            str += '</tr></thead>';
            i++;
            if (i >= historyArrayLength) {
                break;
            }

            str += '</table></div>';
        }

        i--;
        str += '</div>';
    }

    return str;
};

/**
 * To change And/Or relation in history tab
 *
 *
 * @param {number} index index of DesignerHistory.historyArray where change is to be made
 */
const andOr = function (index): void {
    if (DesignerHistory.historyArray[index].getAndOr()) {
        DesignerHistory.historyArray[index].setAndOr(0);
    } else {
        DesignerHistory.historyArray[index].setAndOr(1);
    }

    const existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(0, 0);
    $('#ab').accordion('refresh');
};

/**
 * Deletes entry in DesignerHistory.historyArray
 *
 * @param {number} index of DesignerHistory.historyArray[] which is to be deleted
 */
const historyDelete = function (index): void {
    const fromArrayLength = window.fromArray.length;
    for (let k = 0; k < fromArrayLength; k++) {
        if (window.fromArray[k] === DesignerHistory.historyArray[index].getTab()) {
            window.fromArray.splice(k, 1);
            break;
        }
    }

    DesignerHistory.historyArray.splice(index, 1);
    const existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(0, 0);
    $('#ab').accordion('refresh');
};

/**
 * @param {string} elementId
 */
const changeStyle = function (elementId): void {
    const element = document.getElementById(elementId);
    element.style.left = '530px';
    element.style.top = '130px';
    element.style.position = 'absolute';
    element.style.zIndex = '103';
    element.style.visibility = 'visible';
    element.style.display = 'block';
};

/**
 * To show where,rename,aggregate,having forms to edit a object
 *
 * @param {number} index index of DesignerHistory.historyArray where change is to be made
 */
const historyEdit = function (index): void {
    gIndex = index;
    const type = DesignerHistory.historyArray[index].getType();
    if (type === 'Where') {
        (document.getElementById('eQuery') as HTMLTextAreaElement).value = DesignerHistory.historyArray[index].getObj().getQuery();
        (document.getElementById('erel_opt') as HTMLSelectElement).value = DesignerHistory.historyArray[index].getObj().getRelationOperator();
        DesignerHistory.changeStyle('query_where');
    } else if (type === 'Having') {
        (document.getElementById('hQuery') as HTMLTextAreaElement).value = DesignerHistory.historyArray[index].getObj().getQuery();
        (document.getElementById('hrel_opt') as HTMLSelectElement).value = DesignerHistory.historyArray[index].getObj().getRelationOperator();
        (document.getElementById('hoperator') as HTMLSelectElement).value = DesignerHistory.historyArray[index].getObj().getOperator();
        DesignerHistory.changeStyle('query_having');
    } else if (type === 'Rename') {
        (document.getElementById('e_rename') as HTMLInputElement).value = DesignerHistory.historyArray[index].getObj().getRenameTo();
        DesignerHistory.changeStyle('query_rename_to');
    } else if (type === 'Aggregate') {
        (document.getElementById('e_operator') as HTMLSelectElement).value = DesignerHistory.historyArray[index].getObj().getOperator();
        DesignerHistory.changeStyle('query_Aggregate');
    }
};

/**
 * Make changes in DesignerHistory.historyArray when Edit button is clicked
 * checks for the type of object and then sets the new value
 *
 * @param {string} type of DesignerHistory.historyArray where change is to be made
 */
const edit = function (type): void {
    if (type === 'Rename') {
        if ((document.getElementById('e_rename') as HTMLInputElement).value !== '') {
            DesignerHistory.historyArray[gIndex].getObj().setRenameTo((document.getElementById('e_rename') as HTMLInputElement).value);
            (document.getElementById('e_rename') as HTMLInputElement).value = '';
        }

        document.getElementById('query_rename_to').style.visibility = 'hidden';
    } else if (type === 'Aggregate') {
        if ((document.getElementById('e_operator') as HTMLSelectElement).value !== '---') {
            DesignerHistory.historyArray[gIndex].getObj().setOperator((document.getElementById('e_operator') as HTMLSelectElement).value);
            (document.getElementById('e_operator') as HTMLSelectElement).value = '---';
        }

        document.getElementById('query_Aggregate').style.visibility = 'hidden';
    } else if (type === 'Where') {
        if ((document.getElementById('erel_opt') as HTMLSelectElement).value !== '--' && (document.getElementById('eQuery') as HTMLTextAreaElement).value !== '') {
            DesignerHistory.historyArray[gIndex].getObj().setQuery((document.getElementById('eQuery') as HTMLTextAreaElement).value);
            DesignerHistory.historyArray[gIndex].getObj().setRelationOperator((document.getElementById('erel_opt') as HTMLSelectElement).value);
        }

        document.getElementById('query_where').style.visibility = 'hidden';
    } else if (type === 'Having') {
        if ((document.getElementById('hrel_opt') as HTMLSelectElement).value !== '--' && (document.getElementById('hQuery') as HTMLTextAreaElement).value !== '') {
            DesignerHistory.historyArray[gIndex].getObj().setQuery((document.getElementById('hQuery') as HTMLTextAreaElement).value);
            DesignerHistory.historyArray[gIndex].getObj().setRelationOperator((document.getElementById('hrel_opt') as HTMLSelectElement).value);
            DesignerHistory.historyArray[gIndex].getObj().setOperator((document.getElementById('hoperator') as HTMLSelectElement).value);
        }

        document.getElementById('query_having').style.visibility = 'hidden';
    }

    const existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(0, 0);
    $('#ab').accordion('refresh');
};

/**
 * history object closure
 *
 * @param nColumnName  name of the column on which conditions are put
 * @param nObj          object details(where,rename,orderby,groupby,aggregate)
 * @param nTab          table name of the column on which conditions are applied
 * @param nObjNo       object no used for inner join
 * @param nType         type of object
 *
 */
const HistoryObj = function (nColumnName, nObj, nTab, nObjNo, nType) {
    let andOr;
    let obj;
    let tab;
    let columnName;
    let objNo;
    let type;
    this.setColumnName = function (nColumnName) {
        columnName = nColumnName;
    };

    this.getColumnName = function () {
        return columnName;
    };

    this.setAndOr = function (nAndOr) {
        andOr = nAndOr;
    };

    this.getAndOr = function () {
        return andOr;
    };

    this.getRelation = function () {
        return andOr;
    };

    this.setObj = function (nObj) {
        obj = nObj;
    };

    this.getObj = function () {
        return obj;
    };

    this.setTab = function (nTab) {
        tab = nTab;
    };

    this.getTab = function () {
        return tab;
    };

    this.setObjNo = function (nObjNo) {
        objNo = nObjNo;
    };

    this.getObjNo = function () {
        return objNo;
    };

    this.setType = function (nType) {
        type = nType;
    };

    this.getType = function () {
        return type;
    };

    this.setObjNo(nObjNo);
    this.setTab(nTab);
    this.setAndOr(0);
    this.setObj(nObj);
    this.setColumnName(nColumnName);
    this.setType(nType);
};

/**
 * where object closure, makes an object with all information of where
 *
 * @param nRelationOperator type of relation operator to be applied
 * @param nQuery             stores value of value/sub-query
 *
 */

const Where = function (nRelationOperator, nQuery) {
    let relationOperator;
    let query;
    this.setRelationOperator = function (nRelationOperator) {
        relationOperator = nRelationOperator;
    };

    this.setQuery = function (nQuery) {
        query = nQuery;
    };

    this.getQuery = function () {
        return query;
    };

    this.getRelationOperator = function () {
        return relationOperator;
    };

    this.setQuery(nQuery);
    this.setRelationOperator(nRelationOperator);
};

/**
 * Orderby object closure
 *
 * @param nOrder order, ASC or DESC
 */
const OrderBy = function (nOrder) {
    let order;
    this.setOrder = function (nOrder) {
        order = nOrder;
    };

    this.getOrder = function () {
        return order;
    };

    this.setOrder(nOrder);
};

/**
 * Having object closure, makes an object with all information of where
 *
 * @param nRelationOperator type of relation operator to be applied
 * @param nQuery             stores value of value/sub-query
 * @param nOperator          operator
 */
const Having = function (nRelationOperator, nQuery, nOperator) {
    let relationOperator;
    let query;
    let operator;
    this.setOperator = function (nOperator) {
        operator = nOperator;
    };

    this.setRelationOperator = function (nRelationOperator) {
        relationOperator = nRelationOperator;
    };

    this.setQuery = function (nQuery) {
        query = nQuery;
    };

    this.getQuery = function () {
        return query;
    };

    this.getRelationOperator = function () {
        return relationOperator;
    };

    this.getOperator = function () {
        return operator;
    };

    this.setQuery(nQuery);
    this.setRelationOperator(nRelationOperator);
    this.setOperator(nOperator);
};

/**
 * rename object closure,makes an object with all information of rename
 *
 * @param nRenameTo new name information
 *
 */
const Rename = function (nRenameTo) {
    let renameTo;
    this.setRenameTo = function (nRenameTo) {
        renameTo = nRenameTo;
    };

    this.getRenameTo = function () {
        return renameTo;
    };

    this.setRenameTo(nRenameTo);
};

/**
 * aggregate object closure
 *
 * @param nOperator aggregate operator
 *
 */
const Aggregate = function (nOperator) {
    let operator;
    this.setOperator = function (nOperator) {
        operator = nOperator;
    };

    this.getOperator = function () {
        return operator;
    };

    this.setOperator(nOperator);
};

/**
 * This function returns unique element from an array
 *
 * @param arrayName array from which duplicate elem are to be removed.
 * @return unique array
 */

const unique = function (arrayName) {
    const newArray = [];
    uniquetop:
    for (let i = 0; i < arrayName.length; i++) {
        const newArrayLength = newArray.length;
        for (let j = 0; j < newArrayLength; j++) {
            if (newArray[j] === arrayName[i]) {
                continue uniquetop;
            }
        }

        newArray[newArrayLength] = arrayName[i];
    }

    return newArray;
};

/**
 * This function takes in array and a value as input and returns 1 if values is present in array
 * else returns -1
 *
 * @param arrayName array
 * @param value  value which is to be searched in the array
 */

const found = function (arrayName, value) {
    const arrayNameLength = arrayName.length;
    for (let i = 0; i < arrayNameLength; i++) {
        if (arrayName[i] === value) {
            return 1;
        }
    }

    return -1;
};

/**
 * This function concatenates two array
 *
 * @param {object} add array elements of which are pushed in
 * @param {obj[]} arr array in which elements are added
 *
 * @return {obj[]}
 */
const addArray = function (add, arr) {
    const addLength = add.length;
    for (let i = 0; i < addLength; i++) {
        arr.push(add[i]);
    }

    return arr;
};

/**
 * This function removes all elements present in one array from the other.
 *
 * @param {object} rem array from which each element is removed from other array.
 * @param {obj[]} arr array from which elements are removed.
 *
 * @return {obj[]}
 *
 */
const removeArray = function (rem, arr) {
    const remLength = rem.length;
    for (let i = 0; i < remLength; i++) {
        const arrLength = arr.length;
        for (let j = 0; j < arrLength; j++) {
            if (rem[i] === arr[j]) {
                arr.splice(j, 1);
            }
        }
    }

    return arr;
};

/**
 * This function builds the groupby clause from history object
 * @return {string}
 */
const queryGroupBy = function () {
    let i;
    let str = '';
    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (DesignerHistory.historyArray[i].getType() === 'GroupBy') {
            str += '`' + DesignerHistory.historyArray[i].getColumnName() + '`, ';
        }
    }

    str = str.substring(0, str.length - 2);

    return str;
};

/**
 * This function builds the Having clause from the history object.
 * @return {string}
 */
const queryHaving = function () {
    let i;
    let and = '(';
    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (DesignerHistory.historyArray[i].getType() === 'Having') {
            if (DesignerHistory.historyArray[i].getObj().getOperator() !== 'None') {
                and += DesignerHistory.historyArray[i].getObj().getOperator() + '(`' + DesignerHistory.historyArray[i].getColumnName() + '`) ' + DesignerHistory.historyArray[i].getObj().getRelationOperator();
                and += ' ' + DesignerHistory.historyArray[i].getObj().getQuery() + ', ';
            } else {
                and += '`' + DesignerHistory.historyArray[i].getColumnName() + '` ' + DesignerHistory.historyArray[i].getObj().getRelationOperator() + ' ' + DesignerHistory.historyArray[i].getObj().getQuery() + ', ';
            }
        }
    }

    if (and === '(') {
        and = '';
    } else {
        and = and.substring(0, and.length - 2) + ')';
    }

    return and;
};

/**
 * This function builds the orderby clause from the history object.
 * @return {string}
 */
const queryOrderBy = function () {
    let i;
    let str = '';
    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (DesignerHistory.historyArray[i].getType() === 'OrderBy') {
            str += '`' + DesignerHistory.historyArray[i].getColumnName() + '` ' +
                DesignerHistory.historyArray[i].getObj().getOrder() + ', ';
        }
    }

    str = str.substring(0, str.length - 2);

    return str;
};

/**
 * This function builds the Where clause from the history object.
 * @return {string}
 */
const queryWhere = function () {
    let i;
    let and = '(';
    let or = '(';
    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (DesignerHistory.historyArray[i].getType() === 'Where') {
            if (DesignerHistory.historyArray[i].getAndOr() === 0) {
                and += '( `' + DesignerHistory.historyArray[i].getColumnName() + '` ' + DesignerHistory.historyArray[i].getObj().getRelationOperator() + ' ' + DesignerHistory.historyArray[i].getObj().getQuery() + ')';
                and += ' AND ';
            } else {
                or += '( `' + DesignerHistory.historyArray[i].getColumnName() + '` ' + DesignerHistory.historyArray[i].getObj().getRelationOperator() + ' ' + DesignerHistory.historyArray[i].getObj().getQuery() + ')';
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
};

const checkAggregate = function (idThis) {
    let i;
    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        const temp = '`' + DesignerHistory.historyArray[i].getTab() + '`.`' + DesignerHistory.historyArray[i].getColumnName() + '`';
        if (temp === idThis && DesignerHistory.historyArray[i].getType() === 'Aggregate') {
            return DesignerHistory.historyArray[i].getObj().getOperator() + '(' + idThis + ')';
        }
    }

    return '';
};

const checkRename = function (idThis) {
    let i;
    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        const temp = '`' + DesignerHistory.historyArray[i].getTab() + '`.`' + DesignerHistory.historyArray[i].getColumnName() + '`';
        if (temp === idThis && DesignerHistory.historyArray[i].getType() === 'Rename') {
            return ' AS `' + DesignerHistory.historyArray[i].getObj().getRenameTo() + '`';
        }
    }

    return '';
};

/**
 * This function builds from clause of query
 * makes automatic joins.
 *
 * @return {string}
 */
const queryFrom = function () {
    let i;
    let tabLeft = [];
    let tabUsed = [];
    let tTabLeft = [];
    let temp;
    let query = '';
    let quer = '';
    let parts = [];
    let tArray = [];
    tArray = window.fromArray;
    let K: any = 0;
    let k;
    let key;
    let key2;
    let key3;
    let parts1;

    // the constraints that have been used in the LEFT JOIN
    const constraintsAdded = [];

    const historyArrayLength = DesignerHistory.historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        window.fromArray.push(DesignerHistory.historyArray[i].getTab());
    }

    window.fromArray = DesignerHistory.unique(window.fromArray);
    tabLeft = window.fromArray;
    temp = tabLeft.shift();
    quer = '`' + temp + '`';
    tabUsed.push(temp);

    // if master table (key2) matches with tab used get all keys and check if tab_left matches
    // after this check if master table (key2) matches with tab left then check if any foreign matches with master .
    for (i = 0; i < 2; i++) {
        for (K in DesignerConfig.contr) {
            for (key in DesignerConfig.contr[K]) {// contr name
                for (key2 in DesignerConfig.contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (DesignerHistory.found(tabUsed, parts[1]) > 0) {
                        for (key3 in DesignerConfig.contr[K][key][key2]) {
                            parts1 = DesignerConfig.contr[K][key][key2][key3][0].split('.');
                            if (DesignerHistory.found(tabLeft, parts1[1]) > 0) {
                                if (DesignerHistory.found(constraintsAdded, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + DesignerConfig.contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + DesignerConfig.contr[K][key][key2][key3][1] + '` = ';
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
        tTabLeft = DesignerHistory.unique(tTabLeft);
        tabUsed = DesignerHistory.addArray(tTabLeft, tabUsed);
        tabLeft = DesignerHistory.removeArray(tTabLeft, tabLeft);
        tTabLeft = [];
        for (K in DesignerConfig.contr) {
            for (key in DesignerConfig.contr[K]) {
                for (key2 in DesignerConfig.contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (DesignerHistory.found(tabLeft, parts[1]) > 0) {
                        for (key3 in DesignerConfig.contr[K][key][key2]) {
                            parts1 = DesignerConfig.contr[K][key][key2][key3][0].split('.');
                            if (DesignerHistory.found(tabUsed, parts1[1]) > 0) {
                                if (DesignerHistory.found(constraintsAdded, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + DesignerConfig.contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + DesignerConfig.contr[K][key][key2][key3][1] + '` = ';
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

        tTabLeft = DesignerHistory.unique(tTabLeft);
        tabUsed = DesignerHistory.addArray(tTabLeft, tabUsed);
        tabLeft = DesignerHistory.removeArray(tTabLeft, tabLeft);
        tTabLeft = [];
    }

    for (k in tabLeft) {
        quer += ' , `' + tabLeft[k] + '`';
    }

    query = quer + query;
    window.fromArray = tArray;

    return query;
};

/**
 * This function is the main function for query building.
 * uses history object details for this.
 *
 * @uses DesignerHistory.queryWhere()
 * @uses DesignerHistory.queryGroupBy()
 * @uses DesignerHistory.queryHaving()
 * @uses DesignerHistory.queryOrderBy()
 */
const buildQuery = function () {
    let qSelect = 'SELECT ';
    let temp;
    const selectFieldLength = DesignerHistory.selectField.length;
    if (selectFieldLength > 0) {
        for (let i = 0; i < selectFieldLength; i++) {
            temp = DesignerHistory.checkAggregate(DesignerHistory.selectField[i]);
            if (temp !== '') {
                qSelect += temp;
                temp = DesignerHistory.checkRename(DesignerHistory.selectField[i]);
                qSelect += temp + ', ';
            } else {
                temp = DesignerHistory.checkRename(DesignerHistory.selectField[i]);
                qSelect += DesignerHistory.selectField[i] + temp + ', ';
            }
        }

        qSelect = qSelect.substring(0, qSelect.length - 2);
    } else {
        qSelect += '* ';
    }

    qSelect += '\nFROM ' + DesignerHistory.queryFrom();

    const qWhere = DesignerHistory.queryWhere();
    if (qWhere !== '') {
        qSelect += '\nWHERE ' + qWhere;
    }

    const qGroupBy = DesignerHistory.queryGroupBy();
    if (qGroupBy !== '') {
        qSelect += '\nGROUP BY ' + qGroupBy;
    }

    const qHaving = DesignerHistory.queryHaving();
    if (qHaving !== '') {
        qSelect += '\nHAVING ' + qHaving;
    }

    const qOrderBy = DesignerHistory.queryOrderBy();
    if (qOrderBy !== '') {
        qSelect += '\nORDER BY ' + qOrderBy;
    }

    $('#buildQuerySubmitButton').on('click', function () {
        if (DesignerHistory.vqbEditor) {
            const $elm = $('#buildQueryModal').find('textarea');
            DesignerHistory.vqbEditor.save();
            $elm.val(DesignerHistory.vqbEditor.getValue());
        }

        $('#vqb_form').trigger('submit');
    });

    $('#buildQueryModal').modal('show');
    $('#buildQueryModalLabel').first().text('SELECT');
    $('#buildQueryModal').on('shown.bs.modal', function () {
        // Attach syntax highlighted editor to query dialog
        /**
         * @var $elm jQuery object containing the reference
         *           to the query textarea.
         */
        const $elm = $('#buildQueryModal').find('textarea');
        if (! DesignerHistory.vqbEditor) {
            DesignerHistory.vqbEditor = getSqlEditor($elm);
        }

        if (DesignerHistory.vqbEditor) {
            DesignerHistory.vqbEditor.setValue(qSelect);
            DesignerHistory.vqbEditor.focus();
        } else {
            $elm.val(qSelect);
            $elm.trigger('focus');
        }
    });
};

const DesignerHistory = {
    /**
     * Global array to store history objects.
     */
    historyArray: [],

    /**
     * Global array to store information for columns which are used in select clause.
     */
    selectField: [],

    vqbEditor: null,
    detail: detail,
    display: display,
    andOr: andOr,
    historyDelete: historyDelete,
    changeStyle: changeStyle,
    historyEdit: historyEdit,
    edit: edit,
    HistoryObj: HistoryObj,
    Where: Where,
    OrderBy: OrderBy,
    Having: Having,
    Rename: Rename,
    Aggregate: Aggregate,
    unique: unique,
    found: found,
    addArray: addArray,
    removeArray: removeArray,
    queryGroupBy: queryGroupBy,
    queryHaving: queryHaving,
    queryOrderBy: queryOrderBy,
    queryWhere: queryWhere,
    checkAggregate: checkAggregate,
    checkRename: checkRename,
    queryFrom: queryFrom,
    buildQuery: buildQuery,
};

declare global {
    interface Window {
        DesignerHistory: typeof DesignerHistory;
    }
}

window.DesignerHistory = DesignerHistory;

export { DesignerHistory };
