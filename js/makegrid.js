(function ($) {
    $.grid = function(t) {
        // prepare the grid
        var g = {
            // constant
            minColWidth: 15,
            
            // variables, assigned with default value, changed later
            actionSpan: 5,
            colOrder: new Array(),      // array of column order
            colVisib: new Array(),      // array of column visibility
            tableCreateTime: null,      // table creation time, only available in "Browse tab"
            qtip: null,                 // qtip API
            reorderHint: '',            // string, hint for column reordering
            sortHint: '',               // string, hint for column sorting
            markHint: '',               // string, hint for column marking
            colVisibHint: '',           // string, hint for column visibility drop-down
            showReorderHint: false,
            showSortHint: false,
            showMarkHint: false,
            showColVisibHint: false,
            showAllColText: '',         // string, text for "show all" button under column visibility list
            visibleHeadersCount: 0,     // number of visible data headers
            isCellEditActive: false,    // true if current focus is in edit cell
            isEditCellTextEditable: false,  // true if current edit cell is editable in the text input box (not textarea)
            currentEditCell: null,      // reference to <td> that currently being edited
            inEditMode: false,          // true if grid is in edit mode
            cellEditHint: '',           // hint shown when doing grid edit
            gotoLinkText: 'Go to link', // "Go to link" text
            wasEditedCellNull: false,   // true if last value of the edited cell was NULL
            maxTruncatedLen: 0,         // number of characters that can be displayed in a cell
            saveCellsAtOnce: false,     // $cfg[saveCellsAtOnce]
            isCellEdited: false,        // true if at least one cell has been edited
            saveCellWarning: '',        // string, warning text when user want to leave a page with unsaved edited data
            lastXHR : null,             // last XHR object used in AJAX request
            
            // functions
            dragStartRsz: function(e, obj) {    // start column resize
                var n = $(this.cRsz).find('div').index(obj);
                this.colRsz = {
                    x0: e.pageX,
                    n: n,
                    obj: obj,
                    objLeft: $(obj).position().left,
                    objWidth: $(this.t).find('th.draggable:visible:eq(' + n + ') span').outerWidth()
                };
                $('body').css('cursor', 'col-resize');
                $('body').noSelect();
                if (g.isCellEditActive) {
                    g.hideEditCell();
                }
            },
            
            dragStartMove: function(e, obj) {   // start column move
                // prepare the cCpy and cPointer from the dragged column
                $(this.cCpy).text($(obj).text());
                var objPos = $(obj).position();
                $(this.cCpy).css({
                    top: objPos.top + 20,
                    left: objPos.left,
                    height: $(obj).height(),
                    width: $(obj).width()
                });
                $(this.cPointer).css({
                    top: objPos.top
                });
                
                // get the column index, zero-based
                var n = this.getHeaderIdx(obj);
                
                this.colMov = {
                    x0: e.pageX,
                    y0: e.pageY,
                    n: n,
                    newn: n,
                    obj: obj,
                    objTop: objPos.top,
                    objLeft: objPos.left
                };
                this.qtip.hide();
                $('body').css('cursor', 'move');
                $('body').noSelect();
                if (g.isCellEditActive) {
                    g.hideEditCell();
                }
            },
            
            dragMove: function(e) {
                if (this.colRsz) {
                    var dx = e.pageX - this.colRsz.x0;
                    if (this.colRsz.objWidth + dx > this.minColWidth) {
                        $(this.colRsz.obj).css('left', this.colRsz.objLeft + dx + 'px');
                    }
                } else if (this.colMov) {
                    // dragged column animation
                    var dx = e.pageX - this.colMov.x0;
                    $(this.cCpy)
                        .css('left', this.colMov.objLeft + dx)
                        .show();
                    
                    // pointer animation
                    var hoveredCol = this.getHoveredCol(e);
                    if (hoveredCol) {
                        var newn = this.getHeaderIdx(hoveredCol);
                        this.colMov.newn = newn;
                        if (newn != this.colMov.n) {
                            // show the column pointer in the right place
                            var colPos = $(hoveredCol).position();
                            var newleft = newn < this.colMov.n ?
                                          colPos.left :
                                          colPos.left + $(hoveredCol).outerWidth();
                            $(this.cPointer)
                                .css({
                                    left: newleft,
                                    visibility: 'visible'
                                });
                        } else {
                            // no movement to other column, hide the column pointer
                            $(this.cPointer).css('visibility', 'hidden');
                        }
                    }
                }
            },
            
            dragEnd: function(e) {
                if (this.colRsz) {
                    var dx = e.pageX - this.colRsz.x0;
                    var nw = this.colRsz.objWidth + dx;
                    if (nw < this.minColWidth) {
                        nw = this.minColWidth;
                    }
                    var n = this.colRsz.n;
                    // do the resizing
                    this.resize(n, nw);
                    
                    $('body').css('cursor', 'default');
                    this.reposRsz();
                    this.reposDrop();
                    this.colRsz = false;
                } else if (this.colMov) {
                    // shift columns
                    if (this.colMov.newn != this.colMov.n) {
                        this.shiftCol(this.colMov.n, this.colMov.newn);
                        // assign new position
                        var objPos = $(this.colMov.obj).position();
                        this.colMov.objTop = objPos.top;
                        this.colMov.objLeft = objPos.left;
                        this.colMov.n = this.colMov.newn;
                        // send request to server to remember the column order
                        if (this.tableCreateTime) {
                            this.sendColPrefs();
                        }
                        this.refreshRestoreButton();
                    }
                    
                    // animate new column position
                    $(this.cCpy).stop(true, true)
                        .animate({
                            top: g.colMov.objTop,
                            left: g.colMov.objLeft
                        }, 'fast')
                        .fadeOut();
                    $(this.cPointer).css('visibility', 'hidden');

                    this.colMov = false;
                }
                $('body').css('cursor', 'default');
                $('body').noSelect(false);
            },
            
            /**
             * Resize column n to new width "nw"
             */
            resize: function(n, nw) {
                $(this.t).find('tr').each(function() {
                    $(this).find('th.draggable:visible:eq(' + n + ') span,' +
                                 'td:visible:eq(' + (g.actionSpan + n) + ') span')
                           .css('width', nw);
                });
            },
            
            /**
             * Reposition column resize bars.
             */
            reposRsz: function() {
                $(this.cRsz).find('div').hide();
                var $firstRowCols = $(this.t).find('tr:first th.draggable:visible');
                for (var n = 0; n < $firstRowCols.length; n++) {
                    $this = $($firstRowCols[n]);
                    $cb = $(g.cRsz).find('div:eq(' + n + ')');   // column border
                    $cb.css('left', $this.position().left + $this.outerWidth(true))
                       .show();
                }
                $(this.cRsz).css('height', $(this.t).height());
            },
            
            /**
             * Shift column from index oldn to newn.
             */
            shiftCol: function(oldn, newn) {
                $(this.t).find('tr').each(function() {
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
                this.reposRsz();
                    
                // adjust the column visibility list
                if (newn < oldn) {
                    $(g.cList).find('.lDiv div:eq(' + newn + ')')
                              .before($(g.cList).find('.lDiv div:eq(' + oldn + ')'));
                } else {
                    $(g.cList).find('.lDiv div:eq(' + newn + ')')
                              .after($(g.cList).find('.lDiv div:eq(' + oldn + ')'));
                }
                // adjust the colOrder
                var tmp = this.colOrder[oldn];
                this.colOrder.splice(oldn, 1);
                this.colOrder.splice(newn, 0, tmp);
                // adjust the colVisib
                var tmp = this.colVisib[oldn];
                this.colVisib.splice(oldn, 1);
                this.colVisib.splice(newn, 0, tmp);
            },
            
            /**
             * Find currently hovered table column's header (excluding actions column).
             * @return the hovered column's th object or undefined if no hovered column found.
             */
            getHoveredCol: function(e) {
                var hoveredCol;
                $headers = $(this.t).find('th.draggable:visible');
                $headers.each(function() {
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
             */
            getHeaderIdx: function(obj) {
                return $(obj).parents('tr').find('th.draggable').index(obj);
            },
            
            /**
             * Reposition the table back to normal order.
             */
            restoreColOrder: function() {
                // use insertion sort, since we already have shiftCol function
                for (var i = 1; i < this.colOrder.length; i++) {
                    var x = this.colOrder[i];
                    var j = i - 1;
                    while (j >= 0 && x < this.colOrder[j]) {
                        j--;
                    }
                    if (j != i - 1) {
                        this.shiftCol(i, j + 1);
                    }
                }
                if (this.tableCreateTime) {
                    // send request to server to remember the column order
                    this.sendColPrefs();
                }
                this.refreshRestoreButton();
            },
            
            /**
             * Send column preferences (column order and visibility) to the server.
             */
            sendColPrefs: function() {
                $.post('sql.php', {
                    ajax_request: true,
                    db: window.parent.db,
                    table: window.parent.table,
                    token: window.parent.token,
                    server: window.parent.server,
                    set_col_prefs: true,
                    col_order: this.colOrder.toString(),
                    col_visib: this.colVisib.toString(),
                    table_create_time: this.tableCreateTime
                });
            },
            
            /**
             * Refresh restore button state.
             * Make restore button disabled if the table is similar with initial state.
             */
            refreshRestoreButton: function() {
                // check if table state is as initial state
                var isInitial = true;
                for (var i = 0; i < this.colOrder.length; i++) {
                    if (this.colOrder[i] != i) {
                        isInitial = false;
                        break;
                    }
                }
                // check if only one visible column left
                var isOneColumn = this.visibleHeadersCount == 1;
                // enable or disable restore button
                if (isInitial || isOneColumn) {
                    $('.restore_column').hide();
                } else {
                    $('.restore_column').show();
                }
            },
            
            /**
             * Update current hint using the boolean values (showReorderHint, showSortHint, etc.).
             * It will hide the hint if all the boolean values is false.
             */
            updateHint: function(e) {
                if (!this.colRsz && !this.colMov) {     // if not resizing or dragging
                    var text = '';
                    if (this.showReorderHint && this.reorderHint) {
                        text += this.reorderHint;
                    }
                    if (this.showSortHint && this.sortHint) {
                        text += text.length > 0 ? '<br />' : '';
                        text += this.sortHint;
                    }
                    if (this.showMarkHint && this.markHint &&
                        !this.showSortHint      // we do not show mark hint, when sort hint is shown
                    ) {
                        text += text.length > 0 ? '<br />' : '';
                        text += this.markHint;
                    }
                    if (this.showColVisibHint && this.colVisibHint) {
                        text += text.length > 0 ? '<br />' : '';
                        text += this.colVisibHint;
                    }
                    
                    // hide the hint if no text
                    this.qtip.disable(!text && e.type == 'mouseenter');
                    
                    this.qtip.updateContent(text, false);
                } else {
                    this.qtip.disable(true);
                }
            },
            
            /**
             * Toggle column's visibility.
             * After calling this function and it returns true, afterToggleCol() must be called.
             *
             * @return boolean True if the column is toggled successfully.
             */
            toggleCol: function(n) {
                if (this.colVisib[n]) {
                    // can hide if more than one column is visible
                    if (this.visibleHeadersCount > 1) {
                        $(this.t).find('tr').each(function() {
                            $(this).find('th.draggable:eq(' + n + '),' +
                                         'td:eq(' + (g.actionSpan + n) + ')')
                                   .hide();
                        });
                        this.colVisib[n] = 0;
                        $(this.cList).find('.lDiv div:eq(' + n + ') input').removeAttr('checked');
                    } else {
                        // cannot hide, force the checkbox to stay checked
                        $(this.cList).find('.lDiv div:eq(' + n + ') input').attr('checked', 'checked');
                        return false;
                    }
                } else {    // column n is not visible
                    $(this.t).find('tr').each(function() {
                        $(this).find('th.draggable:eq(' + n + '),' +
                                     'td:eq(' + (g.actionSpan + n) + ')')
                               .show();
                    });
                    this.colVisib[n] = 1;
                    $(this.cList).find('.lDiv div:eq(' + n + ') input').attr('checked', 'checked');
                }
                return true;
            },
            
            /**
             * This must be called after calling toggleCol() and the return value is true.
             *
             * This function is separated from toggleCol because, sometimes, we want to toggle
             * some columns together at one time and do one adjustment after it, e.g. in showAllColumns().
             */
            afterToggleCol: function() {
                // some adjustments after hiding column
                this.reposRsz();
                this.reposDrop();
                this.sendColPrefs();
                
                // check visible first row headers count
                this.visibleHeadersCount = $(this.t).find('tr:first th.draggable:visible').length;
                this.refreshRestoreButton();
            },
            
            /**
             * Show columns' visibility list.
             */
            showColList: function(obj) {
                // only show when not resizing or reordering
                if (!this.colRsz && !this.colMov) {
                    var pos = $(obj).position();
                    // check if the list position is too right
                    if (pos.left + $(this.cList).outerWidth(true) > $(document).width()) {
                        pos.left = $(document).width() - $(this.cList).outerWidth(true);
                    }
                    $(this.cList).css({
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
            hideColList: function() {
                $(this.cList).hide();
                $(g.cDrop).find('.coldrop-hover').removeClass('coldrop-hover');
            },
            
            /**
             * Reposition the column visibility drop-down arrow.
             */
            reposDrop: function() {
                $th = $(t).find('th:not(.draggable)');
                for (var i = 0; i < $th.length; i++) {
                    var $cd = $(this.cDrop).find('div:eq(' + i + ')');   // column drop-down arrow
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
            showAllColumns: function() {
                for (var i = 0; i < this.colVisib.length; i++) {
                    if (!this.colVisib[i]) {
                        this.toggleCol(i);
                    }
                }
                this.afterToggleCol();
            },
            
            /**
             * Show edit cell, if it can be shown or it is forced.
             */
            showEditCell: function(cell, force) {
                if ($(cell).is('.inline_edit') &&
                    !g.colRsz && !g.colMov)
                {
                    if (!g.isCellEditActive || force) {
                        $cell = $(cell);
                        // remove all edit area and hide it
                        $(g.cEdit).find('.edit_area').empty().hide();
                        // reposition the cEdit element
                        $(g.cEdit).css({
                                top: $cell.position().top,
                                left: $cell.position().left,
                            })
                            .show()
                            .find('input')
                            .css({
                                width: $cell.outerWidth(),
                                height: $cell.outerHeight()
                            });
                        // fill the cell edit with text from <td>, if it is not null
                        var value = $cell.is(':not(.null)') ? PMA_getCellValue(cell) : '';
                        $(g.cEdit).find('input')
                            .val(value);
                        
                        g.isCellEditActive = false;
                        g.currentEditCell = cell;
                        $(g.cEdit).find('input[type=text]').focus();
                        $(g.cEdit).find('*').attr('disabled', false);
                    }
                } else {
                    g.hideEditCell();
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
             */
            hideEditCell: function(force, data, field) {
                if (g.isCellEditActive && !force) {
                    // cell is being edited, post the edited data
                    g.isCellEditActive = false;
                    g.saveOrPostEditedCell();
                    return;
                }
                
                // cancel any previous request
                if (g.lastXHR != null) {
                    g.lastXHR.abort();
                    g.lastXHR = null;
                }
                
                // hide the cell editing area
                $(g.cEdit).hide();
                $(g.cEdit).find('input[type=text]').blur();
                g.isCellEditActive = false;
                
                if (data) {
                    if (data === true) {
                        // replace current edited field with the new value
                        var $this_field = $(g.currentEditCell);
                        var new_html = $this_field.data('value');
                        var is_null = $this_field.data('value') == null;
                        if (is_null) {
                            $this_field.find('span').html('NULL');
                            $this_field.addClass('null');
                        } else {
                            $this_field.removeClass('null');
                            if ($this_field.is('.truncated')) {
                                if (new_html.length > g.maxTruncatedLen) {
                                    new_html = new_html.substring(0, g.maxTruncatedLen) + '...';
                                }
                            }
                            // replace '\n' with <br>
                            new_html = new_html.replace(/\n/g, '<br />');
                            $this_field.find('span').html(new_html);
                        }
                    } else {
                        // update edited fields with new value from "data"
                        if (data.transformations != undefined) {
                            $.each(data.transformations, function(cell_index, value) {
                                var $this_field = $(g.t).find('.to_be_saved:eq(' + cell_index + ')');
                                $this_field.find('span').html(value);
                            });
                        }
                        if (data.relations != undefined) {
                            $.each(data.relations, function(cell_index, value) {
                                var $this_field = $(g.t).find('.to_be_saved:eq(' + cell_index + ')');
                                $this_field.find('span').html(value);
                            });
                        }
                    }
                    
                    // refresh the grid
                    this.reposRsz();
                    this.reposDrop();
                }
            },
            
            /**
             * Show drop-down edit area when edit cell is clicked.
             */
            showEditArea: function() {
                if (!this.isCellEditActive) {   // make sure we don't have focus on other edit cell
                    g.isCellEditActive = true;
                    g.isEditCellTextEditable = false;
                    var $td = $(g.currentEditCell);
                    var $editArea = $(this.cEdit).find('.edit_area');
                    var where_clause = $td.parent('tr').find('.where_clause').val();
                    /**
                     * @var field_name  String containing the name of this field.
                     * @see getFieldName()
                     */
                    var field_name = getFieldName($td);
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
                    
                    // add goto link, if this cell contains a link
                    if ($td.find('a').length > 0) {
                        var gotoLink = document.createElement('div');
                        gotoLink.className = 'goto_link';
                        $(gotoLink).append(g.gotoLinkText + ': ')
                            .append($td.find('a').clone());
                        $editArea.append(gotoLink);
                    }
                    
                    g.wasEditedCellNull = false;
                    if ($td.is(':not(.not_null)')) {
                        // append a null checkbox
                        $editArea.append('<div class="null_div">Null :<input type="checkbox"></div>');
                        var $checkbox = $editArea.find('.null_div input');
                        // check if current <td> is NULL
                        if ($td.is('.null')) {
                            $checkbox.attr('checked', true);
                            g.wasEditedCellNull = true;
                        }
                        
                        // if the select/editor is changed un-check the 'checkbox_null_<field_name>_<row_index>'.
                        if ($td.is('.enum, .set')) {
                            $editArea.find('select').live('change', function(e) {
                                $checkbox.attr('checked', false);
                            })
                        } else if ($td.is('.relation')) {
                            $editArea.find('select').live('change', function(e) {
                                $checkbox.attr('checked', false);
                            })
                            $editArea.find('.browse_foreign').live('click', function(e) {
                                $checkbox.attr('checked', false);
                            })
                        } else {
                            $(g.cEdit).find('input[type=text]').live('change', function(e) {
                                $checkbox.attr('checked', false);
                            })
                            $editArea.find('textarea').live('keydown', function(e) {
                                $checkbox.attr('checked', false);
                            })
                        }
                        
                        // if 'checkbox_null_<field_name>_<row_index>' is clicked empty the corresponding select/editor.
                        $checkbox.click(function(e) {
                            if ($td.is('.enum')) {
                                $editArea.find('select').attr('value', '');
                            } else if ($td.is('.set')) {
                                $editArea.find('select').find('option').each(function() {
                                    var $option = $(this);
                                    $option.attr('selected', false);
                                })
                            } else if ($td.is('.relation')) {
                                // if the dropdown is there to select the foreign value
                                if ($editArea.find('select').length > 0) {
                                    $editArea.find('select').attr('value', '');
                                }
                            } else {
                                $editArea.find('textarea').val('');
                            }
                            $(g.cEdit).find('input[type=text]').val('');
                        })
                    }
                    
                    if($td.is('.relation')) {
                        /** @lends jQuery */
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
                                'server' : window.parent.server,
                                'db' : window.parent.db,
                                'table' : window.parent.table,
                                'column' : field_name,
                                'token' : window.parent.token,
                                'curr_value' : relation_curr_value,
                                'relation_key_or_display_column' : relation_key_or_display_column
                        }

                        g.lastXHR = $.post('sql.php', post_params, function(data) {
                            $editArea.removeClass('edit_area_loading');
                            // save original_data
                            var value = $(data.dropdown).val();
                            $td.data('original_data', value);
                            // update the text input field, in case where the "Relational display column" is checked
                            $(g.cEdit).find('input[type=text]').val(value);
                            
                            $editArea.append(data.dropdown);
                            $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                        }) // end $.post()
                        
                        $editArea.find('select').live('change', function(e) {
                            $(g.cEdit).find('input[type=text]').val($(this).val());
                        })
                    }
                    else if($td.is('.enum')) {
                        /** @lends jQuery */
                        //handle enum fields
                        $editArea.addClass('edit_area_loading');

                        /**
                         * @var post_params Object containing parameters for the POST request
                         */
                        var post_params = {
                                'ajax_request' : true,
                                'get_enum_values' : true,
                                'server' : window.parent.server,
                                'db' : window.parent.db,
                                'table' : window.parent.table,
                                'column' : field_name,
                                'token' : window.parent.token,
                                'curr_value' : curr_value
                        }
                        g.lastXHR = $.post('sql.php', post_params, function(data) {
                            $editArea.removeClass('edit_area_loading');
                            $editArea.append(data.dropdown);
                            $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                        }) // end $.post()
                        
                        $editArea.find('select').live('change', function(e) {
                            $(g.cEdit).find('input[type=text]').val($(this).val());
                        })
                    }
                    else if($td.is('.set')) {
                        /** @lends jQuery */
                        //handle set fields
                        $editArea.addClass('edit_area_loading');

                        /**
                         * @var post_params Object containing parameters for the POST request
                         */
                        var post_params = {
                                'ajax_request' : true,
                                'get_set_values' : true,
                                'server' : window.parent.server,
                                'db' : window.parent.db,
                                'table' : window.parent.table,
                                'column' : field_name,
                                'token' : window.parent.token,
                                'curr_value' : curr_value
                        }

                        g.lastXHR = $.post('sql.php', post_params, function(data) {
                            $editArea.removeClass('edit_area_loading');
                            $editArea.append(data.select);
                            $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                        }) // end $.post()
                        
                        $editArea.find('select').live('change', function(e) {
                            $(g.cEdit).find('input[type=text]').val($(this).val());
                        })
                    }
                    else if($td.is('.truncated, .transformed')) {
                        if ($td.is('.to_be_saved')) {   // cell has been edited
                            var value = $td.data('value');
                            $(g.cEdit).find('input[type=text]').val(value);
                            $editArea.append('<textarea>'+value+'</textarea>');
                            $editArea.find('textarea').live('keyup', function(e) {
                                $(g.cEdit).find('input[type=text]').val($(this).val());
                            });
                            $(g.cEdit).find('input[type=text]').live('keyup', function(e) {
                                $editArea.find('textarea').val($(this).val());
                            });
                            $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                        } else {
                            /** @lends jQuery */
                            //handle truncated/transformed values values
                            $editArea.addClass('edit_area_loading');

                            // initialize the original data
                            $td.data('original_data', null);

                            /**
                             * @var sql_query   String containing the SQL query used to retrieve value of truncated/transformed data
                             */
                            var sql_query = 'SELECT `' + field_name + '` FROM `' + window.parent.table + '` WHERE ' + PMA_urldecode(where_clause);
                            
                            // Make the Ajax call and get the data, wrap it and insert it
                            g.lastXHR = $.post('sql.php', {
                                'token' : window.parent.token,
                                'server' : window.parent.server,
                                'db' : window.parent.db,
                                'ajax_request' : true,
                                'sql_query' : sql_query,
                                'inline_edit' : true
                            }, function(data) {
                                $editArea.removeClass('edit_area_loading');
                                if(data.success == true) {
                                    if ($td.is('.truncated')) {
                                        // get the truncated data length
                                        g.maxTruncatedLen = $(g.currentEditCell).text().length - 3;
                                    }
                                    
                                    $td.data('original_data', data.value);
                                    $(g.cEdit).find('input[type=text]').val(data.value);
                                    $editArea.append('<textarea>'+data.value+'</textarea>');
                                    $editArea.find('textarea').live('keyup', function(e) {
                                        $(g.cEdit).find('input[type=text]').val($(this).val());
                                    });
                                    $(g.cEdit).find('input[type=text]').live('keyup', function(e) {
                                        $editArea.find('textarea').val($(this).val());
                                    });
                                    $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                                }
                                else {
                                    PMA_ajaxShowMessage(data.error);
                                }
                            }) // end $.post()
                        }
                        g.isEditCellTextEditable = true;
                    } else {
                        $editArea.append('<textarea>' + PMA_getCellValue(g.currentEditCell) + '</textarea>');
                        $editArea.find('textarea').live('keyup', function(e) {
                            $(g.cEdit).find('input[type=text]').val($(this).val());
                        });
                        $(g.cEdit).find('input[type=text]').live('keyup', function(e) {
                            $editArea.find('textarea').val($(this).val());
                        });
                        $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                        g.isEditCellTextEditable = true;
                    }
                    
                    $editArea.show();
                }
            },
            
            /**
             * Post the content of edited cell.
             */
            postEditedCell: function() {
                /**
                 * @var relation_fields Array containing the name/value pairs of relational fields
                 */
                var relation_fields = {};
                /**
                 * @var relational_display string 'K' if relational key, 'D' if relational display column
                 */
                var relational_display = $("#relational_display_K").attr('checked') ? 'K' : 'D';
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
                var full_where_clause = Array();
                /**
                 * @var nonunique   Boolean, whether the rows in this table is unique or not
                 */
                var nonunique = $('.inline_edit_anchor').is('.nonunique') ? 0 : 1;
                /**
                 * multi edit variables
                 */
                var me_fields_name = Array();
                var me_fields = Array();
                var me_fields_null = Array();
                
                // loop each edited row
                $('.to_be_saved').parents('tr').each(function() {
                    var where_clause = $(this).find('.where_clause').val();
                    full_where_clause.push(unescape(where_clause.replace(/[+]/g, ' ')));
                    var new_clause = where_clause;
                    
                    /**
                     * multi edit variables, for current row
                     * @TODO array indices are still not correct, they should be md5 of field's name
                     */
                    var fields_name = Array();
                    var fields = Array();
                    var fields_null = Array();

                    // loop each edited cell in a row
                    $(this).find('.to_be_saved').each(function() {
                        /**
                         * @var $this_field    Object referring to the td that is being edited
                         */
                        var $this_field = $(this);
                        
                        var $test_element = ''; // to test the presence of a element

                        /**
                         * @var field_name  String containing the name of this field.
                         * @see getFieldName()
                         */
                        var field_name = getFieldName($this_field);

                        /**
                         * @var this_field_params   Array temporary storage for the name/value of current field
                         */
                        var this_field_params = {};

                        if($this_field.is('.transformed')) {
                            transformation_fields =  true;
                        }
                        /**
                         * @var is_null String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
                         */
                        var is_null = $this_field.data('value') === null;
                        
                        fields_name.push(field_name);
                        
                        if (is_null) {
                            fields_null.push('on');
                            fields.push('');
                        } else {
                            fields_null.push('');
                            fields.push($this_field.data('value'));
                            this_field_params[field_name] = $this_field.data('value');
                            
                            var cell_index = $this_field.index('.to_be_saved');
                            if($this_field.is(":not(.relation, .enum, .set, .bit)")) {
                                if($this_field.is('.transformed')) {
                                    transform_fields[cell_index] = {};
                                    $.extend(transform_fields[cell_index], this_field_params);
                                }
                            } else if($this_field.is('.relation')) {
                                relation_fields[cell_index] = {};
                                $.extend(relation_fields[cell_index], this_field_params);
                            }
                            if (where_clause.indexOf(PMA_urlencode(field_name)) > -1) {
                                var fields_str = PMA_urlencode('`' + window.parent.table + '`.' + '`' + field_name + '` = ');
                                fields_str = fields_str.replace(/[+]/g, '[+]');    // replace '+' sign with '[+]' (regex)
                                var old_sub_clause_regex = new RegExp(fields_str + '[^+]*');
                                var new_sub_clause = PMA_urlencode('`' + window.parent.table + '`.' + '`' + field_name + "` = '" + this_field_params[field_name].replace(/'/g,"''") + "'");
                                new_clause = new_clause.replace(old_sub_clause_regex, new_sub_clause);
                            }
                        }
                        
                        // save new_clause
                        $this_field.parent('tr').data('new_clause', new_clause);
                    }); // end of loop for every edited cells in a row
                    
                    me_fields_name.push(fields_name);
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
                                'token' : window.parent.token,
                                'server' : window.parent.server,
                                'db' : window.parent.db,
                                'table' : window.parent.table,
                                'clause_is_unique' : nonunique,
                                'where_clause' : full_where_clause,
                                'fields[multi_edit]' : me_fields,
                                'fields_name[multi_edit]' : me_fields_name,
                                'fields_null[multi_edit]' : me_fields_null,
                                'rel_fields_list' : rel_fields_list,
                                'do_transformations' : transformation_fields,
                                'transform_fields_list' : transform_fields_list,
                                'relational_display' : relational_display,
                                'goto' : 'sql.php',
                                'submit_type' : 'save'
                              };
                
                if (!g.saveCellsAtOnce) {
                    $(g.cEdit).find('*').attr('disabled', true);
                    var $editArea = $(g.cEdit).find('.edit_area');
                    $editArea.addClass('edit_area_posting');
                } else {
                    $('.save_edited').addClass('saving_edited_data')
                        .attr('disabled', true);
                }
                
                $.ajax({
                    type: 'POST',
                    url: 'tbl_replace.php',
                    data: post_params,
                    success:
                        function(data) {
                            if (!g.saveCellsAtOnce) {
                                $editArea.removeClass('edit_area_posting');
                            } else {
                                $('.save_edited').removeClass('saving_edited_data')
                                    .attr('disabled', false);
                            }
                            if(data.success == true) {
                                PMA_ajaxShowMessage(data.message);
                                $('.to_be_saved').each(function() {
                                    var new_clause = $(this).parent('tr').data('new_clause');
                                    if (new_clause != '') {
                                        var $where_clause = $(this).parent('tr').find('.where_clause');
                                        var old_clause = $where_clause.attr('value');
                                        $where_clause.attr('value', new_clause);
                                        // update Edit, Copy, and Delete links also
                                        $(this).parent('tr').find('a').each(function() {
                                            $(this).attr('href', $(this).attr('href').replace(old_clause, new_clause));
                                        });
                                    }
                                });
                                // remove possible previous feedback message
                                $('#result_query').remove();
                                if (typeof data.sql_query != 'undefined') {
                                    // display feedback
                                    $('#sqlqueryresults').prepend(data.sql_query);
                                }
                                g.hideEditCell(true, data);
                                
                                // remove the "Save edited cells" button
                                $('.save_edited').hide();
                                // update saved fields
                                $(g.t).find('.to_be_saved')
                                    .removeClass('to_be_saved')
                                    .data('value', null)
                                    .data('original_data', null);
                                
                                g.isCellEdited = false;
                            } else {
                                PMA_ajaxShowMessage(data.error);
                            }
                        }
                }) // end $.ajax()
            },
            
            // save edited cell, so it can be posted later
            saveEditedCell: function() {
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
                var field_name = getFieldName($this_field);

                /**
                 * @var this_field_params   Array temporary storage for the name/value of current field
                 */
                var this_field_params = {};

                /**
                 * @var is_null String capturing whether 'checkbox_null_<field_name>_<row_index>' is checked.
                 */
                var is_null = $(g.cEdit).find('input:checkbox').is(':checked');
                var value;

                if (is_null) {
                    if (!g.wasEditedCellNull) {
                        this_field_params[field_name] = null;
                        need_to_post = true;
                    }
                } else {
                    if($this_field.is(":not(.relation, .enum, .set, .bit)")) {
                        this_field_params[field_name] = $(g.cEdit).find('textarea').val();
                    } else if ($this_field.is('.bit')) {
                        this_field_params[field_name] = '0b' + $(g.cEdit).find('textarea').val();
                    } else if ($this_field.is('.set')) {
                        $test_element = $(g.cEdit).find('select');
                        this_field_params[field_name] = $test_element.map(function(){
                            return $(this).val();
                        }).get().join(",");
                    } else {
                        // results from a drop-down
                        $test_element = $(g.cEdit).find('select');
                        if ($test_element.length != 0) {
                            this_field_params[field_name] = $test_element.val();
                        }

                        // results from Browse foreign value
                        $test_element = $(g.cEdit).find('span.curr_value');
                        if ($test_element.length != 0) {
                            this_field_params[field_name] = $test_element.text();
                        }
                    }
                    if (g.wasEditedCellNull || this_field_params[field_name] != PMA_getCellValue(g.currentEditCell)) {
                        need_to_post = true;
                    }
                }
                
                if (need_to_post) {
                    $(g.currentEditCell).addClass('to_be_saved')
                        .data('value', this_field_params[field_name]);
                    if (g.saveCellsAtOnce) {
                        $('.save_edited').show();
                    }
                    g.isCellEdited = true;
                }
                
                return need_to_post;
            },
            
            // save or post edited cell, depending on the configuration
            saveOrPostEditedCell: function() {
                var saved = g.saveEditedCell();
                if (!g.saveCellsAtOnce) {
                    if (saved) {
                        g.postEditedCell();
                    } else {
                        g.hideEditCell(true);
                    }
                } else {
                    if (saved) {
                        g.hideEditCell(true, true);
                    } else {
                        g.hideEditCell(true);
                    }
                }
            }
        }
        
        // wrap all data cells, except actions cell, with span
        $(t).find('th, td:not(:has(span))')
            .wrapInner('<span />');
        
        g.gDiv = document.createElement('div');     // create global div
        g.cRsz = document.createElement('div');     // column resizer
        g.cCpy = document.createElement('div');     // column copy, to store copy of dragged column header
        g.cPointer = document.createElement('div'); // column pointer, used when reordering column
        g.cDrop = document.createElement('div');    // column drop-down arrows
        g.cList = document.createElement('div');    // column visibility list
        g.cEdit = document.createElement('div');    // cell edit
        
        // adjust g.cCpy
        g.cCpy.className = 'cCpy';
        $(g.cCpy).hide();
        
        // adjust g.cPoint
        g.cPointer.className = 'cPointer';
        $(g.cPointer).css('visibility', 'hidden');
        
        // adjust g.cDrop
        g.cDrop.className = 'cDrop';
        
        // adjust g.cList
        g.cList.className = 'cList';
        $(g.cList).hide();
        
        // adjust g.cEdit
        g.cEdit.className = 'cEdit';
        $(g.cEdit).html('<input type="text" /><div class="edit_area" />');
        $(g.cEdit).hide();
        
        // chain table and grid together
        t.grid = g;
        g.t = t;
        
        // get first row data columns
        var $firstRowCols = $(t).find('tr:first th.draggable');
        
        // initialize g.visibleHeadersCount
        g.visibleHeadersCount = $firstRowCols.filter(':visible').length;
        
        // assign first column (actions) span
        if (! $(t).find('tr:first th:first').hasClass('draggable')) {  // action header exist
            g.actionSpan = $(t).find('tr:first th:first').prop('colspan');
        } else {
            g.actionSpan = 0;
        }
        
        // assign table create time
        // #table_create_time will only available if we are in "Browse" tab
        g.tableCreateTime = $('#table_create_time').val();
        
        // assign column reorder & column sort hint
        g.reorderHint = $('#col_order_hint').val();
        g.sortHint = $('#sort_hint').val();
        g.markHint = $('#col_mark_hint').val();
        g.colVisibHint = $('#col_visib_hint').val();
        g.showAllColText = $('#show_all_col_text').val();
        
        // assign cell editing hint
        g.cellEditHint = $('#cell_edit_hint').val();
        g.saveCellWarning = $('#save_cell_warning').val();
        
        // initialize cell editing configuration
        g.saveCellsAtOnce = $('#save_cells_at_once').val();
        
        // initialize column order
        $col_order = $('#col_order');
        if ($col_order.length > 0) {
            g.colOrder = $col_order.val().split(',');
            for (var i = 0; i < g.colOrder.length; i++) {
                g.colOrder[i] = parseInt(g.colOrder[i]);
            }
        } else {
            g.colOrder = new Array();
            for (var i = 0; i < $firstRowCols.length; i++) {
                g.colOrder.push(i);
            }
        }
        
        // initialize column visibility
        $col_visib = $('#col_visib');
        if ($col_visib.length > 0) {
            g.colVisib = $col_visib.val().split(',');
            for (var i = 0; i < g.colVisib.length; i++) {
                g.colVisib[i] = parseInt(g.colVisib[i]);
            }
        } else {
            g.colVisib = new Array();
            for (var i = 0; i < $firstRowCols.length; i++) {
                g.colVisib.push(1);
            }
        }
        
        if ($firstRowCols.length > 1) {
            // create column drop-down arrow(s)
            $(t).find('th:not(.draggable)').each(function() {
                var cd = document.createElement('div'); // column drop-down arrow
                var pos = $(this).position();
                $(cd).addClass('coldrop')
                    .css({
                        left: pos.left + $(this).width() - $(cd).width(),
                        top: pos.top
                    })
                    .click(function() {
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
            for (var i = 0; i < $firstRowCols.length; i++) {
                var currHeader = $firstRowCols[i];
                var listElmt = document.createElement('div');
                $(listElmt).text($(currHeader).text())
                    .prepend('<input type="checkbox" ' + (g.colVisib[i] ? 'checked="checked" ' : '') + '/>');
                $listDiv.append(listElmt);
                // add event on click
                $(listElmt).click(function() {
                    if ( g.toggleCol($(this).index()) ) {
                        g.afterToggleCol();
                    }
                });
            }
            // add "show all column" button
            var showAll = document.createElement('div');
            $(showAll).addClass('showAllColBtn')
                .text(g.showAllColText);
            $(g.cList).append(showAll);
            $(showAll).click(function() {
                g.showAllColumns();
            });
            // prepend "show all column" button at top if the list is too long
            if ($firstRowCols.length > 10) {
                var clone = showAll.cloneNode(true);
                $(g.cList).prepend(clone);
                $(clone).click(function() {
                    g.showAllColumns();
                });
            }
        }
        
        // create column borders
        $firstRowCols.each(function() {
            $this = $(this);
            var cb = document.createElement('div'); // column border
            $(cb).addClass('colborder')
                .mousedown(function(e) {
                    g.dragStartRsz(e, this);
                });
            $(g.cRsz).append(cb);
        });
        g.reposRsz();
        
        // bind event to update currently hovered qtip API
        $(t).find('th').mouseenter(function(e) {
            g.qtip = $(this).qtip('api');
        });
        
        // create qtip for each <th> with draggable class
        PMA_createqTip($(t).find('th.draggable'));
        
        // register events
        if (g.reorderHint) {    // make sure columns is reorderable
            $(t).find('th.draggable')
                .mousedown(function(e) {
                    if (g.visibleHeadersCount > 1) {
                        g.dragStartMove(e, this);
                    }
                })
                .mouseenter(function(e) {
                    if (g.visibleHeadersCount > 1) {
                        g.showReorderHint = true;
                        $(this).css('cursor', 'move');
                    } else {
                        $(this).css('cursor', 'inherit');
                    }
                    g.updateHint(e);
                })
                .mouseleave(function(e) {
                    g.showReorderHint = false;
                    g.updateHint(e);
                });
        }
        if ($firstRowCols.length > 1) {
            var $colVisibTh = $(t).find('th:not(.draggable)');
            
            PMA_createqTip($colVisibTh);
            $colVisibTh.mouseenter(function(e) {
                    g.showColVisibHint = true;
                    g.updateHint(e);
                })
                .mouseleave(function(e) {
                    g.showColVisibHint = false;
                    g.updateHint(e);
                });
        }
        $(t).find('th.draggable a')
            .attr('title', '')          // hide default tooltip for sorting
            .mouseenter(function(e) {
                g.showSortHint = true;
                g.updateHint(e);
            })
            .mouseleave(function(e) {
                g.showSortHint = false;
                g.updateHint(e);
            });
        $(t).find('th.marker')
            .mouseenter(function(e) {
                g.showMarkHint = true;
                g.updateHint(e);
            })
            .mouseleave(function(e) {
                g.showMarkHint = false;
                g.updateHint(e);
            });
        $(document).mousemove(function(e) {
            g.dragMove(e);
        });
        $(document).mouseup(function(e) {
            g.dragEnd(e);
        });
        $('.restore_column').click(function() {
            g.restoreColOrder();
        });
        $(t).find('td, th.draggable').mouseenter(function() {
            g.hideColList();
        });
        // edit cell event
        $(t).find('td.data')
            .click(function(e) {
                if (g.isCellEditActive) {
                    g.saveOrPostEditedCell();
                    e.stopPropagation();
                } else {
                    g.showEditCell(this);
                    e.stopPropagation();
                }
                // prevent default action when clicking on "link" in a table
                if ($(e.target).is('a')) {
                    e.preventDefault();
                }
            });
        $(g.cEdit).find('input[type=text]').focus(function(e) {
            g.showEditArea();
        });
        $(g.cEdit).find('input[type=text], select').live('keydown', function(e) {
            if (e.which == 13) {
                // post on pressing "Enter"
                e.preventDefault();
                g.saveOrPostEditedCell();
            }
        });
        $(g.cEdit).keydown(function(e) {
            if (!g.isEditCellTextEditable) {
                // prevent text editing
                e.preventDefault();
            }
        });
        $('html').click(function(e) {
            // hide edit cell if the click is not from g.cEdit
            if ($(e.target).parents().index(g.cEdit) == -1) {
                g.hideEditCell();
            }
        });
        $('html').keydown(function(e) {
            if (e.which == 27 && g.isCellEditActive) {

                // cancel on pressing "Esc"
                g.hideEditCell(true);
            }
        });
        $('.save_edited').click(function() {
            g.hideEditCell();
            g.postEditedCell();
        });
        $(window).bind('beforeunload', function(e) {
            if (g.isCellEdited) {
                g.saveCellWarning;
            }
        });
        
        // add table class
        $(t).addClass('pma_table');
        
        // link all divs
        $(t).before(g.gDiv);
        $(g.gDiv).append(t);
        $(g.gDiv).prepend(g.cRsz);
        $(g.gDiv).append(g.cPointer);
        $(g.gDiv).append(g.cDrop);
        $(g.gDiv).append(g.cList);
        $(g.gDiv).append(g.cCpy);
        $(g.gDiv).append(g.cEdit);

        // some adjustment
        g.refreshRestoreButton();
        g.cRsz.className = 'cRsz';
        $(t).removeClass('data');
        $(g.gDiv).addClass('data');
        $(g.cRsz).css('height', $(t).height());
        $(t).find('th a').bind('dragstart', function() {
            return false;
        });
    };
    
    // document ready checking
    var docready = false;
    $(document).ready(function() {
        docready = true;
    });
    
    // Additional jQuery functions
    /**
     * Make resizable, reorderable grid.
     */
    $.fn.makegrid = function() {
        return this.each(function() {
            if (!docready) {
                var t = this;
                $(document).ready(function() {
                    $.grid(t);
                    t.grid.reposDrop();
                });
            } else {
                $.grid(this);
                this.grid.reposDrop();
            }
        });
    };
    /**
     * Refresh grid. This must be called after changing the grid's content.
     */
    $.fn.refreshgrid = function() {
        return this.each(function() {
            if (!docready) {
                var t = this;
                $(document).ready(function() {
                    if (t.grid) {
                        t.grid.reposRsz();
                        t.grid.reposDrop();
                    }
                });
            } else {
                if (this.grid) {
                    this.grid.reposRsz();
                    this.grid.reposDrop();
                }
            }
        });
    }
    
})(jQuery);

