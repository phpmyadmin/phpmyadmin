(function ($) {
    $.grid = function(t) {
        // prepare the grid
        var g = {
            // constant
            minColWidth: 5,
            
            // changeable vars
            alignment: 'horizontal',
            actionSpan: 5,
            
            // functions
            dragStartRsz: function(e, obj) {    // start column resize
                var n = $(this.cRsz).find('div').index(obj);
                this.colRsz = {
                    x0: e.pageX,
                    n: n,
                    obj: obj,
                    objLeft: parseInt(obj.style.left),
                    objWidth: this.alignment == 'horizontal' ?
                              $(this.t).find('tr:first th:eq(' + (1 + n) + ') span').width() :
                              $(this.t).find('tr:first td:eq(' + n + ') span').width()
                };
                $('body').css('cursor', 'col-resize');
                $('body').noSelect();
            },
            
            dragStartMove: function(e, obj) {   // start column move
                // prepare the cCpy from the dragged column
                $(this.cCpy).html($(obj).html());
                var objPos = $(obj).position();
                if (this.alignment == 'horizontal') {
                    $(this.cCpy).css({
                        top: objPos.top + 20,
                        left: objPos.left,
                        width: $(obj).width()
                    });
                } else {    // vertical alignment
                    $(this.cCpy).css({
                        top: objPos.top,
                        left: objPos.left + 30,
                        width: $(obj).width()
                    });
                }
                // get the column index
                var n = $(this.t).find('th:gt(0)').index(obj);
                this.colMov = {
                    x0: e.pageX,
                    y0: e.pageY,
                    n: n,
                    obj: obj,
                    objTop: parseInt(objPos.top),
                    objLeft: parseInt(objPos.left)
                };
                $('body').noSelect();
            },
            
            dragMove: function(e) {
                if (this.colRsz) {
                    var dx = e.pageX - this.colRsz.x0;
                    if (this.colRsz.objWidth + dx > this.minColWidth)
                        $(this.colRsz.obj).css('left', this.colRsz.objLeft + dx);
                } else if (this.colMov) {
                    // movement animation
                    if (this.alignment == 'horizontal') {
                        var dx = e.pageX - this.colMov.x0;
                        $(this.cCpy)
                            .css('left', this.colMov.objLeft + dx)
                            .fadeIn();
                    } else {    // vertical alignment
                        var dy = e.pageY - this.colMov.y0;
                        $(this.cCpy)
                            .css('top', this.colMov.objTop + dy)
                            .fadeIn();
                    }
                    $(this.t).stop(true, true).fadeTo('normal', 0.5);
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
                    if (this.alignment == 'horizontal') {
                        $(this.t).find('tr').each(function() {
                            $(this).find('th:eq(' + (1 + n) + ') span,' +
                                         'td:eq(' + (g.actionSpan + n) + ') span')
                                   .css('width', nw + 'px');
                        });
                    } else {    // vertical alignment
                        $(this.t).find('tr').each(function() {
                            $(this).find('td:eq(' + n + ') span')
                                   .css('width', nw + 'px');
                        });
                    }
                    $('body').css('cursor', 'default');
                    this.reposRsz();
                    this.colRsz = false;
                } else if (this.colMov) {
                    $(this.t).stop(true, true).fadeTo('fast', 1.0);
                    
                    // find current hovered column
                    var hoveredCol;
                    $headers = $(this.t).find('th:gt(0)');
                    if (this.alignment == 'horizontal') {
                        $headers.each(function() {
                            var left = $(this).position().left;
                            var right = left + $(this).width();
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
                    if (hoveredCol) {
                        // shift columns if new column is hovered
                        var newn = $(this.t).find('th:gt(0)').index(hoveredCol);
                        if (newn != this.colMov.n) {
                            this.shiftCol(this.colMov.n, newn);
                            // assign new position
                            var objPos = $(this.colMov.obj).position();
                            this.colMov.objTop = objPos.top;
                            this.colMov.objLeft = objPos.left;
                            this.colMov.n = newn;
                        }
                    }
                    
                    // animate new column position
                    $(this.cCpy).stop(true, true)
                        .animate({
                            top: g.colMov.objTop,
                            left: g.colMov.objLeft
                        }, 'fast')
                        .fadeOut();

                    this.colMov = false;
                }
            },
            
            /**
             * Reposition column resize bars.
             */
            reposRsz: function() {
                $firstRowCols = this.alignment == 'horizontal' ?
                                $(this.t).find('tr:first th:gt(0)') :
                                $(this.t).find('tr:first td');
                $firstRowCols.each(function() {
                    $this = $(this);
                    var n = $this.index();
                    $cb = $(g.cRsz).find('div:eq(' + (n - 1) + ')');   // column border
                    $cb.css('left', $this.position().left + $this.width());
                });
            },
            
            /**
             * Shift column from index oldn to newn.
             */
            shiftCol: function(oldn, newn) {
                if (this.alignment == 'horizontal') {
                    // shift header
                    $(this.t).find('thead tr').each(function() {
                        if (newn < oldn) {
                            $(this).find('th:eq(' + (1 + newn) + ')')
                                   .before($(this).find('th:eq(' + (1 + oldn) + ')'));
                        } else {
                            $(this).find('th:eq(' + (1 + newn) + ')')
                                   .after($(this).find('th:eq(' + (1 + oldn) + ')'));
                        }
                    });
                    // shift data
                    $(this.t).find('tbody tr').each(function() {
                        if (newn < oldn) {
                            $(this).find('td:eq(' + (g.actionSpan + newn) + ')')
                                   .before($(this).find('td:eq(' + (g.actionSpan + oldn) + ')'));
                        } else {
                            $(this).find('td:eq(' + (g.actionSpan + newn) + ')')
                                   .after($(this).find('td:eq(' + (g.actionSpan + oldn) + ')'));
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
            }
        }
        
        g.gDiv = document.createElement('div');   // create global div
        g.cRsz = document.createElement('div');   // column resizer
        g.cCpy = document.createElement('div');   // column copy, to store copy of dragged column header
        
        // adjust g.cCpy
        g.cCpy.className = 'cCpy';
        $(g.cCpy).hide();
        
        // chain table and grid together
        t.grid = g;
        g.t = t;
        
        // assign the table alignment
        // Currently, we can detect the alignment from the first table header.
        // If the first th have children (<- T -> symbol), then the alignment is horizontal, vertical otherwise.
        g.alignment = $(t).find('th:first').children().length > 0 ? 'horizontal' : 'vertical';
        
        // assign the first column (actions) span
        g.actionSpan = g.alignment == 'horizontal' ?
                       parseInt($(t).find('tr:first th:first').attr('colspan')) :
                       parseInt($(t).find('tr:first th:first').attr('rowspan'));
        
        // create column borders
        $firstRowCols = g.alignment == 'horizontal' ?
                        $(t).find('tr:first th:gt(0)') :
                        $(t).find('tr:first td');
        $firstRowCols.each(function() {
            $this = $(this);
            var cb = document.createElement('div'); // column border
            cb.style.left = $this.position().left + $this.width() + 'px';
            cb.className = 'colborder';
            $(cb).mousedown(function(e) {
                g.dragStartRsz(e, this);
            });
            $(g.cRsz).append(cb);
        });
        
        // wrap all data cells, except actions cell, with span
        $(t).find('th, td:not(:has(span))')
            .wrapInner('<span />');
        
        // create draggable header
        $(t).find('th:gt(0)').addClass('draggable');
        
        // register events
        $(t).find('th').mousedown(function(e) {
            g.dragStartMove(e, this);
        });
        $(document).mousemove(function(e) {
            g.dragMove(e);
        });
        $(document).mouseup(function(e) {
            g.dragEnd(e);
        });
        
        // add table class
        $(t).addClass('pma_table');
        
        // link all divs
        $(t).before(g.gDiv);
        $(g.gDiv).append(t);
        $(g.gDiv).prepend(g.cRsz);
        $(g.gDiv).append(g.cCpy);

        // some adjustment
        g.cRsz.className = 'cRsz';
        $(t).removeClass('data');
        $(g.gDiv).addClass('data');
        $(g.cRsz).css('height', $(t).height());
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

