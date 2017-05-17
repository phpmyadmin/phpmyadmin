/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Create advanced table (resize, reorder, and show/hide columns; and also grid editing).
 * This function is designed mainly for table DOM generated from browsing a table in the database.
 * For using this function in other table DOM, you may need to:
 * - add "draggable" class in the table header <th>, in order to make it resizable, sortable or hidable
 * - have at least one non-"draggable" header in the table DOM for placing column visibility drop-down arrow
 * - pass the value "false" for the parameter "enableGridEdit"
 * - adjust other parameter value, to select which features that will be enabled
 *
 * @param t the table DOM element
 * @param enableResize Optional, if false, column resizing feature will be disabled
 * @param enableReorder Optional, if false, column reordering feature will be disabled
 * @param enableVisib Optional, if false, show/hide column feature will be disabled
 * @param enableGridEdit Optional, if false, grid editing feature will be disabled
 */
function PMA_makegrid(t, enableResize, enableReorder, enableVisib, enableGridEdit) {
    var g = {
        /***********
         * Constant
         ***********/
        minColWidth: 15,


        /***********
         * Variables, assigned with default value, changed later
         ***********/
        actionSpan: 5,              // number of colspan in Actions header in a table
        tableCreateTime: null,      // table creation time, used for saving column order and visibility to server, only available in "Browse tab"

        // Column reordering variables
        colOrder: [],      // array of column order

        // Column visibility variables
        colVisib: [],      // array of column visibility
        showAllColText: '',         // string, text for "show all" button under column visibility list
        visibleHeadersCount: 0,     // number of visible data headers

        // Table hint variables
        reorderHint: '',            // string, hint for column reordering
        sortHint: '',               // string, hint for column sorting
        markHint: '',               // string, hint for column marking
        copyHint: '',               // string, hint for copy column name
        showReorderHint: false,
        showSortHint: false,
        showMarkHint: false,

        // Grid editing
        isCellEditActive: false,    // true if current focus is in edit cell
        isEditCellTextEditable: false,  // true if current edit cell is editable in the text input box (not textarea)
        currentEditCell: null,      // reference to <td> that currently being edited
        cellEditHint: '',           // hint shown when doing grid edit
        gotoLinkText: '',           // "Go to link" text
        wasEditedCellNull: false,   // true if last value of the edited cell was NULL
        maxTruncatedLen: 0,         // number of characters that can be displayed in a cell
        saveCellsAtOnce: false,     // $cfg[saveCellsAtOnce]
        isCellEdited: false,        // true if at least one cell has been edited
        saveCellWarning: '',        // string, warning text when user want to leave a page with unsaved edited data
        lastXHR : null,             // last XHR object used in AJAX request
        isSaving: false,            // true when currently saving edited data, used to handle double posting caused by pressing ENTER in grid edit text box in Chrome browser
        alertNonUnique: '',         // string, alert shown when saving edited nonunique table

        // Common hidden inputs
        token: null,
        server: null,
        db: null,
        table: null,


        /************
         * Functions
         ************/

        /**
         * Start to resize column. Called when clicking on column separator.
         *
         * @param e event
         * @param obj dragged div object
         */
        dragStartRsz: function (e, obj) {
            var n = $(g.cRsz).find('div').index(obj);    // get the index of separator (i.e., column index)
            $(obj).addClass('colborder_active');
            g.colRsz = {
                x0: e.pageX,
                n: n,
                obj: obj,
                objLeft: $(obj).position().left,
                objWidth: $(g.t).find('th.draggable:visible:eq(' + n + ') span').outerWidth()
            };
            $(document.body).css('cursor', 'col-resize').noSelect();
            if (g.isCellEditActive) {
                g.hideEditCell();
            }
        },

        /**
         * Start to reorder column. Called when clicking on table header.
         *
         * @param e event
         * @param obj table header object
         */
        dragStartReorder: function (e, obj) {
            // prepare the cCpy (column copy) and cPointer (column pointer) from the dragged column
            $(g.cCpy).text($(obj).text());
            var objPos = $(obj).position();
            $(g.cCpy).css({
                top: objPos.top + 20,
                left: objPos.left,
                height: $(obj).height(),
                width: $(obj).width()
            });
            $(g.cPointer).css({
                top: objPos.top
            });

            // get the column index, zero-based
            var n = g.getHeaderIdx(obj);

            g.colReorder = {
                x0: e.pageX,
                y0: e.pageY,
                n: n,
                newn: n,
                obj: obj,
                objTop: objPos.top,
                objLeft: objPos.left
            };

            $(document.body).css('cursor', 'move').noSelect();
            if (g.isCellEditActive) {
                g.hideEditCell();
            }
        },

        /**
         * Handle mousemove event when dragging.
         *
         * @param e event
         */
        dragMove: function (e) {
            if (g.colRsz) {
                var dx = e.pageX - g.colRsz.x0;
                if (g.colRsz.objWidth + dx > g.minColWidth) {
                    $(g.colRsz.obj).css('left', g.colRsz.objLeft + dx + 'px');
                }
            } else if (g.colReorder) {
                // dragged column animation
                var dx = e.pageX - g.colReorder.x0;
                $(g.cCpy)
                    .css('left', g.colReorder.objLeft + dx)
                    .show();

                // pointer animation
                var hoveredCol = g.getHoveredCol(e);
                if (hoveredCol) {
                    var newn = g.getHeaderIdx(hoveredCol);
                    g.colReorder.newn = newn;
                    if (newn != g.colReorder.n) {
                        // show the column pointer in the right place
                        var colPos = $(hoveredCol).position();
                        var newleft = newn < g.colReorder.n ?
                                      colPos.left :
                                      colPos.left + $(hoveredCol).outerWidth();
                        $(g.cPointer)
                            .css({
                                left: newleft,
                                visibility: 'visible'
                            });
                    } else {
                        // no movement to other column, hide the column pointer
                        $(g.cPointer).css('visibility', 'hidden');
                    }
                }
            }
        },

        /**
         * Stop the dragging action.
         *
         * @param e event
         */
        dragEnd: function (e) {
            if (g.colRsz) {
                var dx = e.pageX - g.colRsz.x0;
                var nw = g.colRsz.objWidth + dx;
                if (nw < g.minColWidth) {
                    nw = g.minColWidth;
                }
                var n = g.colRsz.n;
                // do the resizing
                g.resize(n, nw);

                g.reposRsz();
                g.reposDrop();
                g.colRsz = false;
                $(g.cRsz).find('div').removeClass('colborder_active');
                rearrangeStickyColumns($(t).prev('.sticky_columns'), $(t));
            } else if (g.colReorder) {
                // shift columns
                if (g.colReorder.newn != g.colReorder.n) {
                    g.shiftCol(g.colReorder.n, g.colReorder.newn);
                    // assign new position
                    var objPos = $(g.colReorder.obj).position();
                    g.colReorder.objTop = objPos.top;
                    g.colReorder.objLeft = objPos.left;
                    g.colReorder.n = g.colReorder.newn;
                    // send request to server to remember the column order
                    if (g.tableCreateTime) {
                        g.sendColPrefs();
                    }
                    g.refreshRestoreButton();
                }

                // animate new column position
                $(g.cCpy).stop(true, true)
                    .animate({
                        top: g.colReorder.objTop,
                        left: g.colReorder.objLeft
                    }, 'fast')
                    .fadeOut();
                $(g.cPointer).css('visibility', 'hidden');

                g.colReorder = false;
                rearrangeStickyColumns($(t).prev('.sticky_columns'), $(t));
            }
            $(document.body).css('cursor', 'inherit').noSelect(false);
        },

        /**
         * Resize column n to new width "nw"
         *
         * @param n zero-based column index
         * @param nw new width of the column in pixel
         */
        resize: function (n, nw) {
            $(g.t).find('tr').each(function () {
                $(this).find('th.draggable:visible:eq(' + n + ') span,' +
                             'td:visible:eq(' + (g.actionSpan + n) + ') span')
                       .css('width', nw);
            });
        },

        /**
         * Reposition column resize bars.
         */
        reposRsz: function () {
            $(g.cRsz).find('div').hide();
            var $firstRowCols = $(g.t).find('tr:first th.draggable:visible');
            var $resizeHandles = $(g.cRsz).find('div').removeClass('condition');
            $(g.t).find('table.pma_table').find('thead th:first').removeClass('before-condition');
            for (var n = 0, l = $firstRowCols.length; n < l; n++) {
                var $col = $($firstRowCols[n]);
                var colWidth;
                if (navigator.userAgent.toLowerCase().indexOf("safari") != -1) {
                    colWidth = $col.outerWidth();
                } else {
                    colWidth = $col.outerWidth(true);
                }
                $($resizeHandles[n]).css('left', $col.position().left + colWidth)
                   .show();
                if ($col.hasClass('condition')) {
                    $($resizeHandles[n]).addClass('condition');
                    if (n > 0) {
                        $($resizeHandles[n - 1]).addClass('condition');
                    }
                }
            }
            if ($($resizeHandles[0]).hasClass('condition')) {
                $(g.t).find('thead th:first').addClass('before-condition');
            }
            $(g.cRsz).css('height', $(g.t).height());
        },

        /**
         * Shift column from index oldn to newn.
         *
         * @param oldn old zero-based column index
         * @param newn new zero-based column index
         */
        shiftCol: function (oldn, newn) {
            $(g.t).find('tr').each(function () {
                if (newn < oldn) {
                    $(this).find('th.draggable:eq(' + newn + '),' +
                                 'td:eq(' + (g.actionSpan + newn) + ')')
                           .before($(this).find('th.draggable:eq(' + oldn + '),' +
                                                'td:eq(' + (g.actionSpan + oldn) + ')'));
                } else {
                    $(this).find('th.draggable:eq(' + newn + '),' +
                                 'td:eq(' + (g.actionSpan + newn) + ')')
                           .after($(this).find('th.draggable:eq(' + oldn + '),' +
                                               'td:eq(' + (g.actionSpan + oldn) + ')'));
                }
            });
            // reposition the column resize bars
            g.reposRsz();

            // adjust the column visibility list
            if (newn < oldn) {
                $(g.cList).find('.lDiv div:eq(' + newn + ')')
                          .before($(g.cList).find('.lDiv div:eq(' + oldn + ')'));
            } else {
                $(g.cList).find('.lDiv div:eq(' + newn + ')')
                          .after($(g.cList).find('.lDiv div:eq(' + oldn + ')'));
            }
            // adjust the colOrder
            var tmp = g.colOrder[oldn];
            g.colOrder.splice(oldn, 1);
            g.colOrder.splice(newn, 0, tmp);
            // adjust the colVisib
            if (g.colVisib.length > 0) {
                tmp = g.colVisib[oldn];
                g.colVisib.splice(oldn, 1);
                g.colVisib.splice(newn, 0, tmp);
            }
        },

        /**
         * Find currently hovered table column's header (excluding actions column).
         *
         * @param e event
         * @return the hovered column's th object or undefined if no hovered column found.
         */
        getHoveredCol: function (e) {
            var hoveredCol;
            $headers = $(g.t).find('th.draggable:visible');
            $headers.each(function () {
                var left = $(this).offset().left;
                var right = left + $(this).outerWidth();
                if (left <= e.pageX && e.pageX <= right) {
                    hoveredCol = this;
                }
            });
            return hoveredCol;
        },

        /**
         * Get a zero-based index from a <th class="draggable"> tag in a table.
         *
         * @param obj table header <th> object
         * @return zero-based index of the specified table header in the set of table headers (visible or not)
         */
        getHeaderIdx: function (obj) {
            return $(obj).parents('tr').find('th.draggable').index(obj);
        },

        /**
         * Reposition the columns back to normal order.
         */
        restoreColOrder: function () {
            // use insertion sort, since we already have shiftCol function
            for (var i = 1; i < g.colOrder.length; i++) {
                var x = g.colOrder[i];
                var j = i - 1;
                while (j >= 0 && x < g.colOrder[j]) {
                    j--;
                }
                if (j != i - 1) {
                    g.shiftCol(i, j + 1);
                }
            }
            if (g.tableCreateTime) {
                // send request to server to remember the column order
                g.sendColPrefs();
            }
            g.refreshRestoreButton();
        },

        /**
         * Send column preferences (column order and visibility) to the server.
         */
        sendColPrefs: function () {
            if ($(g.t).is('.ajax')) {   // only send preferences if ajax class
                var post_params = {
                    ajax_request: true,
                    db: g.db,
                    table: g.table,
                    token: g.token,
                    server: g.server,
                    set_col_prefs: true,
                    table_create_time: g.tableCreateTime
                };
                if (g.colOrder.length > 0) {
                    $.extend(post_params, {col_order: g.colOrder.toString()});
                }
                if (g.colVisib.length > 0) {
                    $.extend(post_params, {col_visib: g.colVisib.toString()});
                }
                $.post('sql.php', post_params, function (data) {
                    if (data.success !== true) {
                        var $temp_div = $(document.createElement('div'));
                        $temp_div.html(data.error);
                        $temp_div.addClass("error");
                        PMA_ajaxShowMessage($temp_div, false);
                    }
                });
            }
        },

        /**
         * Refresh restore button state.
         * Make restore button disabled if the table is similar with initial state.
         */
        refreshRestoreButton: function () {
            // check if table state is as initial state
            var isInitial = true;
            for (var i = 0; i < g.colOrder.length; i++) {
                if (g.colOrder[i] != i) {
                    isInitial = false;
                    break;
                }
            }
            // check if only one visible column left
            var isOneColumn = g.visibleHeadersCount == 1;
            // enable or disable restore button
            if (isInitial || isOneColumn) {
                $(g.o).find('div.restore_column').hide();
            } else {
                $(g.o).find('div.restore_column').show();
            }
        },

        /**
         * Update current hint using the boolean values (showReorderHint, showSortHint, etc.).
         *
         */
        updateHint: function () {
            var text = '';
            if (!g.colRsz && !g.colReorder) {     // if not resizing or dragging
                if (g.visibleHeadersCount > 1) {
                    g.showReorderHint = true;
                }
                if ($(t).find('th.marker').length > 0) {
                    g.showMarkHint = true;
                }
                if (g.showSortHint && g.sortHint) {
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.sortHint;
                }
                if (g.showMultiSortHint && g.strMultiSortHint) {
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.strMultiSortHint;
                }
                if (g.showMarkHint &&
                    g.markHint &&
                    ! g.showSortHint && // we do not show mark hint, when sort hint is shown
                    g.showReorderHint &&
                    g.reorderHint
                ) {
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.reorderHint;
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.markHint;
                    text += text.length > 0 ? '<br />' : '';
                    text += '- ' + g.copyHint;
                }
            }
            return text;
        },

        /**
         * Toggle column's visibility.
         * After calling this function and it returns true, afterToggleCol() must be called.
         *
         * @return boolean True if the column is toggled successfully.
         */
        toggleCol: function (n) {
            if (g.colVisib[n]) {
                // can hide if more than one column is visible
                if (g.visibleHeadersCount > 1) {
                    $(g.t).find('tr').each(function () {
                        $(this).find('th.draggable:eq(' + n + '),' +
                                     'td:eq(' + (g.actionSpan + n) + ')')
                               .hide();
                    });
                    g.colVisib[n] = 0;
                    $(g.cList).find('.lDiv div:eq(' + n + ') input').prop('checked', false);
                } else {
                    // cannot hide, force the checkbox to stay checked
                    $(g.cList).find('.lDiv div:eq(' + n + ') input').prop('checked', true);
                    return false;
                }
            } else {    // column n is not visible
                $(g.t).find('tr').each(function () {
                    $(this).find('th.draggable:eq(' + n + '),' +
                                 'td:eq(' + (g.actionSpan + n) + ')')
                           .show();
                });
                g.colVisib[n] = 1;
                $(g.cList).find('.lDiv div:eq(' + n + ') input').prop('checked', true);
            }
            return true;
        },

        /**
         * This must be called if toggleCol() returns is true.
         *
         * This function is separated from toggleCol because, sometimes, we want to toggle
         * some columns together at one time and do just one adjustment after it, e.g. in showAllColumns().
         */
        afterToggleCol: function () {
            // some adjustments after hiding column
            g.reposRsz();
            g.reposDrop();
            g.sendColPrefs();

            // check visible first row headers count
            g.visibleHeadersCount = $(g.t).find('tr:first th.draggable:visible').length;
            g.refreshRestoreButton();
        },

        /**
         * Show columns' visibility list.
         *
         * @param obj The drop down arrow of column visibility list
         */
        showColList: function (obj) {
            // only show when not resizing or reordering
            if (!g.colRsz && !g.colReorder) {
                var pos = $(obj).position();
                // check if the list position is too right
                if (pos.left + $(g.cList).outerWidth(true) > $(document).width()) {
                    pos.left = $(document).width() - $(g.cList).outerWidth(true);
                }
                $(g.cList).css({
                        left: pos.left,
                        top: pos.top + $(obj).outerHeight(true)
                    })
                    .show();
                $(obj).addClass('coldrop-hover');
            }
        },

        /**
         * Hide columns' visibility list.
         */
        hideColList: function () {
            $(g.cList).hide();
            $(g.cDrop).find('.coldrop-hover').removeClass('coldrop-hover');
        },

        /**
         * Reposition the column visibility drop-down arrow.
         */
        reposDrop: function () {
            var $th = $(t).find('th:not(.draggable)');
            for (var i = 0; i < $th.length; i++) {
                var $cd = $(g.cDrop).find('div:eq(' + i + ')');   // column drop-down arrow
                var pos = $($th[i]).position();
                $cd.css({
                        left: pos.left + $($th[i]).width() - $cd.width(),
                        top: pos.top
                    });
            }
        },

        /**
         * Show all hidden columns.
         */
        showAllColumns: function () {
            for (var i = 0; i < g.colVisib.length; i++) {
                if (!g.colVisib[i]) {
                    g.toggleCol(i);
                }
            }
            g.afterToggleCol();
        },

        /**
         * Show edit cell, if it can be shown
         *
         * @param cell <td> element to be edited
         */
        showEditCell: function (cell) {
            if ($(cell).is('.grid_edit') &&
                !g.colRsz && !g.colReorder)
            {
                if (!g.isCellEditActive) {
                    var $cell = $(cell);

                    if ('string' === $cell.attr('data-type') ||
                        'blob' === $cell.attr('data-type')
                    ) {
                        g.cEdit = g.cEditTextarea;
                    } else {
                        g.cEdit = g.cEditStd;
                    }

                    // remove all edit area and hide it
                    $(g.cEdit).find('.edit_area').empty().hide();
                    // reposition the cEdit element
                    $(g.cEdit).css({
                            top: $cell.position().top,
                            left: $cell.position().left
                        })
                        .show()
                        .find('.edit_box')
                        .css({
                            width: $cell.outerWidth(),
                            height: $cell.outerHeight()
                        });
                    // fill the cell edit with text from <td>
                    var value = PMA_getCellValue(cell);
                    $(g.cEdit).find('.edit_box').val(value);

                    g.currentEditCell = cell;
                    $(g.cEdit).find('.edit_box').focus();
                    moveCursorToEnd($(g.cEdit).find('.edit_box'));
                    $(g.cEdit).find('*').prop('disabled', false);
                }
            }

            function moveCursorToEnd(input) {
                var originalValue = input.val();
                var originallength = originalValue.length;
                input.val('');
                input.blur().focus().val(originalValue);
                input[0].setSelectionRange(originallength, originallength);
            }
        },

        /**
         * Remove edit cell and the edit area, if it is shown.
         *
         * @param force Optional, force to hide edit cell without saving edited field.
         * @param data  Optional, data from the POST AJAX request to save the edited field
         *              or just specify "true", if we want to replace the edited field with the new value.
         * @param field Optional, the edited <td>. If not specified, the function will
         *              use currently edited <td> from g.currentEditCell.
         * @param field Optional, this object contains a boolean named move (true, if called from move* functions)
         *              and a <td> to which the grid_edit should move
         */
        hideEditCell: function (force, data, field, options) {
            if (g.isCellEditActive && !force) {
                // cell is being edited, save or post the edited data
                if (options !== undefined) {
                    g.saveOrPostEditedCell(options);
                } else {
                    g.saveOrPostEditedCell();
                }
                return;
            }

            // cancel any previous request
            if (g.lastXHR !== null) {
                g.lastXHR.abort();
                g.lastXHR = null;
            }

            if (data) {
                if (g.currentEditCell) {    // save value of currently edited cell
                    // replace current edited field with the new value
                    var $this_field = $(g.currentEditCell);
                    var is_null = $this_field.data('value') === null;
                    if (is_null) {
                        $this_field.find('span').html('NULL');
                        $this_field.addClass('null');
                    } else {
                        $this_field.removeClass('null');
                        var value = data.isNeedToRecheck
                            ? data.truncatableFieldValue
                            : $this_field.data('value');

                        // Truncates the text.
                        $this_field.removeClass('truncated');
                        if (PMA_commonParams.get('pftext') === 'P' && value.length > g.maxTruncatedLen) {
                            $this_field.addClass('truncated');
                            value = value.substring(0, g.maxTruncatedLen) + '...';
                        }

                        //Add <br> before carriage return.
                        new_html = escapeHtml(value);
                        new_html = new_html.replace(/\n/g, '<br>\n');

                        //remove decimal places if column type not supported
                        if (($this_field.attr('data-decimals') == 0) && ( $this_field.attr('data-type').indexOf('time') != -1)) {
                            new_html = new_html.substring(0, new_html.indexOf('.'));
                        }

                        //remove addtional decimal places
                        if (($this_field.attr('data-decimals') > 0) && ( $this_field.attr('data-type').indexOf('time') != -1)){
                            new_html = new_html.substring(0, new_html.length - (6 - $this_field.attr('data-decimals')));
                        }

                        var selector = 'span';
                        if ($this_field.hasClass('hex') && $this_field.find('a').length) {
                            selector = 'a';
                        }

                        // Updates the code keeping highlighting (if any).
                        var $target = $this_field.find(selector);
                        if (!PMA_updateCode($target, new_html, value)) {
                            $target.html(new_html);
                        }
                    }
                    if ($this_field.is('.bit')) {
                        $this_field.find('span').text($this_field.data('value'));
                    }
                }
                if (data.transformations !== undefined) {
                    $.each(data.transformations, function (cell_index, value) {
                        var $this_field = $(g.t).find('.to_be_saved:eq(' + cell_index + ')');
                        $this_field.find('span').html(value);
                    });
                }
                if (data.relations !== undefined) {
                    $.each(data.relations, function (cell_index, value) {
                        var $this_field = $(g.t).find('.to_be_saved:eq(' + cell_index + ')');
                        $this_field.find('span').html(value);
                    });
                }

                // refresh the grid
                g.reposRsz();
                g.reposDrop();
            }

            // hide the cell editing area
            $(g.cEdit).hide();
            $(g.cEdit).find('.edit_box').blur();
            g.isCellEditActive = false;
            g.currentEditCell = null;
            // destroy datepicker in edit area, if exist
            var $dp = $(g.cEdit).find('.hasDatepicker');
            if ($dp.length > 0) {
                $(document).bind('mousedown', $.datepicker._checkExternalClick);
                $dp.datepicker('destroy');
                // change the cursor in edit box back to normal
                // (the cursor become a hand pointer when we add datepicker)
                $(g.cEdit).find('.edit_box').css('cursor', 'inherit');
            }
        },

        /**
         * Show drop-down edit area when edit cell is focused.
         */
        showEditArea: function () {
            if (!g.isCellEditActive) {   // make sure the edit area has not been shown
                g.isCellEditActive = true;
                g.isEditCellTextEditable = false;
                /**
                 * @var $td current edited cell
                 */
                var $td = $(g.currentEditCell);
                /**
                 * @var $editArea the editing area
                 */
                var $editArea = $(g.cEdit).find('.edit_area');
                /**
                 * @var where_clause WHERE clause for the edited cell
                 */
                var where_clause = $td.parent('tr').find('.where_clause').val();
                /**
                 * @var field_name  String containing the name of this field.
                 * @see getFieldName()
                 */
                var field_name = getFieldName($(t), $td);
                /**
                 * @var relation_curr_value String current value of the field (for fields that are foreign keyed).
                 */
                var relation_curr_value = $td.text();
                /**
                 * @var relation_key_or_display_column String relational key if in 'Relational display column' mode,
                 * relational display column if in 'Relational key' mode (for fields that are foreign keyed).
                 */
                var relation_key_or_display_column = $td.find('a').attr('title');
                /**
                 * @var curr_value String current value of the field (for fields that are of type enum or set).
                 */
                var curr_value = $td.find('span').text();

                // empty all edit area, then rebuild it based on $td classes
                $editArea.empty();

                // remember this instead of testing more than once
                var is_null = $td.is('.null');

                // add goto link, if this cell contains a link
                if ($td.find('a').length > 0) {
                    var gotoLink = document.createElement('div');
                    gotoLink.className = 'goto_link';
                    $(gotoLink).append(g.gotoLinkText + ' ').append($td.find('a').clone());
                    $editArea.append(gotoLink);
                }

                g.wasEditedCellNull = false;
                if ($td.is(':not(.not_null)')) {
                    // append a null checkbox
                    $editArea.append('<div class="null_div">Null:<input type="checkbox"></div>');

                    var $checkbox = $editArea.find('.null_div input');
                    // check if current <td> is NULL
                    if (is_null) {
                        $checkbox.prop('checked', true);
                        g.wasEditedCellNull = true;
                    }

                    // if the select/editor is changed un-check the 'checkbox_null_<field_name>_<row_index>'.
                    if ($td.is('.enum, .set')) {
                        $editArea.on('change', 'select', function () {
                            $checkbox.prop('checked', false);
                        });
                    } else if ($td.is('.relation')) {
                        $editArea.on('change', 'select', function () {
                            $checkbox.prop('checked', false);
                        });
                        $editArea.on('click', '.browse_foreign', function () {
                            $checkbox.prop('checked', false);
                        });
                    } else {
                        $(g.cEdit).on('keypress change paste', '.edit_box', function () {
                            $checkbox.prop('checked', false);
                        });
                        // Capture ctrl+v (on IE and Chrome)
                        $(g.cEdit).on('keydown', '.edit_box', function (e) {
                            if (e.ctrlKey && e.which == 86) {
                                $checkbox.prop('checked', false);
                            }
                        });
                        $editArea.on('keydown', 'textarea', function () {
                            $checkbox.prop('checked', false);
                        });
                    }

                    // if null checkbox is clicked empty the corresponding select/editor.
                    $checkbox.click(function () {
                        if ($td.is('.enum')) {
                            $editArea.find('select').val('');
                        } else if ($td.is('.set')) {
                            $editArea.find('select').find('option').each(function () {
                                var $option = $(this);
                                $option.prop('selected', false);
                            });
                        } else if ($td.is('.relation')) {
                            // if the dropdown is there to select the foreign value
                            if ($editArea.find('select').length > 0) {
                                $editArea.find('select').val('');
                            }
                        } else {
                            $editArea.find('textarea').val('');
                        }
                        $(g.cEdit).find('.edit_box').val('');
                    });
                }

                //reset the position of the edit_area div after closing datetime picker
                $(g.cEdit).find('.edit_area').css({'top' :'0','position':''});

                if ($td.is('.relation')) {
                    //handle relations
                    $editArea.addClass('edit_area_loading');

                    // initialize the original data
                    $td.data('original_data', null);

                    /**
                     * @var post_params Object containing parameters for the POST request
                     */
                    var post_params = {
                        'ajax_request' : true,
                        'get_relational_values' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : field_name,
                        'token' : g.token,
                        'curr_value' : relation_curr_value,
                        'relation_key_or_display_column' : relation_key_or_display_column
                    };

                    g.lastXHR = $.post('sql.php', post_params, function (data) {
                        g.lastXHR = null;
                        $editArea.removeClass('edit_area_loading');
                        if ($(data.dropdown).is('select')) {
                            // save original_data
                            var value = $(data.dropdown).val();
                            $td.data('original_data', value);
                            // update the text input field, in case where the "Relational display column" is checked
                            $(g.cEdit).find('.edit_box').val(value);
                        }

                        $editArea.append(data.dropdown);
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');

                        // for 'Browse foreign values' options,
                        // hide the value next to 'Browse foreign values' link
                        $editArea.find('span.curr_value').hide();
                        // handle update for new values selected from new window
                        $editArea.find('span.curr_value').change(function () {
                            $(g.cEdit).find('.edit_box').val($(this).text());
                        });
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                    g.isEditCellTextEditable = true;
                }
                else if ($td.is('.enum')) {
                    //handle enum fields
                    $editArea.addClass('edit_area_loading');

                    /**
                     * @var post_params Object containing parameters for the POST request
                     */
                    var post_params = {
                        'ajax_request' : true,
                        'get_enum_values' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : field_name,
                        'token' : g.token,
                        'curr_value' : curr_value
                    };
                    g.lastXHR = $.post('sql.php', post_params, function (data) {
                        g.lastXHR = null;
                        $editArea.removeClass('edit_area_loading');
                        $editArea.append(data.dropdown);
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                }
                else if ($td.is('.set')) {
                    //handle set fields
                    $editArea.addClass('edit_area_loading');

                    /**
                     * @var post_params Object containing parameters for the POST request
                     */
                    var post_params = {
                        'ajax_request' : true,
                        'get_set_values' : true,
                        'server' : g.server,
                        'db' : g.db,
                        'table' : g.table,
                        'column' : field_name,
                        'token' : g.token,
                        'curr_value' : curr_value
                    };

                    // if the data is truncated, get the full data
                    if ($td.is('.truncated')) {
                        post_params.get_full_values = true;
                        post_params.where_clause = where_clause;
                    }

                    g.lastXHR = $.post('sql.php', post_params, function (data) {
                        g.lastXHR = null;
                        $editArea.removeClass('edit_area_loading');
                        $editArea.append(data.select);
                        $td.data('original_data', $(data.select).val().join());
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }); // end $.post()

                    $editArea.show();
                    $editArea.on('change', 'select', function () {
                        $(g.cEdit).find('.edit_box').val($(this).val());
                    });
                }
                else if ($td.is('.truncated, .transformed')) {
                    if ($td.is('.to_be_saved')) {   // cell has been edited
                        var value = $td.data('value');
                        $(g.cEdit).find('.edit_box').val(value);
                        $editArea.append('<textarea></textarea>');
                        $editArea.find('textarea').val(value);
                        $editArea
                            .on('keyup', 'textarea', function () {
                                $(g.cEdit).find('.edit_box').val($(this).val());
                            });
                        $(g.cEdit).on('keyup', '.edit_box', function () {
                            $editArea.find('textarea').val($(this).val());
                        });
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    } else {
                        //handle truncated/transformed values values
                        $editArea.addClass('edit_area_loading');

                        // initialize the original data
                        $td.data('original_data', null);

                        /**
                         * @var sql_query   String containing the SQL query used to retrieve value of truncated/transformed data
                         */
                        var sql_query = 'SELECT `' + field_name + '` FROM `' + g.table + '` WHERE ' + where_clause;

                        // Make the Ajax call and get the data, wrap it and insert it
                        g.lastXHR = $.post('sql.php', {
                            'token' : g.token,
                            'server' : g.server,
                            'db' : g.db,
                            'ajax_request' : true,
                            'sql_query' : sql_query,
                            'grid_edit' : true
                        }, function (data) {
                            g.lastXHR = null;
                            $editArea.removeClass('edit_area_loading');
                            if (typeof data !== 'undefined' && data.success === true) {
                                $td.data('original_data', data.value);
                                $(g.cEdit).find('.edit_box').val(data.value);
                            } else {
                                PMA_ajaxShowMessage(data.error, false);
                            }
                        }); // end $.post()
                    }
                    g.isEditCellTextEditable = true;
                } else if ($td.is('.timefield, .datefield, .datetimefield, .timestampfield')) {
                    var $input_field = $(g.cEdit).find('.edit_box');

                    // remember current datetime value in $input_field, if it is not null
                    var datetime_value = !is_null ? $input_field.val() : '';

                    var showMillisec = false;
                    var showMicrosec = false;
                    var timeFormat = 'HH:mm:ss';
                    // check for decimal places of seconds
                    if (($td.attr('data-decimals') > 0) && ($td.attr('data-type').indexOf('time') != -1)){
                        if (datetime_value && datetime_value.indexOf('.') === false) {
                            datetime_value += '.';
                        }
                        if ($td.attr('data-decimals') > 3) {
                            showMillisec = true;
                            showMicrosec = true;
                            timeFormat = 'HH:mm:ss.lc';

                            if (datetime_value) {
                                datetime_value += '000000';
                                var datetime_value = datetime_value.substring(0, datetime_value.indexOf('.') + 7);
                                $input_field.val(datetime_value);
                            }
                        } else {
                            showMillisec = true;
                            timeFormat = 'HH:mm:ss.l';

                            if (datetime_value) {
                                datetime_value += '000';
                                var datetime_value = datetime_value.substring(0, datetime_value.indexOf('.') + 4);
                                $input_field.val(datetime_value);
                            }
                        }
                    }

                    // add datetime picker
                    PMA_addDatepicker($input_field, $td.attr('data-type'), {
                        showMillisec: showMillisec,
                        showMicrosec: showMicrosec,
                        timeFormat: timeFormat
                    });

                    $input_field.on('keyup', function (e) {
                        if (e.which == 13) {
                            // post on pressing "Enter"
                            e.preventDefault();
                            e.stopPropagation();
                            g.saveOrPostEditedCell();
                        } else if (e.which == 27) {
                        } else {
                            toggleDatepickerIfInvalid($td, $input_field);
                        }
                    });

                    $input_field.datepicker("show");
                    toggleDatepickerIfInvalid($td, $input_field);

                    // unbind the mousedown event to prevent the problem of
                    // datepicker getting closed, needs to be checked for any
                    // change in names when updating
                    $(document).unbind('mousedown', $.datepicker._checkExternalClick);

                    //move ui-datepicker-div inside cEdit div
                    var datepicker_div = $('#ui-datepicker-div');
                    datepicker_div.css({'top': 0, 'left': 0, 'position': 'relative'});
                    $(g.cEdit).append(datepicker_div);

                    // cancel any click on the datepicker element
                    $editArea.find('> *').click(function (e) {
                        e.stopPropagation();
                    });

                    g.isEditCellTextEditable = true;
                } else {
                    g.isEditCellTextEditable = true;
                    // only append edit area hint if there is a null checkbox
                    if ($editArea.children().length > 0) {
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                    }
                }
                if ($editArea.children().length > 0) {
                    $editArea.show();
                }
            }
        },

        /**
         * Post the content of edited cell.
         *
         * @param field Optional, this object contains a boolean named move (true, if called from move* functions)
         *              and a <td> to which the grid_edit should move
         */
        postEditedCell: function (options) {
            if (g.isSaving) {
                return;
            }
            g.isSaving = true;
            /**
             * @var relation_fields Array containing the name/value pairs of relational fields
             */
            var relation_fields = {};
            /**
             * @var relational_display string 'K' if relational key, 'D' if relational display column
             */
            var relational_display = $(g.o).find("input[name=relational_display]:checked").val();
            /**
             * @var transform_fields    Array containing the name/value pairs for transformed fields
             */
            var transform_fields = {};
            /**
             * @var transformation_fields   Boolean, if there are any transformed fields in the edited cells
             */
            var transformation_fields = false;
            /**
             * @var full_sql_query String containing the complete SQL query to update this table
             */
            var full_sql_query = '';
            /**
             * @var rel_fields_list  String, url encoded representation of {@link relations_fields}
             */
            var rel_fields_list = '';
            /**
             * @var transform_fields_list  String, url encoded representation of {@link transform_fields}
             */
            var transform_fields_list = '';
            /**
             * @var where_clause Array containing where clause for updated fields
             */
            var full_where_clause = [];
            /**
             * @var is_unique   Boolean, whether the rows in this table is unique or not
             */
            var is_unique = $(g.t).find('td.edit_row_anchor').is('.nonunique') ? 0 : 1;
            /**
             * multi edit variables
             */
            var me_fields_name = [];
            var me_fields_type = [];
            var me_fields = [];
            var me_fields_null = [];

            // alert user if edited table is not unique
            if (!is_unique) {
                alert(g.alertNonUnique);
            }

            // loop each edited row
            $(g.t).find('td.to_be_saved').parents('tr').each(function () {
                var $tr = $(this);
                var where_clause = $tr.find('.where_clause').val();
                if (typeof where_clause === 'undefined') {
                    where_clause = '';
                }
                full_where_clause.push(where_clause);
                var condition_array = JSON.parse($tr.find('.condition_array').val());

                /**
                 * multi edit variables, for current row
                 * @TODO array indices are still not correct, they should be md5 of field's name
                 */
                var fields_name = [];
                var fields_type = [];
                var fields = [];
                var fields_null = [];

                // loop each edited cell in a row
                $tr.find('.to_be_saved').each(function () {
                    /**
                     * @var $this_field    Object referring to the td that is being edited
                     */
                    var $this_field = $(this);

                    /**
                     * @var field_name  String containing the name of this field.
                     * @see getFieldName()
                     */
                    var field_name = getFieldName($(g.t), $this_field);

                    /**
                     * @var this_field_params   Array temporary storage for the name/value of current field
                     */
                    var this_field_params = {};

                    if ($this_field.is('.transformed')) {
                        transformation_fields =  true;
                    }
                    this_field_params[field_name] = $this_field.data('value');

                    /**
                     * @var is_null String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
                     */
                    var is_null = this_field_params[field_name] === null;

                    fields_name.push(field_name);

                    if (is_null) {
                        fields_null.push('on');
                        fields.push('');
                    } else {
                        if ($this_field.is('.bit')) {
                            fields_type.push('bit');
                        } else if ($this_field.hasClass('hex')) {
                            fields_type.push('hex');
                        }
                        fields_null.push('');
                        // Convert \n to \r\n to be consistent with form submitted value.
                        // The internal browser representation has to be just \n
                        // while form submitted value \r\n, see specification:
                        // https://www.w3.org/TR/html5/forms.html#the-textarea-element
                        fields.push($this_field.data('value').replace(/\n/g, '\r\n'));

                        var cell_index = $this_field.index('.to_be_saved');
                        if ($this_field.is(":not(.relation, .enum, .set, .bit)")) {
                            if ($this_field.is('.transformed')) {
                                transform_fields[cell_index] = {};
                                $.extend(transform_fields[cell_index], this_field_params);
                            }
                        } else if ($this_field.is('.relation')) {
                            relation_fields[cell_index] = {};
                            $.extend(relation_fields[cell_index], this_field_params);
                        }
                    }
                    // check if edited field appears in WHERE clause
                    if (where_clause.indexOf(PMA_urlencode(field_name)) > -1) {
                        var field_str = '`' + g.table + '`.' + '`' + field_name + '`';
                        for (var field in condition_array) {
                            if (field.indexOf(field_str) > -1) {
                                condition_array[field] = is_null ? 'IS NULL' : "= '" + this_field_params[field_name].replace(/'/g, "''") + "'";
                                break;
                            }
                        }
                    }

                }); // end of loop for every edited cells in a row

                // save new_clause
                var new_clause = '';
                for (var field in condition_array) {
                    new_clause += field + ' ' + condition_array[field] + ' AND ';
                }
                new_clause = new_clause.substring(0, new_clause.length - 5); // remove the last AND
                $tr.data('new_clause', new_clause);
                // save condition_array
                $tr.find('.condition_array').val(JSON.stringify(condition_array));

                me_fields_name.push(fields_name);
                me_fields_type.push(fields_type);
                me_fields.push(fields);
                me_fields_null.push(fields_null);

            }); // end of loop for every edited rows

            rel_fields_list = $.param(relation_fields);
            transform_fields_list = $.param(transform_fields);

            // Make the Ajax post after setting all parameters
            /**
             * @var post_params Object containing parameters for the POST request
             */
            var post_params = {'ajax_request' : true,
                            'sql_query' : full_sql_query,
                            'token' : g.token,
                            'server' : g.server,
                            'db' : g.db,
                            'table' : g.table,
                            'clause_is_unique' : is_unique,
                            'where_clause' : full_where_clause,
                            'fields[multi_edit]' : me_fields,
                            'fields_name[multi_edit]' : me_fields_name,
                            'fields_type[multi_edit]' : me_fields_type,
                            'fields_null[multi_edit]' : me_fields_null,
                            'rel_fields_list' : rel_fields_list,
                            'do_transformations' : transformation_fields,
                            'transform_fields_list' : transform_fields_list,
                            'relational_display' : relational_display,
                            'goto' : 'sql.php',
                            'submit_type' : 'save'
                          };

            if (!g.saveCellsAtOnce) {
                $(g.cEdit).find('*').prop('disabled', true);
                $(g.cEdit).find('.edit_box').addClass('edit_box_posting');
            } else {
                $(g.o).find('div.save_edited').addClass('saving_edited_data')
                    .find('input').prop('disabled', true);    // disable the save button
            }

            $.ajax({
                type: 'POST',
                url: 'tbl_replace.php',
                data: post_params,
                success:
                    function (data) {
                        g.isSaving = false;
                        if (!g.saveCellsAtOnce) {
                            $(g.cEdit).find('*').prop('disabled', false);
                            $(g.cEdit).find('.edit_box').removeClass('edit_box_posting');
                        } else {
                            $(g.o).find('div.save_edited').removeClass('saving_edited_data')
                                .find('input').prop('disabled', false);  // enable the save button back
                        }
                        if (typeof data !== 'undefined' && data.success === true) {
                            if (typeof options === 'undefined' || ! options.move) {
                                PMA_ajaxShowMessage(data.message);
                            }

                            // update where_clause related data in each edited row
                            $(g.t).find('td.to_be_saved').parents('tr').each(function () {
                                var new_clause = $(this).data('new_clause');
                                var $where_clause = $(this).find('.where_clause');
                                var old_clause = $where_clause.val();
                                var decoded_old_clause = old_clause;
                                var decoded_new_clause = new_clause;

                                $where_clause.val(new_clause);
                                // update Edit, Copy, and Delete links also
                                $(this).find('a').each(function () {
                                    $(this).attr('href', $(this).attr('href').replace(old_clause, new_clause));
                                    // update delete confirmation in Delete link
                                    if ($(this).attr('href').indexOf('DELETE') > -1) {
                                        $(this).removeAttr('onclick')
                                            .unbind('click')
                                            .bind('click', function () {
                                                return confirmLink(this, 'DELETE FROM `' + g.db + '`.`' + g.table + '` WHERE ' +
                                                       decoded_new_clause + (is_unique ? '' : ' LIMIT 1'));
                                            });
                                    }
                                });
                                // update the multi edit checkboxes
                                $(this).find('input[type=checkbox]').each(function () {
                                    var $checkbox = $(this);
                                    var checkbox_name = $checkbox.attr('name');
                                    var checkbox_value = $checkbox.val();

                                    $checkbox.attr('name', checkbox_name.replace(old_clause, new_clause));
                                    $checkbox.val(checkbox_value.replace(decoded_old_clause, decoded_new_clause));
                                });
                            });
                            // update the display of executed SQL query command
                            if (typeof data.sql_query != 'undefined') {
                                //extract query box
                                var $result_query = $($.parseHTML(data.sql_query));
                                var sqlOuter = $result_query.find('.sqlOuter').wrap('<p>').parent().html();
                                var tools = $result_query.find('.tools').wrap('<p>').parent().html();
                                // sqlOuter and tools will not be present if 'Show SQL queries' configuration is off
                                if (typeof sqlOuter != 'undefined' && typeof tools != 'undefined') {
                                    $(g.o).find('.result_query:not(:last)').remove();
                                    var $existing_query = $(g.o).find('.result_query');
                                    // If two query box exists update query in second else add a second box
                                    if ($existing_query.find('div.sqlOuter').length > 1) {
                                        $existing_query.children(":nth-child(4)").remove();
                                        $existing_query.children(":nth-child(4)").remove();
                                        $existing_query.append(sqlOuter + tools);
                                    } else {
                                        $existing_query.append(sqlOuter + tools);
                                    }
                                    PMA_highlightSQL($existing_query);
                                }
                            }
                            // hide and/or update the successfully saved cells
                            g.hideEditCell(true, data);

                            // remove the "Save edited cells" button
                            $(g.o).find('div.save_edited').hide();
                            // update saved fields
                            $(g.t).find('.to_be_saved')
                                .removeClass('to_be_saved')
                                .data('value', null)
                                .data('original_data', null);

                            g.isCellEdited = false;
                        } else {
                            PMA_ajaxShowMessage(data.error, false);
                            if (!g.saveCellsAtOnce) {
                                $(g.t).find('.to_be_saved')
                                    .removeClass('to_be_saved');
                            }
                        }
                    }
            }).done(function(){
                if (options !== undefined && options.move) {
                    g.showEditCell(options.cell);
                }
            }); // end $.ajax()
        },

        /**
         * Save edited cell, so it can be posted later.
         */
        saveEditedCell: function () {
            /**
             * @var $this_field    Object referring to the td that is being edited
             */
            var $this_field = $(g.currentEditCell);
            var $test_element = ''; // to test the presence of a element

            var need_to_post = false;

            /**
             * @var field_name  String containing the name of this field.
             * @see getFieldName()
             */
            var field_name = getFieldName($(g.t), $this_field);

            /**
             * @var this_field_params   Array temporary storage for the name/value of current field
             */
            var this_field_params = {};

            /**
             * @var is_null String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
             */
            var is_null = $(g.cEdit).find('input:checkbox').is(':checked');

            if ($(g.cEdit).find('.edit_area').is('.edit_area_loading')) {
                // the edit area is still loading (retrieving cell data), no need to post
                need_to_post = false;
            } else if (is_null) {
                if (!g.wasEditedCellNull) {
                    this_field_params[field_name] = null;
                    need_to_post = true;
                }
            } else {
                if ($this_field.is('.bit')) {
                    this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                } else if ($this_field.is('.set')) {
                    $test_element = $(g.cEdit).find('select');
                    this_field_params[field_name] = $test_element.map(function () {
                        return $(this).val();
                    }).get().join(",");
                } else if ($this_field.is('.relation, .enum')) {
                    // for relation and enumeration, take the results from edit box value,
                    // because selected value from drop-down, new window or multiple
                    // selection list will always be updated to the edit box
                    this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                } else if ($this_field.hasClass('hex')) {
                    if ($(g.cEdit).find('.edit_box').val().match(/^[a-f0-9]*$/i) !== null) {
                        this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                    } else {
                        var hexError = '<div class="error">' + PMA_messages.strEnterValidHex + '</div>';
                        PMA_ajaxShowMessage(hexError, false);
                        this_field_params[field_name] = PMA_getCellValue(g.currentEditCell);
                    }
                } else {
                    this_field_params[field_name] = $(g.cEdit).find('.edit_box').val();
                }
                if (g.wasEditedCellNull || this_field_params[field_name] != PMA_getCellValue(g.currentEditCell)) {
                    need_to_post = true;
                }
            }

            if (need_to_post) {
                $(g.currentEditCell).addClass('to_be_saved')
                    .data('value', this_field_params[field_name]);
                if (g.saveCellsAtOnce) {
                    $(g.o).find('div.save_edited').show();
                }
                g.isCellEdited = true;
            }

            return need_to_post;
        },

        /**
         * Save or post currently edited cell, depending on the "saveCellsAtOnce" configuration.
         *
         * @param field Optional, this object contains a boolean named move (true, if called from move* functions)
         *              and a <td> to which the grid_edit should move
         */
        saveOrPostEditedCell: function (options) {
            var saved = g.saveEditedCell();
            // Check if $cfg['SaveCellsAtOnce'] is false
            if (!g.saveCellsAtOnce) {
                // Check if need_to_post is true
                if (saved) {
                    // Check if this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.postEditedCell(options);
                    } else {
                        g.postEditedCell();
                    }
                // need_to_post is false
                } else {
                    // Check if this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true);
                    }
                }
            // $cfg['SaveCellsAtOnce'] is true
            } else {
                // If need_to_post
                if (saved) {
                    // If this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true, true, false, options);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true, true);
                    }
                } else {
                    // If this function called from 'move' functions
                    if (options !== undefined && options.move) {
                        g.hideEditCell(true, false, false, options);
                        g.showEditCell(options.cell);
                    // NOT called from 'move' functions
                    } else {
                        g.hideEditCell(true);
                    }
                }
            }
        },

        /**
         * Initialize column resize feature.
         */
        initColResize: function () {
            // create column resizer div
            g.cRsz = document.createElement('div');
            g.cRsz.className = 'cRsz';

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr:first th.draggable');

            // create column borders
            $firstRowCols.each(function () {
                var cb = document.createElement('div'); // column border
                $(cb).addClass('colborder')
                    .mousedown(function (e) {
                        g.dragStartRsz(e, this);
                    });
                $(g.cRsz).append(cb);
            });
            g.reposRsz();

            // attach to global div
            $(g.gDiv).prepend(g.cRsz);
        },

        /**
         * Initialize column reordering feature.
         */
        initColReorder: function () {
            g.cCpy = document.createElement('div');     // column copy, to store copy of dragged column header
            g.cPointer = document.createElement('div'); // column pointer, used when reordering column

            // adjust g.cCpy
            g.cCpy.className = 'cCpy';
            $(g.cCpy).hide();

            // adjust g.cPointer
            g.cPointer.className = 'cPointer';
            $(g.cPointer).css('visibility', 'hidden');  // set visibility to hidden instead of calling hide() to force browsers to cache the image in cPointer class

            // assign column reordering hint
            g.reorderHint = PMA_messages.strColOrderHint;

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr:first th.draggable');

            // initialize column order
            $col_order = $(g.o).find('.col_order');   // check if column order is passed from PHP
            if ($col_order.length > 0) {
                g.colOrder = $col_order.val().split(',');
                for (var i = 0; i < g.colOrder.length; i++) {
                    g.colOrder[i] = parseInt(g.colOrder[i], 10);
                }
            } else {
                g.colOrder = [];
                for (var i = 0; i < $firstRowCols.length; i++) {
                    g.colOrder.push(i);
                }
            }

            // register events
            $(g.t).find('th.draggable')
                .mousedown(function (e) {
                    $(g.o).addClass("turnOffSelect");
                    if (g.visibleHeadersCount > 1) {
                        g.dragStartReorder(e, this);
                    }
                })
                .mouseenter(function () {
                    if (g.visibleHeadersCount > 1) {
                        $(this).css('cursor', 'move');
                    } else {
                        $(this).css('cursor', 'inherit');
                    }
                })
                .mouseleave(function () {
                    g.showReorderHint = false;
                    $(this).tooltip("option", {
                        content: g.updateHint()
                    });
                })
                .dblclick(function (e) {
                    e.preventDefault();
                    $("<div/>")
                    .prop("title", PMA_messages.strColNameCopyTitle)
                    .addClass("modal-copy")
                    .text(PMA_messages.strColNameCopyText)
                    .append(
                        $("<input/>")
                        .prop("readonly", true)
                        .val($(this).data("column"))
                        )
                    .dialog({
                        resizable: false,
                        modal: true
                    })
                    .find("input").focus().select();
                });
            $(g.t).find('th.draggable a')
                .dblclick(function (e) {
                    e.stopPropagation();
                });
            // restore column order when the restore button is clicked
            $(g.o).find('div.restore_column').click(function () {
                g.restoreColOrder();
            });

            // attach to global div
            $(g.gDiv).append(g.cPointer);
            $(g.gDiv).append(g.cCpy);

            // prevent default "dragstart" event when dragging a link
            $(g.t).find('th a').bind('dragstart', function () {
                return false;
            });

            // refresh the restore column button state
            g.refreshRestoreButton();
        },

        /**
         * Initialize column visibility feature.
         */
        initColVisib: function () {
            g.cDrop = document.createElement('div');    // column drop-down arrows
            g.cList = document.createElement('div');    // column visibility list

            // adjust g.cDrop
            g.cDrop.className = 'cDrop';

            // adjust g.cList
            g.cList.className = 'cList';
            $(g.cList).hide();

            // assign column visibility related hints
            g.showAllColText = PMA_messages.strShowAllCol;

            // get data columns in the first row of the table
            var $firstRowCols = $(g.t).find('tr:first th.draggable');

            var i;
            // initialize column visibility
            var $col_visib = $(g.o).find('.col_visib');   // check if column visibility is passed from PHP
            if ($col_visib.length > 0) {
                g.colVisib = $col_visib.val().split(',');
                for (i = 0; i < g.colVisib.length; i++) {
                    g.colVisib[i] = parseInt(g.colVisib[i], 10);
                }
            } else {
                g.colVisib = [];
                for (i = 0; i < $firstRowCols.length; i++) {
                    g.colVisib.push(1);
                }
            }

            // make sure we have more than one column
            if ($firstRowCols.length > 1) {
                var $colVisibTh = $(g.t).find('th:not(.draggable)');
                PMA_tooltip(
                    $colVisibTh,
                    'th',
                    PMA_messages.strColVisibHint
                );

                // create column visibility drop-down arrow(s)
                $colVisibTh.each(function () {
                        var $th = $(this);
                        var cd = document.createElement('div'); // column drop-down arrow
                        var pos = $th.position();
                        $(cd).addClass('coldrop')
                            .click(function () {
                                if (g.cList.style.display == 'none') {
                                    g.showColList(this);
                                } else {
                                    g.hideColList();
                                }
                            });
                        $(g.cDrop).append(cd);
                    });

                // add column visibility control
                g.cList.innerHTML = '<div class="lDiv"></div>';
                var $listDiv = $(g.cList).find('div');

                var tempClick = function () {
                    if (g.toggleCol($(this).index())) {
                        g.afterToggleCol();
                    }
                };

                for (i = 0; i < $firstRowCols.length; i++) {
                    var currHeader = $firstRowCols[i];
                    var listElmt = document.createElement('div');
                    $(listElmt).text($(currHeader).text())
                        .prepend('<input type="checkbox" ' + (g.colVisib[i] ? 'checked="checked" ' : '') + '/>');
                    $listDiv.append(listElmt);
                    // add event on click
                    $(listElmt).click(tempClick);
                }
                // add "show all column" button
                var showAll = document.createElement('div');
                $(showAll).addClass('showAllColBtn')
                    .text(g.showAllColText);
                $(g.cList).append(showAll);
                $(showAll).click(function () {
                    g.showAllColumns();
                });
                // prepend "show all column" button at top if the list is too long
                if ($firstRowCols.length > 10) {
                    var clone = showAll.cloneNode(true);
                    $(g.cList).prepend(clone);
                    $(clone).click(function () {
                        g.showAllColumns();
                    });
                }
            }

            // hide column visibility list if we move outside the list
            $(g.t).find('td, th.draggable').mouseenter(function () {
                g.hideColList();
            });

            // attach to global div
            $(g.gDiv).append(g.cDrop);
            $(g.gDiv).append(g.cList);

            // some adjustment
            g.reposDrop();
        },

        /**
         * Move currently Editing Cell to Up
         */
        moveUp: function(e) {
            e.preventDefault();
            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var $prev_row;
            var j = 0;

            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
                if (!found) {
                    $prev_row = $(this);
                }
            });

            var new_cell;

            if (found && $prev_row) {
                $prev_row.children('td').each(function(){
                    if (getFieldName($(g.t), $(this)) == field_name) {
                        new_cell = this;
                    }
                });
            }

            if (new_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : new_cell});
            }
        },

        /**
         * Move currently Editing Cell to Down
         */
        moveDown: function(e) {
            e.preventDefault();

            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var $next_row;
            var j = 0;
            var next_row_found = false;
            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
                if (found) {
                    if (j >= 1 && ! next_row_found) {
                        $next_row = $(this);
                        next_row_found = true;
                    } else {
                        j++;
                    }
                }
            });

            var new_cell;
            if (found && $next_row) {
                $next_row.children('td').each(function(){
                    if (getFieldName($(g.t), $(this)) == field_name) {
                        new_cell = this;
                    }
                });
            }

            if (new_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : new_cell});
            }
        },

        /**
         * Move currently Editing Cell to Left
         */
        moveLeft: function(e) {
            e.preventDefault();

            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var j = 0;
            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
            });

            var left_cell;
            var cell_found = false;
            if (found) {
                $found_row.children('td.grid_edit').each(function(){
                    if (getFieldName($(g.t), $(this)) === field_name) {
                        cell_found = true;
                    }
                    if (!cell_found) {
                        left_cell = this;
                    }
                });
            }

            if (left_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : left_cell});
            }
        },

        /**
         * Move currently Editing Cell to Right
         */
        moveRight: function(e) {
            e.preventDefault();

            var $this_field = $(g.currentEditCell);
            var field_name = getFieldName($(g.t), $this_field);

            var where_clause = $this_field.parents('tr').first().find('.where_clause').val();
            if (typeof where_clause === 'undefined') {
                where_clause = '';
            }
            var found = false;
            var $found_row;
            var j = 0;
            $this_field.parents('tr').first().parents('tbody').children().each(function(){
                if ($(this).find('.where_clause').val() == where_clause) {
                    found = true;
                    $found_row = $(this);
                }
            });

            var right_cell;
            var cell_found = false;
            var next_cell_found = false;
            if (found) {
                $found_row.children('td.grid_edit').each(function(){
                    if (getFieldName($(g.t), $(this)) === field_name) {
                        cell_found = true;
                    }
                    if (cell_found) {
                        if (j >= 1 && ! next_cell_found) {
                            right_cell = this;
                            next_cell_found = true;
                        } else {
                            j++;
                        }
                    }
                });
            }

            if (right_cell) {
                g.hideEditCell(false, false, false, {move : true, cell : right_cell});
            }
        },

        /**
         * Initialize grid editing feature.
         */
        initGridEdit: function () {

            function startGridEditing(e, cell) {
                if (g.isCellEditActive) {
                    g.saveOrPostEditedCell();
                } else {
                    g.showEditCell(cell);
                }
                e.stopPropagation();
            }

            function handleCtrlNavigation(e) {
                if ((e.ctrlKey && e.which == 38 ) || (e.altKey && e.which == 38)) {
                    g.moveUp(e);
                } else if ((e.ctrlKey && e.which == 40)  || (e.altKey && e.which == 40)) {
                    g.moveDown(e);
                } else if ((e.ctrlKey && e.which == 37 ) || (e.altKey && e.which == 37)) {
                    g.moveLeft(e);
                } else if ((e.ctrlKey && e.which == 39)  || (e.altKey && e.which == 39)) {
                    g.moveRight(e);
                }
            }

            // create cell edit wrapper element
            g.cEditStd = document.createElement('div');
            g.cEdit = g.cEditStd;
            g.cEditTextarea = document.createElement('div');

            // adjust g.cEditStd
            g.cEditStd.className = 'cEdit';
            $(g.cEditStd).html('<input class="edit_box" rows="1" ></input><div class="edit_area" />');
            $(g.cEditStd).hide();

            // adjust g.cEdit
            g.cEditTextarea.className = 'cEdit';
            $(g.cEditTextarea).html('<textarea class="edit_box" rows="1" ></textarea><div class="edit_area" />');
            $(g.cEditTextarea).hide();

            // assign cell editing hint
            g.cellEditHint = PMA_messages.strCellEditHint;
            g.saveCellWarning = PMA_messages.strSaveCellWarning;
            g.alertNonUnique = PMA_messages.strAlertNonUnique;
            g.gotoLinkText = PMA_messages.strGoToLink;

            // initialize cell editing configuration
            g.saveCellsAtOnce = $(g.o).find('.save_cells_at_once').val();
            g.maxTruncatedLen = PMA_commonParams.get('LimitChars');

            // register events
            $(g.t).find('td.data.click1')
                .click(function (e) {
                    startGridEditing(e, this);
                    // prevent default action when clicking on "link" in a table
                    if ($(e.target).is('.grid_edit a')) {
                        e.preventDefault();
                    }
                });

            $(g.t).find('td.data.click2')
                .click(function (e) {
                    var $cell = $(this);
                    // In the case of relational link, We want single click on the link
                    // to goto the link and double click to start grid-editing.
                    var $link = $(e.target);
                    if ($link.is('.grid_edit.relation a')) {
                        e.preventDefault();
                        // get the click count and increase
                        var clicks = $cell.data('clicks');
                        clicks = (typeof clicks === 'undefined') ? 1 : clicks + 1;

                        if (clicks == 1) {
                            // if there are no previous clicks,
                            // start the single click timer
                            var timer = setTimeout(function () {
                                // temporarily remove ajax class so the page loader will not handle it,
                                // submit and then add it back
                                $link.removeClass('ajax');
                                AJAX.requestHandler.call($link[0]);
                                $link.addClass('ajax');
                                $cell.data('clicks', 0);
                            }, 700);
                            $cell.data('clicks', clicks);
                            $cell.data('timer', timer);
                        } else {
                            // this is a double click, cancel the single click timer
                            // and make the click count 0
                            clearTimeout($cell.data('timer'));
                            $cell.data('clicks', 0);
                            // start grid-editing
                            startGridEditing(e, this);
                        }
                    }
                })
                .dblclick(function (e) {
                    if ($(e.target).is('.grid_edit a')) {
                        e.preventDefault();
                    } else {
                        startGridEditing(e, this);
                    }
                });

            $(g.cEditStd).on('keydown', 'input.edit_box, select', handleCtrlNavigation);

            $(g.cEditStd).find('.edit_box').focus(function () {
                g.showEditArea();
            });
            $(g.cEditStd).on('keydown', '.edit_box, select', function (e) {
                if (e.which == 13) {
                    // post on pressing "Enter"
                    e.preventDefault();
                    g.saveOrPostEditedCell();
                }
            });
            $(g.cEditStd).keydown(function (e) {
                if (!g.isEditCellTextEditable) {
                    // prevent text editing
                    e.preventDefault();
                }
            });

            $(g.cEditTextarea).on('keydown', 'textarea.edit_box, select', handleCtrlNavigation);

            $(g.cEditTextarea).find('.edit_box').focus(function () {
                g.showEditArea();
            });
            $(g.cEditTextarea).on('keydown', '.edit_box, select', function (e) {
                if (e.which == 13 && !e.shiftKey) {
                    // post on pressing "Enter"
                    e.preventDefault();
                    g.saveOrPostEditedCell();
                }
            });
            $(g.cEditTextarea).keydown(function (e) {
                if (!g.isEditCellTextEditable) {
                    // prevent text editing
                    e.preventDefault();
                }
            });
            $('html').click(function (e) {
                // hide edit cell if the click is not fromDat edit area
                if ($(e.target).parents().index($(g.cEdit)) == -1 &&
                    !$(e.target).parents('.ui-datepicker-header').length &&
                    !$('.browse_foreign_modal.ui-dialog:visible').length &&
                    !$(e.target).hasClass('error')
                ) {
                    g.hideEditCell();
                }
            }).keydown(function (e) {
                if (e.which == 27 && g.isCellEditActive) {

                    // cancel on pressing "Esc"
                    g.hideEditCell(true);
                }
            });
            $(g.o).find('div.save_edited').click(function () {
                g.hideEditCell();
                g.postEditedCell();
            });
            $(window).bind('beforeunload', function () {
                if (g.isCellEdited) {
                    return g.saveCellWarning;
                }
            });

            // attach to global div
            $(g.gDiv).append(g.cEditStd);
            $(g.gDiv).append(g.cEditTextarea);

            // add hint for grid editing feature when hovering "Edit" link in each table row
            if (PMA_messages.strGridEditFeatureHint !== undefined) {
                PMA_tooltip(
                    $(g.t).find('.edit_row_anchor a'),
                    'a',
                    PMA_messages.strGridEditFeatureHint
                );
            }
        }
    };

    /******************
     * Initialize grid
     ******************/

    // wrap all truncated data cells with span indicating the original length
    // todo update the original length after a grid edit
    $(t).find('td.data.truncated:not(:has(span))')
        .wrapInner(function() {
            return '<span title="' + PMA_messages.strOriginalLength + ' ' +
                $(this).data('originallength') + '"></span>';
        });

    // wrap remaining cells, except actions cell, with span
    $(t).find('th, td:not(:has(span))')
        .wrapInner('<span />');

    // create grid elements
    g.gDiv = document.createElement('div');     // create global div

    // initialize the table variable
    g.t = t;

    // enclosing .sqlqueryresults div
    g.o = $(t).parents('.sqlqueryresults');

    // get data columns in the first row of the table
    var $firstRowCols = $(t).find('tr:first th.draggable');

    // initialize visible headers count
    g.visibleHeadersCount = $firstRowCols.filter(':visible').length;

    // assign first column (actions) span
    if (! $(t).find('tr:first th:first').hasClass('draggable')) {  // action header exist
        g.actionSpan = $(t).find('tr:first th:first').prop('colspan');
    } else {
        g.actionSpan = 0;
    }

    // assign table create time
    // table_create_time will only available if we are in "Browse" tab
    g.tableCreateTime = $(g.o).find('.table_create_time').val();

    // assign the hints
    g.sortHint = PMA_messages.strSortHint;
    g.strMultiSortHint = PMA_messages.strMultiSortHint;
    g.markHint = PMA_messages.strColMarkHint;
    g.copyHint = PMA_messages.strColNameCopyHint;

    // assign common hidden inputs
    var $common_hidden_inputs = $(g.o).find('div.common_hidden_inputs');
    g.token = $common_hidden_inputs.find('input[name=token]').val();
    g.server = $common_hidden_inputs.find('input[name=server]').val();
    g.db = $common_hidden_inputs.find('input[name=db]').val();
    g.table = $common_hidden_inputs.find('input[name=table]').val();

    // add table class
    $(t).addClass('pma_table');

    // add relative position to global div so that resize handlers are correctly positioned
    $(g.gDiv).css('position', 'relative');

    // link the global div
    $(t).before(g.gDiv);
    $(g.gDiv).append(t);

    // FEATURES
    enableResize    = enableResize === undefined ? true : enableResize;
    enableReorder   = enableReorder === undefined ? true : enableReorder;
    enableVisib     = enableVisib === undefined ? true : enableVisib;
    enableGridEdit  = enableGridEdit === undefined ? true : enableGridEdit;
    if (enableResize) {
        g.initColResize();
    }
    if (enableReorder &&
        $(g.o).find('table.navigation').length > 0)    // disable reordering for result from EXPLAIN or SHOW syntax, which do not have a table navigation panel
    {
        g.initColReorder();
    }
    if (enableVisib) {
        g.initColVisib();
    }
    if (enableGridEdit &&
        $(t).is('.ajax'))   // make sure we have the ajax class
    {
        g.initGridEdit();
    }

    // create tooltip for each <th> with draggable class
    PMA_tooltip(
            $(t).find("th.draggable"),
            'th',
            g.updateHint()
    );

    // register events for hint tooltip (anchors inside draggable th)
    $(t).find('th.draggable a')
        .mouseenter(function () {
            g.showSortHint = true;
            g.showMultiSortHint = true;
            $(t).find("th.draggable").tooltip("option", {
                content: g.updateHint()
            });
        })
        .mouseleave(function () {
            g.showSortHint = false;
            g.showMultiSortHint = false;
            $(t).find("th.draggable").tooltip("option", {
                content: g.updateHint()
            });
        });

    // register events for dragging-related feature
    if (enableResize || enableReorder) {
        $(document).mousemove(function (e) {
            g.dragMove(e);
        });
        $(document).mouseup(function (e) {
            $(g.o).removeClass("turnOffSelect");
            g.dragEnd(e);
        });
    }

    // some adjustment
    $(t).removeClass('data');
    $(g.gDiv).addClass('data');
}

/**
 * jQuery plugin to cancel selection in HTML code.
 */
(function ($) {
    $.fn.noSelect = function (p) { //no select plugin by Paulo P.Marinas
        var prevent = (p === null) ? true : p;
        var is_msie = navigator.userAgent.indexOf('MSIE') > -1 || !!window.navigator.userAgent.match(/Trident.*rv\:11\./);
        var is_firefox = navigator.userAgent.indexOf('Firefox') > -1;
        var is_safari = navigator.userAgent.indexOf("Safari") > -1;
        var is_opera = navigator.userAgent.indexOf("Presto") > -1;
        if (prevent) {
            return this.each(function () {
                if (is_msie || is_safari) {
                    $(this).bind('selectstart', function () {
                        return false;
                    });
                } else if (is_firefox) {
                    $(this).css('MozUserSelect', 'none');
                    $('body').trigger('focus');
                } else if (is_opera) {
                    $(this).bind('mousedown', function () {
                        return false;
                    });
                } else {
                    $(this).attr('unselectable', 'on');
                }
            });
        } else {
            return this.each(function () {
                if (is_msie || is_safari) {
                    $(this).unbind('selectstart');
                } else if (is_firefox) {
                    $(this).css('MozUserSelect', 'inherit');
                } else if (is_opera) {
                    $(this).unbind('mousedown');
                } else {
                    $(this).removeAttr('unselectable');
                }
            });
        }
    }; //end noSelect
})(jQuery);
