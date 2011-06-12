(function ($) {
    $.grid = function(t) {
        // prepare the grid
        var g = {
            // constant
            minColWidth: 5,
            
            // variables, assigned with default value, changed later
            alignment: 'horizontal',    // 3 possibilities: vertical, horizontal, horizontalflipped
            actionSpan: 5,
            colOrder: new Array(),
            tableCreateTime: null,
            hintShown: false,
            
            // functions
            dragStartRsz: function(e, obj) {    // start column resize
                var n = $(this.cRsz).find('div').index(obj);
                this.colRsz = {
                    x0: e.pageX,
                    n: n,
                    obj: obj,
                    objLeft: $(obj).position().left,
                    objWidth: this.alignment != 'vertical' ?
                              $(this.t).find('th.draggable:eq(' + n + ') span').outerWidth() :
                              $(this.t).find('tr:first td:eq(' + n + ') span').outerWidth()
                };
                $('body').css('cursor', 'col-resize');
                $('body').noSelect();
            },
            
            dragStartMove: function(e, obj) {   // start column move
                // prepare the cCpy and cPointer from the dragged column
                $(this.cCpy).text($(obj).text());
                var objPos = $(obj).position();
                if (this.alignment != 'vertical') {
                    $(this.cCpy).css({
                        top: objPos.top + 20,
                        left: objPos.left,
                        height: $(obj).height(),
                        width: $(obj).width()
                    });
                    $(this.cPointer).css({
                        top: objPos.top - 10,
                        left: objPos.left
                    });
                } else {    // vertical alignment
                    $(this.cCpy).css({
                        top: objPos.top,
                        left: objPos.left + 30,
                        height: $(obj).height(),
                        width: $(obj).width()
                    });
                    $(this.cPointer).css({
                        top: objPos.top,
                        left: objPos.left
                    });
                }
                
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
                $('body').css('cursor', 'move');
                this.hideDraggableHint();
                $('body').noSelect();
            },
            
            dragMove: function(e) {
                if (this.colRsz) {
                    var dx = e.pageX - this.colRsz.x0;
                    if (this.colRsz.objWidth + dx > this.minColWidth)
                        $(this.colRsz.obj).css('left', this.colRsz.objLeft + dx + 'px');
                } else if (this.colMov) {
                    // dragged column animation
                    if (this.alignment != 'vertical') {
                        var dx = e.pageX - this.colMov.x0;
                        $(this.cCpy)
                            .css('left', this.colMov.objLeft + dx)
                            .show();
                    } else {    // vertical alignment
                        var dy = e.pageY - this.colMov.y0;
                        $(this.cCpy)
                            .css('top', this.colMov.objTop + dy)
                            .show();
                    }
                    
                    // pointer animation
                    var hoveredCol = this.getHoveredCol(e);
                    if (hoveredCol) {
                        var newn = this.getHeaderIdx(hoveredCol);
                        this.colMov.newn = newn;
                        if (newn != this.colMov.n) {
                            // show the column pointer in the right place
                            var colPos = $(hoveredCol).position();
                            if (this.alignment != 'vertical') {
                                var newleft = newn < this.colMov.n ?
                                              colPos.left :
                                              colPos.left + $(hoveredCol).outerWidth();
                                $(this.cPointer)
                                    .css('left', newleft)
                                    .show();
                            } else {    // vertical alignment
                                var newtop = newn < this.colMov.n ?
                                              colPos.top :
                                              colPos.top + $(hoveredCol).outerHeight();
                                $(this.cPointer)
                                    .css('top', newtop)
                                    .show();
                            }
                        } else {
                            // no movement to other column, hide the column pointer
                            $(this.cPointer).hide();
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
                    if (this.alignment != 'vertical') {
                        $(this.t).find('tr').each(function() {
                            $(this).find('th.draggable:eq(' + n + ') span,' +
                                         'td:eq(' + (g.actionSpan + n) + ') span')
                                   .css('width', nw);
                        });
                    } else {    // vertical alignment
                        $(this.t).find('tr').each(function() {
                            $(this).find('td:eq(' + n + ') span')
                                   .css('width', nw);
                        });
                    }
                    $('body').css('cursor', 'default');
                    this.reposRsz();
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
                        this.sendColOrder();
                        this.refreshRestoreButton();
                    }
                    
                    // animate new column position
                    $(this.cCpy).stop(true, true)
                        .animate({
                            top: g.colMov.objTop,
                            left: g.colMov.objLeft
                        }, 'fast')
                        .fadeOut();
                    $(this.cPointer).stop(true, true).hide();

                    this.colMov = false;
                }
                $('body').css('cursor', 'default');
                $('body').noSelect(false);
            },
            
            /**
             * Reposition column resize bars.
             */
            reposRsz: function() {
                $(this.cRsz).find('div').hide();
                $firstRowCols = this.alignment != 'vertical' ?
                                $(this.t).find('tr:first th.draggable') :
                                $(this.t).find('tr:first td');
                for (var n = 0; n < $firstRowCols.length; n++) {
                    $this = $($firstRowCols[n]);
                    $cb = $(g.cRsz).find('div:eq(' + n + ')');   // column border
                    var pad = parseInt($this.css('padding-right'));
                    $cb.css('left', Math.floor($this.position().left + $this.width() + pad))
                       .show();
                }
            },
            
            /**
             * Shift column from index oldn to newn.
             */
            shiftCol: function(oldn, newn) {
                if (this.alignment != 'vertical') {
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
                    
                } else {    // vertical alignment
                    // shift rows
                    if (newn < oldn) {
                        $(this.t).find('tr:eq(' + (g.actionSpan + newn) + ')')
                               .before($(this.t).find('tr:eq(' + (g.actionSpan + oldn) + ')'));
                    } else {
                        $(this.t).find('tr:eq(' + (g.actionSpan + newn) + ')')
                               .after($(this.t).find('tr:eq(' + (g.actionSpan + oldn) + ')'));
                    }
                }
                // adjust the colOrder
                var tmp = this.colOrder[oldn];
                this.colOrder.splice(oldn, 1);
                this.colOrder.splice(newn, 0, tmp);
            },
            
            /**
             * Find currently hovered table column's header (excluding actions column).
             * @return the hovered column's th object or undefined if no hovered column found.
             */
            getHoveredCol: function(e) {
                var hoveredCol;
                $headers = $(this.t).find('th.draggable');
                if (this.alignment != 'vertical') {
                    $headers.each(function() {
                        var left = $(this).position().left;
                        var right = left + $(this).outerWidth();
                        if (left <= e.pageX && e.pageX <= right) {
                            hoveredCol = this;
                        }
                    });
                } else {    // vertical alignment
                    $headers.each(function() {
                        var top = $(this).position().top;
                        var bottom = top + $(this).height();
                        if (top <= e.pageY && e.pageY <= bottom) {
                            hoveredCol = this;
                        }
                    });
                }
                return hoveredCol;
            },
            
            /**
             * Get a zero-based index from a <th class="draggable"> tag in a table.
             */
            getHeaderIdx: function(obj) {
                var n;
                if (this.alignment != 'vertical') {
                    n = $(obj).parents('tr').find('th.draggable').index(obj);
                } else {
                    var column_idx = $(obj).index();
                    var $th_in_same_column = $(this.t).find('th.draggable:nth-child(' + (column_idx + 1) + ')');
                    n = $th_in_same_column.index(obj);
                }
                return n;
            },
            
            /**
             * Reposition the table back to normal order.
             */
            restore: function() {
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
                // send request to server to remember the column order
                this.sendColOrder();
                this.refreshRestoreButton();
            },
            
            /**
             * Send column order to the server.
             */
            sendColOrder: function() {
                $.get('sql.php', {
                    ajax_request: true,
                    db: window.parent.db,
                    table: window.parent.table,
                    token: window.parent.token,
                    set_col_order: true,
                    col_order: this.colOrder,
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
                // enable or disable restore button
                if (isInitial) {
                    $('.restore_column').hide();
                } else {
                    $('.restore_column').show();
                }
            },
            
            /**
             * Show draggable hint.
             */
            showDraggableHint: function() {
                if (!this.colMov) {     // if not dragging
                    $(this.dHint)
                        .stop(true, true)
                        .show('fast');
                    this.hintShown = true;
                }
            },
            
            /**
             * Hide draggable hint.
             */
            hideDraggableHint: function() {
                if (this.hintShown) {
                    $(this.dHint)
                        .stop(true, true)
                        .hide(150, function() {
                            g.hintShown = false;
                        });
                }
            },
            
            /**
             * Update the draggable hint position.
             */
            updateDraggableHint: function(e) {
                if (this.hintShown) {
                    $(this.dHint).css({
                        top: e.pageY - 10,
                        left: e.pageX + 15
                    });
                }
            }
        }
        
        g.gDiv = document.createElement('div');     // create global div
        g.cRsz = document.createElement('div');     // column resizer
        g.cCpy = document.createElement('div');     // column copy, to store copy of dragged column header
        g.cPointer = document.createElement('div'); // column pointer, used when reordering column
        g.dHint = document.createElement('div');    // draggable hint
        
        // assign the table alignment
        g.alignment = $("#top_direction_dropdown").val();
        
        // adjust g.cCpy
        g.cCpy.className = 'cCpy';
        $(g.cCpy).hide();
        
        // adjust g.cPoint
        g.cPointer.className = g.alignment != 'vertical' ? 'cPointer' : 'cPointerVer';
        $(g.cPointer).hide();
        
        // adjust g.dHint
        g.dHint.className = 'dHint';
        $(g.dHint).html($('#col_order_hint').val());
        $(g.dHint).hide();
        
        // chain table and grid together
        t.grid = g;
        g.t = t;
        
        // get first row data columns
        var $firstRowCols = g.alignment != 'vertical' ?
                            $(t).find('tr:first th.draggable') :
                            $(t).find('tr:first td');
        
        // assign first column (actions) span
        if (! $(t).find('tr:first th:first').hasClass('draggable')) {  // action header exist
            g.actionSpan = g.alignment != 'vertical' ?
                           $(t).find('tr:first th:first').prop('colspan') :
                           $(t).find('tr:first th:first').prop('rowspan');
        } else {
            g.actionSpan = 0;
        }
        
        // assign table create time
        g.tableCreateTime = $('#table_create_time').val();
        
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
        
        // create column borders
        $firstRowCols.each(function() {
            $this = $(this);
            var cb = document.createElement('div'); // column border
            var pad = parseInt($this.css('padding-right'));
            $(cb).css('left', Math.floor($this.position().left + $this.width() + pad));
            $(cb).addClass('colborder');
            $(cb).mousedown(function(e) {
                g.dragStartRsz(e, this);
            });
            $(g.cRsz).append(cb);
        });
        
        // wrap all data cells, except actions cell, with span
        $(t).find('th, td:not(:has(span))')
            .wrapInner('<span />');
        
        // register events
        $(t).find('th.draggable')
            .mousedown(function(e) {
                g.dragStartMove(e, this);
            })
            // show/hide draggable column
            .mouseenter(function(e) {
                g.showDraggableHint();
            })
            .mouseleave(function(e) {
                g.hideDraggableHint();
            });
        $(document).mousemove(function(e) {
            g.dragMove(e);
            g.updateDraggableHint(e);
        });
        $(document).mouseup(function(e) {
            g.dragEnd(e);
        });
        $('.restore_column').click(function() {
            g.restore();
        });
        
        // add table class
        $(t).addClass('pma_table');
        
        // link all divs
        $(t).before(g.gDiv);
        $(g.gDiv).append(t);
        $(g.gDiv).prepend(g.cRsz);
        $(g.gDiv).append(g.cCpy);
        $(g.gDiv).append(g.cPointer);
        $(g.gDiv).append(g.dHint);

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
                });
            } else {
                $.grid(this);
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
                    if (t.grid) t.grid.reposRsz();
                });
            } else {
                if (this.grid) this.grid.reposRsz();
            }
        });
    }
    $.fn.noSelect = function (p) { //no select plugin by Paulo P.Marinas
        var prevent = (p == null) ? true : p;
        if (prevent) {
            return this.each(function () {
                if ($.browser.msie || $.browser.safari) $(this).bind('selectstart', function () {
                    return false;
                });
                else if ($.browser.mozilla) {
                    $(this).css('MozUserSelect', 'none');
                    $('body').trigger('focus');
                } else if ($.browser.opera) $(this).bind('mousedown', function () {
                    return false;
                });
                else $(this).attr('unselectable', 'on');
            });
        } else {
            return this.each(function () {
                if ($.browser.msie || $.browser.safari) $(this).unbind('selectstart');
                else if ($.browser.mozilla) $(this).css('MozUserSelect', 'inherit');
                else if ($.browser.opera) $(this).unbind('mousedown');
                else $(this).removeAttr('unselectable', 'on');
            });
        }
    }; //end noSelect
    
})(jQuery);

