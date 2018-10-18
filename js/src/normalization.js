/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as PMA_messages } from './variables/export_variables';
import PMA_commonParams from './variables/common_params';
import { PMA_ajaxShowMessage } from './utils/show_ajax_messages';
import { PMA_sprintf } from './utils/sprintf';
import { escapeHtml } from './utils/Sanitise';
import {
    goToStep2,
    goToStep3,
    moveRepeatingGroup,
    appendHtmlColumnsList
} from './functions/Table/Normalization';
import NormalizationEnum from './utils/NormalizationEnum';
import { indexEditorDialog } from './functions/Indexes';
/**
 * @fileoverview   events handling from normalization page
 * @name            normalization
 *
 * @requires    jQuery
 */

/**
 * AJAX scripts for normalization.php
 *
 */

export function teardownNormalization () {
    $('#extra').off('click', '#selectNonAtomicCol');
    $('#splitGo').off('click');
    $('.tblFooters').off('click', '#saveSplit');
    $('#extra').off('click', '#addNewPrimary');
    $('.tblFooters').off('click', '#saveNewPrimary');
    $('#extra').off('click', '#removeRedundant');
    $('#mainContent p').off('click', '#createPrimaryKey');
    $('#mainContent').off('click', '#backEditPd');
    $('#mainContent').off('click', '#showPossiblePd');
    $('#mainContent').off('click', '.pickPd');
}

export function onloadNormalization () {
    var selectedCol;
    NormalizationEnum.normalizeto = $('#mainContent').data('normalizeto');
    $('#extra').on('click', '#selectNonAtomicCol', function () {
        if ($(this).val() === 'no_such_col') {
            goToStep2();
        } else {
            selectedCol = $(this).val();
        }
    });

    $('#splitGo').on('click', function () {
        if (!selectedCol || selectedCol === '') {
            return false;
        }
        var numField = $('#numField').val();
        $.get(
            'normalization.php',
            {
                'ajax_request': true,
                'db': PMA_commonParams.get('db'),
                'table': PMA_commonParams.get('table'),
                'splitColumn': true,
                'numFields': numField
            },
            function (data) {
                if (data.success === true) {
                    $('#newCols').html(data.message);
                    $('.default_value').hide();
                    $('.enum_notice').hide();

                    $('<input />')
                        .attr({ type: 'submit', id: 'saveSplit', value: PMA_messages.strSave })
                        .appendTo('.tblFooters');

                    var cancelSplitButton = $('<input />')
                        .attr({ type: 'submit', id: 'cancelSplit', value: PMA_messages.strCancel })
                        .on('click', function () {
                            $('#newCols').html('');
                            $(this).parent().html('');
                        })
                        .appendTo('.tblFooters');
                }
            }
        );
        return false;
    });
    $('.tblFooters').on('click','#saveSplit', function () {
        central_column_list = [];
        if ($('#newCols #field_0_1').val() === '') {
            $('#newCols #field_0_1').focus();
            return false;
        }
        var argsep = PMA_commonParams.get('arg_separator');
        var datastring = $('#newCols :input').serialize();
        datastring += argsep + 'ajax_request=1' + argsep + 'do_save_data=1' + argsep + 'field_where=last';
        $.post('tbl_addfield.php', datastring, function (data) {
            if (data.success) {
                $.post(
                    'sql.php',
                    {
                        'ajax_request': true,
                        'db': PMA_commonParams.get('db'),
                        'table': PMA_commonParams.get('table'),
                        'dropped_column': selectedCol,
                        'purge' : 1,
                        'sql_query': 'ALTER TABLE `' + PMA_commonParams.get('table') + '` DROP `' + selectedCol + '`;',
                        'is_js_confirmed': 1
                    },
                    function (data) {
                        if (data.success === true) {
                            appendHtmlColumnsList();
                            $('#newCols').html('');
                            $('.tblFooters').html('');
                        } else {
                            PMA_ajaxShowMessage(data.error, false);
                        }
                        selectedCol = '';
                    }
                );
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });

    $('#extra').on('click', '#addNewPrimary', function () {
        $.get(
            'normalization.php',
            {
                'ajax_request': true,
                'db': PMA_commonParams.get('db'),
                'table': PMA_commonParams.get('table'),
                'addNewPrimary': true
            },
            function (data) {
                if (data.success === true) {
                    $('#newCols').html(data.message);
                    $('.default_value').hide();
                    $('.enum_notice').hide();

                    $('<input />')
                        .attr({ type: 'submit', id: 'saveNewPrimary', value: PMA_messages.strSave })
                        .appendTo('.tblFooters');
                    $('<input />')
                        .attr({ type: 'submit', id: 'cancelSplit', value: PMA_messages.strCancel })
                        .on('click', function () {
                            $('#newCols').html('');
                            $(this).parent().html('');
                        })
                        .appendTo('.tblFooters');
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        );
        return false;
    });
    $('.tblFooters').on('click', '#saveNewPrimary', function () {
        var datastring = $('#newCols :input').serialize();
        var argsep = PMA_commonParams.get('arg_separator');
        datastring += argsep + 'field_key[0]=primary_0' + argsep + 'ajax_request=1' + argsep + 'do_save_data=1' + argsep + 'field_where=last';
        $.post('tbl_addfield.php', datastring, function (data) {
            if (data.success === true) {
                $('#mainContent h4').html(PMA_messages.strPrimaryKeyAdded);
                $('#mainContent p').html(PMA_messages.strToNextStep);
                $('#mainContent #extra').html('');
                $('#mainContent #newCols').html('');
                $('.tblFooters').html('');
                setTimeout(function () {
                    goToStep3();
                }, 2000);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });
    $('#extra').on('click', '#removeRedundant', function () {
        var dropQuery = 'ALTER TABLE `' + PMA_commonParams.get('table') + '` ';
        $('#extra input[type=checkbox]:checked').each(function () {
            dropQuery += 'DROP `' + $(this).val() + '`, ';
        });
        dropQuery = dropQuery.slice(0, -2);
        $.post(
            'sql.php',
            {
                'ajax_request': true,
                'db': PMA_commonParams.get('db'),
                'table': PMA_commonParams.get('table'),
                'sql_query': dropQuery,
                'is_js_confirmed': 1
            },
            function (data) {
                if (data.success === true) {
                    goToStep2('goToFinish1NF');
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }
        );
    });
    $('#extra').on('click', '#moveRepeatingGroup', function () {
        var repeatingCols = '';
        $('#extra input[type=checkbox]:checked').each(function () {
            repeatingCols += $(this).val() + ', ';
        });

        if (repeatingCols !== '') {
            var newColName = $('#extra input[type=checkbox]:checked:first').val();
            repeatingCols = repeatingCols.slice(0, -2);
            var confirmStr = PMA_sprintf(PMA_messages.strMoveRepeatingGroup, escapeHtml(repeatingCols), escapeHtml(PMA_commonParams.get('table')));
            confirmStr += '<input type="text" name="repeatGroupTable" placeholder="' + PMA_messages.strNewTablePlaceholder + '"/>' +
                '( ' + escapeHtml(NormalizationEnum.primary_key.toString()) + ', <input type="text" name="repeatGroupColumn" placeholder="' + PMA_messages.strNewColumnPlaceholder + '" value="' + escapeHtml(newColName) + '">)' +
                '</ol>';
            $('#newCols').html(confirmStr);

            $('<input />')
                .attr({ type: 'submit', value: PMA_messages.strCancel })
                .on('click', function () {
                    $('#newCols').html('');
                    $('#extra input[type=checkbox]').prop('checked', false);
                })
                .appendTo('.tblFooters');
            $('<input />')
                .attr({ type: 'submit', value: PMA_messages.strGo })
                .on('click', function () {
                    moveRepeatingGroup(repeatingCols);
                })
                .appendTo('.tblFooters');
        }
    });
    $('#mainContent p').on('click', '#createPrimaryKey', function (event) {
        event.preventDefault();
        var url = { create_index: 1,
            server:  PMA_commonParams.get('server'),
            db: PMA_commonParams.get('db'),
            table: PMA_commonParams.get('table'),
            added_fields: 1,
            add_fields:1,
            index: { Key_name:'PRIMARY' },
            ajax_request: true
        };
        var title = PMA_messages.strAddPrimaryKey;
        indexEditorDialog(url, title, function () {
            // on success
            $('.sqlqueryresults').remove();
            $('.result_query').remove();
            $('.tblFooters').html('');
            goToStep2('goToStep3');
        });
        return false;
    });
    $('#mainContent').on('click', '#backEditPd', function () {
        $('#mainContent').html(NormalizationEnum.backup);
    });
    $('#mainContent').on('click', '#showPossiblePd', function () {
        if ($(this).hasClass('hideList')) {
            $(this).html('+ ' + PMA_messages.strShowPossiblePd);
            $(this).removeClass('hideList');
            $('#newCols').slideToggle('slow');
            return false;
        }
        if ($('#newCols').html() !== '') {
            $('#showPossiblePd').html('- ' + PMA_messages.strHidePd);
            $('#showPossiblePd').addClass('hideList');
            $('#newCols').slideToggle('slow');
            return false;
        }
        $('#newCols').insertAfter('#mainContent h4');
        $('#newCols').html('<div class="center">' + PMA_messages.strLoading + '<br/>' + PMA_messages.strWaitForPd + '</div>');
        $.post(
            'normalization.php',
            {
                'ajax_request': true,
                'db': PMA_commonParams.get('db'),
                'table': PMA_commonParams.get('table'),
                'findPdl': true
            }, function (data) {
                $('#showPossiblePd').html('- ' + PMA_messages.strHidePd);
                $('#showPossiblePd').addClass('hideList');
                $('#newCols').html(data.message);
            });
    });
    $('#mainContent').on('click', '.pickPd', function () {
        var strColsLeft = $(this).next('.determinants').html();
        var colsLeft = strColsLeft.split(',');
        var strColsRight = $(this).next().next().html();
        var colsRight = strColsRight.split(',');
        for (var i in colsRight) {
            $('form[data-colname="' + colsRight[i].trim() + '"] input[type="checkbox"]').prop('checked', false);
            for (var j in colsLeft) {
                $('form[data-colname="' + colsRight[i].trim() + '"] input[value="' + colsLeft[j].trim() + '"]').prop('checked', true);
            }
        }
    });
}
