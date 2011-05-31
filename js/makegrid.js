(function ($) {
    $.grid = function(t) {
        // prepare the grid
        var g = {
            // constant
            minColWidth: 5,
            
            // changeable vars
            firstColSpan: 5,    // default to 5, only useful in horizontal mode
            
            // functions
            dragStartRsz: function(e, obj) {
                var n = $('div', this.cRsz).index(obj);
                this.colRsz = {
                    x0: e.screenX,
                    n: n,
                    obj: obj,
                    objLeft: parseInt(obj.style.left),
                    objWidth: $('tr:first th:eq(' + (1 + n) + ') div,' +
                                'tr:first td:eq(' + (n) + ') div', this.t).width()
                };
                $('body').css('cursor', 'col-resize');
                $('body').noSelect();
            },
            dragMove: function(e) {
                if (this.colRsz) {
                    var dx = e.screenX - this.colRsz.x0;
                    if (this.colRsz.objWidth + dx > this.minColWidth)
                        $(this.colRsz.obj).css('left', this.colRsz.objLeft + dx);
                }
            },
            dragEnd: function(e) {
                if (this.colRsz) {
                    var dx = e.screenX - this.colRsz.x0;
                    var nw = this.colRsz.objWidth + dx;
                    if (nw < this.minColWidth) {
                        nw = this.minColWidth;
                    }
                    var n = this.colRsz.n;
                    $('tr', this.t).each(function() {
                        $('th:eq(' + (1 + n) + ') div, td:eq(' + (g.firstColSpan + n) + ') div', this).each(function() {
                            $(this).css('width', nw + 'px');
                        });
                    });
                    $('body').css('cursor', 'default');
                    this.reposRsz();
                    this.colRsz = false;
                }
            },
            reposRsz: function() {
                $(this.t).find('tr:first th:gt(0), tr:first td').each(function() {
                    $this = $(this);
                    var n = $this.index();
                    $cb = $('div:eq(' + (n - 1) + ')', g.cRsz);   // column border
                    $cb.css('left', $this.position().left + $this.width());
                });
            }
        }
        g.gDiv = document.createElement('div');   // create global div
        g.cRsz = document.createElement('div');   // column resizer
        g.t = t;
        
        // assign the first column (actions) span
        g.firstColSpan = $('tr:first th:first', t).attr('colspan');
        g.firstColSpan = (g.firstColSpan != undefined) ? parseInt(g.firstColSpan) : 0;  // posibility of vertical display mode
        
        // create column borders
        $(t).find('tr:first th:gt(0), tr:first td').each(function() {
            $this = $(this);
            var cb = document.createElement('div'); // column border
            cb.style.left = $this.position().left + $this.width() + 'px';
            cb.style.height = '100%';
            cb.className = 'colborder';
            $(cb).mousedown(function(e) {
                g.dragStartRsz(e, this);
            });
            $(g.cRsz).append(cb);
        });
        
        // wrap all cells with div
        $(t).find('th, td').each(function() {
            $(this).wrapInner('<div />');
        });
        
        // register events
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

        // some adjustment
        g.cRsz.className = 'cRsz';
        $(g.cRsz).css('height', $(t).height());
        $(t).removeClass('data');
        $(g.gDiv).addClass('data');
    };
    
    // document ready checking
    var docready = false;
    $(document).ready(function() {
        docready = true;
    });
    
    // Additional jQuery functions
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

