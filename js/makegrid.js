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
            cellEditHint: '',           // text hint when doing grid edit
            
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
                if (g.isInEditMode) {
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
                if (g.isInEditMode) {
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
                if (g.isInEditMode &&
                    $(cell).is('.inline_edit') &&
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
                                width: $cell.outerWidth() - 16,
                                height: $cell.outerHeight()
                            });
                        // fill the cell edit with text from <td>, if it is not null
                        var value = $cell.is(':not(.null)') ? PMA_getCellValue(cell) : '';
                        $(g.cEdit).find('input')
                            .val(value);
                        
                        g.isCellEditActive = false;
                        g.currentEditCell = cell;
                    }
                } else {
                    g.hideEditCell();
                }
            },
            
            /**
             * Remove edit cell and the edit area, if it is shown.
             *
             * @param force Optional, force to hide edit cell without saving edited field.
             * @param data  Optional, data from the POST AJAX request to save the edited field.
             */
            hideEditCell: function(force, data) {
                if (g.isCellEditActive && !force) {
                    // cell is being edited, post the edited data
                    g.isCellEditActive = false;
                    g.postEditedCell();
                    return;
                }
                $(g.cEdit).hide();
                $(g.cEdit).find('input[type=text]').blur();
                g.isCellEditActive = false;
                
                if (data) {
                    // Cell edit post has been successful.
                    $this_field = $(g.currentEditCell);
                    $this_field_span = $this_field.children('span');

                    var is_null = $(g.cEdit).find('input:checkbox').is(':checked');
                    if (is_null) {
                        $this_field_span.html('NULL');
                        $this_field.addClass('null');
                    } else {
                        $this_field.removeClass('null');
                        if($this_field.is(':not(.relation, .enum, .set)')) {
                            /**
                             * @var new_html    String containing value of the data field after edit
                             */
                            var new_html = $(g.cEdit).find('textarea').val();

                            if($this_field.is('.transformed')) {
                                var field_name = getFieldName($this_field);
                                if (typeof data.transformations != 'undefined') {
                                    $.each(data.transformations, function(key, value) {
                                        if(key == field_name) {
                                            if($this_field.is('.text_plain, .application_octetstream')) {
                                                new_html = value;
                                                return false;
                                            } else {
                                                var new_value = $(g.cEdit).find('textarea').val();
                                                new_html = $(value).append(new_value);
                                                return false;
                                            }
                                        }
                                    })
                                }
                            }
                            // replace '\n' with <br>
                            new_html = new_html.replace(/\n/g, '<br />');
                        } else {
                            var new_html = '';
                            var new_value = '';
                            $test_element = $(g.cEdit).find('select');
                            if ($test_element.length != 0) {
                                new_value = $test_element.val();
                            }
                            $test_element = $this_field.find('span.curr_value');
                            if ($test_element.length != 0) {
                                new_value = $test_element.text();
                            }

                            if($this_field.is('.relation')) {
                                var field_name = getFieldName($this_field);
                                if (typeof data.relations != 'undefined') {
                                    $.each(data.relations, function(key, value) {
                                        if(key == field_name) {
                                            new_html = $(value);
                                            return false;
                                        }
                                    })
                                }
                            } else if ($this_field.is('.enum')) {
                                new_html = new_value;
                            } else if ($this_field.is('.set')) {
                                if (new_value != null) {
                                    $.each(new_value, function(key, value) {
                                        new_html = new_html + value + ',';
                                    })
                                    new_html = new_html.substring(0, new_html.length-1);
                                }
                            }
                        }
                        $this_field_span.html(new_html);
                    }
                    // refresh the grid
                    this.reposRsz();
                    this.reposDrop();
                }   // end of if "data" is defined, i.e. post successful
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
                    var relation_curr_value = $td.find('a').text();
                    /**
                     * @var relation_key_or_display_column String relational key if in 'Relational display column' mode,
                     * relational display column if in 'Relational key' mode (for fields that are foreign keyed).
                     */
                    var relation_key_or_display_column = $td.find('a').attr('title');
                    
                    // empty all edit area, then rebuild it based on $td classes
                    $editArea.empty();
                    
                    if ($td.is(':not(.not_null)')) {
                        // append a null checkbox
                        $editArea.append('<div class="null_div">Null :<input type="checkbox"></div>');
                        var $checkbox = $editArea.find('.null_div input');
                        // check if current <td> is NULL
                        if ($td.is('.null')) {
                            $checkbox.attr('checked', true);
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
                    
                    if($td.is('.truncated, .transformed')) {
                        /** @lends jQuery */
                        //handle truncated/transformed values values
                        $editArea.addClass('edit_area_loading');

                        /**
                         * @var sql_query   String containing the SQL query used to retrieve value of truncated/transformed data
                         */
                        var sql_query = 'SELECT `' + field_name + '` FROM `' + window.parent.table + '` WHERE ' + PMA_urldecode(where_clause);

                        // Make the Ajax call and get the data, wrap it and insert it
                        $.post('sql.php', {
                            'token' : window.parent.token,
                            'db' : window.parent.db,
                            'ajax_request' : true,
                            'sql_query' : sql_query,
                            'inline_edit' : true
                        }, function(data) {
                            $editArea.removeClass('edit_area_loading');
                            if(data.success == true) {
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
                        g.isEditCellTextEditable = true;
                    }
                    else if($td.is('.relation')) {
                        /** @lends jQuery */
                        //handle relations
                        $editArea.addClass('edit_area_loading');

                        /**
                         * @var post_params Object containing parameters for the POST request
                         */
                        var post_params = {
                                'ajax_request' : true,
                                'get_relational_values' : true,
                                'db' : window.parent.db,
                                'table' : window.parent.table,
                                'column' : field_name,
                                'token' : window.parent.token,
                                'curr_value' : relation_curr_value,
                                'relation_key_or_display_column' : relation_key_or_display_column
                        }

                        $.post('sql.php', post_params, function(data) {
                            $editArea.removeClass('edit_area_loading');
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
                                'db' : window.parent.db,
                                'table' : window.parent.table,
                                'column' : field_name,
                                'token' : window.parent.token,
                                'curr_value' : curr_value
                        }
                        $.post('sql.php', post_params, function(data) {
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
                                'db' : window.parent.db,
                                'table' : window.parent.table,
                                'column' : field_name,
                                'token' : window.parent.token,
                                'curr_value' : curr_value
                        }

                        $.post('sql.php', post_params, function(data) {
                            $editArea.removeClass('edit_area_loading');
                            $editArea.append(data.select);
                            $editArea.append('<div class="cell_edit_hint">' + g.cellEditHint + '</div>');
                        }) // end $.post()
                        
                        $editArea.find('select').live('change', function(e) {
                            $(g.cEdit).find('input[type=text]').val($(this).val());
                        })
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
            
                event.preventDefault();

                /**
                 * @var $this_field    Object referring to the td that is being edited
                 */
                var $this_field = $(g.currentEditCell);
                var $test_element = ''; // to test the presence of a element

                // Initialize variables
                var where_clause = $this_field.parent('tr').find('.where_clause').val();

                /**
                 * @var nonunique   Boolean, whether this row is unique or not
                 */
                var nonunique = $this_field.is('.nonunique') ? 0 : 1;
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
                 * @var transformation_fields   Boolean, if there are any transformed fields in this row
                 */
                var transformation_fields = false;

                /**
                 * @var sql_query String containing the SQL query to update this row
                 */
                var sql_query = 'UPDATE `' + window.parent.table + '` SET ';

                var need_to_post = false;

                var new_clause = '';

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
                var is_null = $(g.cEdit).find('input:checkbox').is(':checked');
                var value;
                var addQuotes = true;

                if (is_null) {
                    sql_query += ' `' + field_name + "`=NULL , ";
                    need_to_post = true;
                } else {
                    if($this_field.is(":not(.relation, .enum, .set, .bit)")) {
                        this_field_params[field_name] = $(g.cEdit).find('textarea').val();
                        if($this_field.is('.transformed')) {
                            $.extend(transform_fields, this_field_params);
                        }
                    } else if ($this_field.is('.bit')) {
                        this_field_params[field_name] = '0b' + $(g.cEdit).find('textarea').val();
                        addQuotes = false;
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

                        if($this_field.is('.relation')) {
                            $.extend(relation_fields, this_field_params);
                        }
                    }
                    if (where_clause.indexOf(field_name) > -1) {
                        new_clause += '`' + window.parent.table + '`.' + '`' + field_name + "` = '" + this_field_params[field_name].replace(/'/g,"''") + "'" + ' AND ';
                    }
                    if (this_field_params[field_name] != PMA_getCellValue(g.currentEditCell)) {
                        if (addQuotes == true) {
                            sql_query += ' `' + field_name + "`='" + this_field_params[field_name].replace(/'/g, "''") + "', ";
                        } else {
                            sql_query += ' `' + field_name + "`=" + this_field_params[field_name].replace(/'/g, "''") + ", ";
                        }
                        need_to_post = true;
                    }
                }

                /*
                 * update the where_clause, remove the last appended ' AND '
                 * */

                //Remove the last ',' appended in the above loop
                sql_query = sql_query.replace(/,\s$/, '');
                //Fix non-escaped backslashes
                sql_query = sql_query.replace(/\\/g, '\\\\');
                new_clause = new_clause.substring(0, new_clause.length-5);
                new_clause = PMA_urlencode(new_clause);
                sql_query += ' WHERE ' + PMA_urldecode(where_clause);
                // Avoid updating more than one row in case there is no primary key
                // (happened only for duplicate rows)
                sql_query += ' LIMIT 1';
                /**
                 * @var rel_fields_list  String, url encoded representation of {@link relations_fields}
                 */
                var rel_fields_list = $.param(relation_fields);

                /**
                 * @var transform_fields_list  String, url encoded representation of {@link transform_fields}
                 */
                var transform_fields_list = $.param(transform_fields);

                if (need_to_post) {
                    // Make the Ajax post after setting all parameters
                    /**
                     * @var post_params Object containing parameters for the POST request
                     */
                    var post_params = {'ajax_request' : true,
                                    'sql_query' : sql_query,
                                    'token' : window.parent.token,
                                    'db' : window.parent.db,
                                    'table' : window.parent.table,
                                    'clause_is_unique' : nonunique,
                                    'where_clause' : where_clause,
                                    'rel_fields_list' : rel_fields_list,
                                    'do_transformations' : transformation_fields,
                                    'transform_fields_list' : transform_fields_list,
                                    'relational_display' : relational_display,
                                    'goto' : 'sql.php',
                                    'submit_type' : 'save'
                                  };
                    
                    var $editArea = $(g.cEdit).find('.edit_area');
                    $editArea.addClass('edit_area_posting');

                    $.post('tbl_replace.php', post_params, function(data) {
                        $editArea.removeClass('edit_area_posting');
                        if(data.success == true) {
                            PMA_ajaxShowMessage(data.message);
                            if (new_clause != '') {
                                $this_field.parent('tr').find('.where_clause').attr('value', new_clause);
                            }
                            // remove possible previous feedback message
                            $('#result_query').remove();
                            if (typeof data.sql_query != 'undefined') {
                                // display feedback
                                $('#sqlqueryresults').prepend(data.sql_query);
                            }
                            //PMA_unInlineEditRow($del_hide, $chg_submit, $this_field, $input_siblings, data);
                            g.hideEditCell(true, data);
                        } else {
                            PMA_ajaxShowMessage(data.error);
                        };
                    }) // end $.post()
                } else {
                    // no posting was done but still need to display the row
                    // in its previous format
                    //PMA_unInlineEditRow($del_hide, $chg_submit, $this_field, $input_siblings, '');
                    g.hideEditCell();
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
        
        // enable "Edit table" button
        $('.edit_mode').removeClass('hide')
            .click(function(e) {
                g.isInEditMode = !g.isInEditMode;
                $('.edit_mode input').toggleClass('edit_mode_active', g.isInEditMode);
                if (!g.isInEditMode) {
                    g.hideEditCell();
                }
            });
        
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
            .mouseenter(function() {
                g.showEditCell(this);
            })
            .click(function(e) {
                if (g.isInEditMode) {
                    if (g.isCellEditActive) {
                        g.postEditedCell();
                        e.stopPropagation();
                    } else {
                        g.showEditCell(this);
                        $(g.cEdit).find('input[type=text]').focus();
                        e.stopPropagation();
                    }
                }
            });
        $(g.cEdit).find('input[type=text]').focus(function(e) {
            g.showEditArea();
        });
        $(g.cEdit).find('input[type=text], select').live('keydown', function(e) {
            if (e.which == 13) {
                // post on pressing "Enter"
                e.preventDefault();
                g.postEditedCell();
            }
        });
        $(g.cEdit).keydown(function(e) {
            if (e.which == 27) {
                // cancel on pressing "Esc"
                g.hideEditCell(true);
            } else if (!g.isEditCellTextEditable) {
                // prevent text editing
                e.preventDefault();
            }
        });
        $('html').click(function(e) {
            // hide edit cell if the click is not from g.cEdit
            if ($(e.target).parents().index(g.cEdit) == -1) {
                if (g.isCellEditActive) {
                    g.hideEditCell();
                }
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

