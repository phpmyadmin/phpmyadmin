/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    A jquery plugin that allows drag&drop sorting in tables.
 *                  Coded because JQuery UI sortable doesn't support tables. Also it has no animation
 *
 * @name            Sortable Table JQuery plugin
 *
 * @requires    jQuery
 *
 */

(function($) {
	jQuery.fn.sortableTable = function(method) {
	
		var methods = {
			init : function(options) {
				var tb = new sortableTableInstance(this, options);
				tb.init();
				$(this).data('sortableTable',tb);
			},
			refresh : function( ) { 
				$(this).data('sortableTable').refresh();
			},
			destroy : function( ) { 
				$(this).data('sortableTable').destroy();
			},
		};

		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.sortableTable' );
		}    
	
		function sortableTableInstance(table, options) {
			var down = false;
			var	$draggedEl, oldCell, previewMove, id;
				
			/* Mouse handlers on the child elements */
			var onMouseUp = function(e) { 
				dropAt(e.pageX, e.pageY); 
			}
			
			var onMouseDown = function(e) {
				down = true;
				$draggedEl = $(this).children();
				oldCell = this;
				move(e.pageX,e.pageY);
				
				if(options.events && options.events.start)
					options.events.start(this);

				return false;
			}
			
			var globalMouseMove = function(e) {
				if(down) {
					move(e.pageX,e.pageY);

					if(inside($(oldCell), e.pageX, e.pageY)) {
						if(previewMove != null) {
							moveTo(previewMove);
							previewMove = null;					
						}
					} else
						$(table).find('td').each(function() {
							if(inside($(this), e.pageX, e.pageY)) {
								if($(previewMove).attr('class') != $(this).children().first().attr('class')) {
									if(previewMove != null) moveTo(previewMove);
									previewMove = $(this).children().first();
									if(previewMove.length > 0)
										moveTo($(previewMove), { pos: {
											top: $(oldCell).offset().top - $(previewMove).parent().offset().top,
											left: $(oldCell).offset().left - $(previewMove).parent().offset().left
										} });
								}
								
								return false;
							}
						});
				}
				
				return false;
			}
			
			// Initialize sortable table
			this.init = function() {
				init();
				$(document).mousemove(globalMouseMove);
			}
			
			// Call this when the table has been updated
			this.refresh = function() {
				init();
			}
			
			this.destroy = function() {
				// Add some required css to each child element in the <td>s
				$(table).find('td').children().each(function() {
					// Remove any old occurences of our added draggable-num class
					$(this).attr('class',$(this).attr('class').replace(/\s*draggable\-\d+/g,''));
				});
				
				// Mouse events
				$(table).find('td').unbind('mouseup',onMouseUp)
				$(table).find('td').unbind('mousedown',onMouseDown);
					
				$(document).unbind('mousemove',globalMouseMove);
			}
			
			function init() {
				id = 1;
				// Add some required css to each child element in the <td>s
				$(table).find('td').children().each(function() {
					// Remove any old occurences of our added draggable-num class
					$(this).attr('class',$(this).attr('class').replace(/\s*draggable\-\d+/g,''));
					$(this).addClass('draggable-' + (id++));
					$(this).css('position','relative');			
				});
				
				// Mouse events
				$(table).find('td').bind('mouseup',onMouseUp);
				$(table).find('td').bind('mousedown',onMouseDown);
			}
			
			
			function switchElement(drag, dropTo) {
				var dragPosDiff = { 
					left: $(drag).children().first().offset().left - $(dropTo).offset().left, 
					top:  $(drag).children().first().offset().top - $(dropTo).offset().top 
				};

				// I love you append(). It moves the DOM Elements so gracefully <3
				$(drag).append($(dropTo).children().first()).children()
					.bind('mouseup',onMouseUp)
					.css('left','0')
					.css('top','0');
					
				$(dropTo).append($(drag).children().first()).children()
					.bind('mouseup',onMouseUp)
					.css('left',dragPosDiff.left + 'px')
					.css('top',dragPosDiff.top + 'px');
				
				moveTo($(dropTo).children().first(), { duration: 100 });
					
				if(options.events && options.events.drop)
					options.events.drop(drag,dropTo);
			}
			
			function move(x,y) {
				$draggedEl.offset({
					top: Math.min($(document).height(), Math.max(0, y - $draggedEl.height()/2)), 
					left: Math.min($(document).width(), Math.max(0, x - $draggedEl.width()/2))
				});
			}
			
			function inside($el, x,y) {
				var off = $el.offset();
				return y >= off.top && x >= off.left && x < off.left + $el.width() && y < off.top + $el.height();
			}
			
			function dropAt(x,y) {
				if(!down) return;
				down = false;
				
				var switched = false;
				
				$(table).find('td').each(function() {
					if($(this).children().first().attr('class') != $(oldCell).children().first().attr('class') && inside($(this), x, y)) {
						switchElement(oldCell, this);
						switched = true;
						return;
					}
				});
				
				if(!switched) {
					if(previewMove) moveTo(previewMove);
					moveTo($draggedEl);
				}
				
				previewMove = null;
			}
			
			function moveTo(elem, opts) {
				if(!opts) opts = {};
				if(!opts.pos) opts.pos = { left: 0, top: 0 };
				if(!opts.duration) opts.duration = 200;
				
				$(elem).animate({ top: opts.pos.top, left: opts.pos.left }, { duration: opts.duration });
			}
		}
	}
	
})( jQuery );