import { Functions } from "./functions.ts";
import { Navigation } from "./navigation.ts";
import { CommonParams } from "./common.ts";
import refreshMainContent from "./functions/refreshMainContent.ts";
import { ajaxRemoveMessage, ajaxShowMessage } from './ajax-message.ts';
import getJsConfirmCommonParam from './functions/getJsConfirmCommonParam.ts';
import highlightSql from './sql-highlight.ts';

/**
 * @return {function}
 */
function off() {
    return function () {};
}

/**
 * @return {function}
 */
function on() {
    return function () {
        $(document).on('change', '#select_check_constraint_level', function (event) {
            event.preventDefault();
            if (event.target.value == "Column") {
                document.getElementById("check_constraint_name_container").classList.add("hide");
                document.getElementById("check_constraint_column_container").classList.remove("hide");
            } else {
                document.getElementById("check_constraint_name_container").classList.remove("hide");
                document.getElementById("check_constraint_column_container").classList.add("hide");
            }
        });


        /**
         * Ajax event handler for check constraint edit & creation
         **/
        $(document).on(
            "click",
            "#table_check_constraint tbody tr td.edit_check_constraint.ajax, #check_constraints_div .add_check_constraint.ajax",
            function (event) {
                event.preventDefault();
                var url;
                var title;

                if ($(this).find("a").length === 0) {
                    // Add index
                    url = $(this).closest("form").serialize();
                    title = window.Messages.strAddConstraint;
                } else {
                    // Edit index
                    url = $(this).find("a").getPostData();
                    title = window.Messages.strEditConstraint;
                }

                url += CommonParams.get("arg_separator") + "ajax_request=true";
                Functions.checkConstraintEditorDialog(url, title, function (data) {
                    Navigation.update(CommonParams.set("db", data.params.db));
                    Navigation.update(
                        CommonParams.set("table", data.params.table)
                    );
                    refreshMainContent("index.php?route=/table/structure");
                });
            }
        );

        /**
         * Ajax event handler for index rename
         **/
        $(document).on('click', '#table_check_constraint tbody tr td.rename_check_constraint.ajax', function (event) {
            event.preventDefault();
            var url = $(this).find('a').getPostData();
            var title = window.Messages.strRenameCheckConstraint;
            url += CommonParams.get('arg_separator') + 'ajax_request=true';
            Functions.checkConstraintRenameDialog(url, title, function (data) {
                Navigation.update(CommonParams.set('db', data.params.db));
                Navigation.update(CommonParams.set('table', data.params.table));
                refreshMainContent('index.php?route=/table/structure');
            });
        });

        /**
         * Ajax Event handler for 'Drop Index'
         */
        $(document).on('click', 'a.drop_check_constraint_anchor.ajax', function (event) {
            event.preventDefault();
            var $anchor = $(this);
            /**
             * @var $currRow Object containing reference to the current field's row
             */
            var $currRow = $anchor.parents('tr');

            /** @var {number} $rowsToHide Rows that should be hidden */
            var $rowsToHide = $currRow;

            var question = $currRow.children('td')
                .children('.drop_check_constraint_msg')
                .val();

            var params = getJsConfirmCommonParam(this, $anchor.getPostData());
            var paramsObj = new URLSearchParams(params);
            if (paramsObj.get("sql_query") === "") {
                ajaxShowMessage(paramsObj.get("message_to_show"), false);
                return;
            }

            Functions.confirmPreviewSql(question, $anchor.attr('href'), function (url) {
                var $msg = ajaxShowMessage(window.Messages.strDroppingCheckConstraint, false);

                $.post(url, params, function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        ajaxRemoveMessage($msg);
                        var $tableRef = $rowsToHide.closest('table');
                        if ($rowsToHide.length === $tableRef.find('tbody > tr').length) {
                            // We are about to remove all rows from the table
                            $tableRef.hide('medium', function () {
                                $('div.no_check_constraints_defined').show('medium');
                                $rowsToHide.remove();
                            });

                            $tableRef.siblings('.alert-primary').hide('medium');
                        } else {
                            // We are removing some of the rows only
                            $rowsToHide.hide('medium', function () {
                                $(this).remove();
                            });
                        }

                        if ($('.result_query').length) {
                            $('.result_query').remove();
                        }

                        if (data.sql_query) {
                            $('<div class="result_query"></div>')
                                .html(data.sql_query)
                                .prependTo('#structure_content');

                            highlightSql($('#page_content'));
                        }

                        Navigation.reload();
                        refreshMainContent('index.php?route=/table/structure');
                    } else {
                        ajaxShowMessage(window.Messages.strErrorProcessingRequest + ' : ' + data.error, false);
                    }
                }); // end $.post()
            });
        }); // end Drop Primary Key/Index
    };
}

/**
 * Check constraints manipulation pages
 */
const CheckConstraints = {
    off: off,
    on: on,
};

export { CheckConstraints };
