import $ from 'jquery';
import { escapeBacktick, escapeSingleQuote } from '../modules/functions/escape.ts';

/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQueryUI
 */

$(document).on('change', '.criteria_op', function () {
    const op = $(this).val();
    const criteria = $(this).closest('.table').find('.rhs_text_val');

    isOpWithoutArg(op) ? criteria.hide().val('') : criteria.show();
});

function getFormatsText () {
    return {
        '=': ' = \'%s\'',
        '>': ' > \'%s\'',
        '>=': ' >= \'%s\'',
        '<': ' < \'%s\'',
        '<=': ' <= \'%s\'',
        '!=': ' != \'%s\'',
        'LIKE': ' LIKE \'%s\'',
        'LIKE %...%': ' LIKE \'%%%s%%\'',
        'NOT LIKE': ' NOT LIKE \'%s\'',
        'NOT LIKE %...%': ' NOT LIKE \'%%%s%%\'',
        'IN (...)': ' IN (%s)',
        'NOT IN (...)': ' NOT IN (%s)',
        'BETWEEN': ' BETWEEN \'%s\' AND \'%s\'',
        'NOT BETWEEN': ' NOT BETWEEN \'%s\' AND \'%s\'',
        'REGEXP': ' REGEXP \'%s\'',
        'REGEXP ^...$': ' REGEXP \'^%s$\'',
        'NOT REGEXP': ' NOT REGEXP \'%s\''
    };
}

function opsWithoutArg () {
    return ['IS NULL', 'IS NOT NULL'];
}

function opsWithMultipleArgs (): string[] {
    return ['IN (...)', 'NOT IN (...)'];
}

function opsWithTwoArgs (): string[] {
    return ['BETWEEN', 'NOT BETWEEN'];
}

function isOpWithoutArg (op) {
    return opsWithoutArg().includes(op);
}

function acceptsMultipleValues (op: string): boolean {
    return opsWithMultipleArgs().includes(op);
}

function acceptsTwoValues (op: string): boolean {
    return opsWithTwoArgs().includes(op);
}

function joinWrappingElementsWith (array: string[], char: string, separator: string = ','): string {
    let string: string = '';

    array.forEach(function (option: string, index: number) {
        string += `${char}${option}${char}`;

        if (index !== array.length - 1) {
            string += separator;
        }
    });

    return string;
}

function generateCondition (criteriaDiv, table) {
    const tableName = table.val();
    const tableAlias = table.siblings('.table_alias').val();
    const criteriaOp = criteriaDiv.find('.criteria_op').first().val();
    let criteriaText = criteriaDiv.find('.rhs_text_val').first().val();

    let query = '`' + escapeBacktick(tableAlias === '' ? tableName : tableAlias) + '`.';
    query += '`' + escapeBacktick(table.parent().find('.opColumn').first().val()) + '`';
    if (criteriaDiv.find('.criteria_rhs').first().val() === 'text') {
        if (isOpWithoutArg(criteriaOp)) {
            query += ' ' + criteriaOp;
        } else if (acceptsMultipleValues(criteriaOp)) {
            const formatsText = getFormatsText();
            const valuesInputs = criteriaDiv.find('input.val');
            let critertiaTextArray = [];

            valuesInputs.each(function () {
                let value: string = escapeSingleQuote($(this).val());

                if (! critertiaTextArray.includes(value)) {
                    critertiaTextArray.push(value);
                }
            });

            criteriaText = joinWrappingElementsWith(critertiaTextArray, '\'');

            query += window.sprintf(formatsText[criteriaOp], criteriaText);
        } else if (acceptsTwoValues(criteriaOp)) {
            const formatsText = getFormatsText();
            const valuesInputs = criteriaDiv.find('input.val');

            query += window.sprintf(formatsText[criteriaOp], valuesInputs[0].value, valuesInputs[1].value);
        } else {
            const formatsText = getFormatsText();

            query += window.sprintf(formatsText[criteriaOp], criteriaText);
        }
    } else {
        query += ' ' + criteriaOp;
        query += ' `' + escapeBacktick(criteriaDiv.find('.tableNameSelect').first().val()) + '`.';
        query += '`' + escapeBacktick(criteriaDiv.find('.opColumn').first().val()) + '`';
    }

    return query;
}

function generateWhereBlock () {
    var count = 0;
    var query = '';
    $('.tableNameSelect').each(function () {
        var criteriaDiv = $(this).siblings('.jsCriteriaOptions').first();
        var useCriteria = $(this).siblings('.criteria_col').first();
        if ($(this).val() !== '' && useCriteria.prop('checked')) {
            if (count > 0) {
                criteriaDiv.find('input.logical_op').each(function () {
                    if ($(this).prop('checked')) {
                        query += ' ' + $(this).val() + ' ';
                    }
                });
            }

            query += generateCondition(criteriaDiv, $(this));
            count++;
        }
    });

    return query;
}

function generateJoin (newTable, tableAliases, fk) {
    var query = '';
    query += ' \n\tLEFT JOIN ' + '`' + escapeBacktick(newTable) + '`';
    if (tableAliases[fk.TABLE_NAME][0] !== '') {
        query += ' AS `' + escapeBacktick(tableAliases[newTable][0]) + '`';
        query += ' ON `' + escapeBacktick(tableAliases[fk.TABLE_NAME][0]) + '`';
    } else {
        query += ' ON `' + escapeBacktick(fk.TABLE_NAME) + '`';
    }

    query += '.`' + fk.COLUMN_NAME + '`';
    if (tableAliases[fk.REFERENCED_TABLE_NAME][0] !== '') {
        query += ' = `' + escapeBacktick(tableAliases[fk.REFERENCED_TABLE_NAME][0]) + '`';
    } else {
        query += ' = `' + escapeBacktick(fk.REFERENCED_TABLE_NAME) + '`';
    }

    query += '.`' + fk.REFERENCED_COLUMN_NAME + '`';

    return query;
}

function existReference (table, fk, usedTables) {
    var isReferredBy = fk.TABLE_NAME === table && usedTables.includes(fk.REFERENCED_TABLE_NAME);
    var isReferencedBy = fk.REFERENCED_TABLE_NAME === table && usedTables.includes(fk.TABLE_NAME);

    return isReferredBy || isReferencedBy;
}

function tryJoinTable (table, tableAliases, usedTables, foreignKeys) {
    for (var i = 0; i < foreignKeys.length; i++) {
        var fk = foreignKeys[i];
        if (existReference(table, fk, usedTables)) {
            return generateJoin(table, tableAliases, fk);
        }
    }

    return '';
}

function appendTable (table, tableAliases, usedTables, foreignKeys) {
    var query = tryJoinTable(table, tableAliases, usedTables, foreignKeys);
    if (query === '') {
        if (usedTables.length > 0) {
            query += '\n\t, ';
        }

        query += '`' + escapeBacktick(table) + '`';
        if (tableAliases[table][0] !== '') {
            query += ' AS `' + escapeBacktick(tableAliases[table][0]) + '`';
        }
    }

    usedTables.push(table);

    return query;
}

function generateFromBlock (tableAliases, foreignKeys) {
    var usedTables = [];
    var query = '';
    for (var table in tableAliases) {
        if (tableAliases.hasOwnProperty(table)) {
            query += appendTable(table, tableAliases, usedTables, foreignKeys);
        }
    }

    return query;
}

declare global {
    interface Window {
        generateWhereBlock: typeof generateWhereBlock;
        generateFromBlock: typeof generateFromBlock;
    }
}

window.generateWhereBlock = generateWhereBlock;
window.generateFromBlock = generateFromBlock;
