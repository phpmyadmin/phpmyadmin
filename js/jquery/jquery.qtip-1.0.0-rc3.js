/*!
 * jquery.qtip. The jQuery tooltip plugin
 *
 * Copyright (c) 2009 Craig Thompson
 * http://craigsworks.com
 *
 * Licensed under MIT
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Launch  : February 2009
 * Version : 1.0.0-rc3
 * Released: Tuesday 12th May, 2009 - 00:00
 * Debug: jquery.qtip.debug.js
 */
(function($)
{
   // Implementation
   $.fn.qtip = function(options, blanket)
   {
      var i, id, interfaces, opts, obj, command, config, api;

      // Return API / Interfaces if requested
      if(typeof options == 'string')
      {
         // Make sure API data exists if requested
         if(typeof $(this).data('qtip') !== 'object')
            $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.NO_TOOLTIP_PRESENT, false);

         // Return requested object
         if(options == 'api')
            return $(this).data('qtip').interfaces[ $(this).data('qtip').current ];
         else if(options == 'interfaces')
            return $(this).data('qtip').interfaces;
      }

      // Validate provided options
      else
      {
         // Set null options object if no options are provided
         if(!options) options = {};

         // Sanitize option data
         if(typeof options.content !== 'object' || (options.content.jquery && options.content.length > 0)) options.content = { text: options.content };
         if(typeof options.content.title !== 'object') options.content.title = { text: options.content.title };
         if(typeof options.position !== 'object') options.position = { corner: options.position };
         if(typeof options.position.corner !== 'object') options.position.corner = { target: options.position.corner, tooltip: options.position.corner };
         if(typeof options.show !== 'object') options.show = { when: options.show };
         if(typeof options.show.when !== 'object') options.show.when = { event: options.show.when };
         if(typeof options.show.effect !== 'object') options.show.effect = { type: options.show.effect };
         if(typeof options.hide !== 'object') options.hide = { when: options.hide };
         if(typeof options.hide.when !== 'object') options.hide.when = { event: options.hide.when };
         if(typeof options.hide.effect !== 'object') options.hide.effect = { type: options.hide.effect };
         if(typeof options.style !== 'object') options.style = { name: options.style };
         options.style = sanitizeStyle(options.style);

         // Build main options object
         opts = $.extend(true, {}, $.fn.qtip.defaults, options);

         // Inherit all style properties into one syle object and include original options
         opts.style = buildStyle.call({ options: opts }, opts.style);
         opts.user = $.extend(true, {}, options);
      };

      // Iterate each matched element
      return $(this).each(function() // Return original elements as per jQuery guidelines
      {
         // Check for API commands
         if(typeof options == 'string')
         {
            command = options.toLowerCase();
            interfaces = $(this).qtip('interfaces');

            // Make sure API data exists$('.qtip').qtip('destroy')
            if(typeof interfaces == 'object')
            {
               // Check if API call is a BLANKET DESTROY command
               if(blanket === true && command == 'destroy')
                  while(interfaces.length > 0) interfaces[interfaces.length-1].destroy();

               // API call is not a BLANKET DESTROY command
               else
               {
                  // Check if supplied command effects this tooltip only (NOT BLANKET)
                  if(blanket !== true) interfaces = [ $(this).qtip('api') ];

                  // Execute command on chosen qTips
                  for(i = 0; i < interfaces.length; i++)
                  {
                     // Destroy command doesn't require tooltip to be rendered
                     if(command == 'destroy') interfaces[i].destroy();

                     // Only call API if tooltip is rendered and it wasn't a destroy call
                     else if(interfaces[i].status.rendered === true)
                     {
                        if(command == 'show') interfaces[i].show();
                        else if(command == 'hide') interfaces[i].hide();
                        else if(command == 'focus') interfaces[i].focus();
                        else if(command == 'disable') interfaces[i].disable(true);
                        else if(command == 'enable') interfaces[i].disable(false);
                     };
                  };
               };
            };
         }

         // No API commands, continue with qTip creation
         else
         {
            // Create unique configuration object
            config = $.extend(true, {}, opts);
            config.hide.effect.length = opts.hide.effect.length;
            config.show.effect.length = opts.show.effect.length;

            // Sanitize target options
            if(config.position.container === false) config.position.container = $(document.body);
            if(config.position.target === false) config.position.target = $(this);
            if(config.show.when.target === false) config.show.when.target = $(this);
            if(config.hide.when.target === false) config.hide.when.target = $(this);

            // Determine tooltip ID (Reuse array slots if possible)
            id = $.fn.qtip.interfaces.length;
            for(i = 0; i < id; i++)
            {
               if(typeof $.fn.qtip.interfaces[i] == 'undefined'){ id = i; break; };
            };

            // Instantiate the tooltip
            obj = new qTip($(this), config, id);

            // Add API references
            $.fn.qtip.interfaces[id] = obj;

            // Check if element already has qTip data assigned
            if(typeof $(this).data('qtip') == 'object')
            {
               // Set new current interface id
               if(typeof $(this).attr('qtip') === 'undefined')
                  $(this).data('qtip').current = $(this).data('qtip').interfaces.length;

               // Push new API interface onto interfaces array
               $(this).data('qtip').interfaces.push(obj);
            }

            // No qTip data is present, create now
            else $(this).data('qtip', { current: 0, interfaces: [obj] });

            // If prerendering is disabled, create tooltip on showEvent
            if(config.content.prerender === false && config.show.when.event !== false && config.show.ready !== true)
            {
               config.show.when.target.bind(config.show.when.event+'.qtip-'+id+'-create', { qtip: id }, function(event)
               {
                  // Retrieve API interface via passed qTip Id
                  api = $.fn.qtip.interfaces[ event.data.qtip ];

                  // Unbind show event and cache mouse coords
                  api.options.show.when.target.unbind(api.options.show.when.event+'.qtip-'+event.data.qtip+'-create');
                  api.cache.mouse = { x: event.pageX, y: event.pageY };

                  // Render tooltip and start the event sequence
                  construct.call( api );
                  api.options.show.when.target.trigger(api.options.show.when.event);
               });
            }

            // Prerendering is enabled, create tooltip now
            else
            {
               // Set mouse position cache to top left of the element
               obj.cache.mouse = {
                  x: config.show.when.target.offset().left,
                  y: config.show.when.target.offset().top
               };

               // Construct the tooltip
               construct.call(obj);
            }
         };
      });
   };

   // Instantiator
   function qTip(target, options, id)
   {
      // Declare this reference
      var self = this;

      // Setup class attributes
      self.id = id;
      self.options = options;
      self.status = {
         animated: false,
         rendered: false,
         disabled: false,
         focused: false
      };
      self.elements = {
         target: target.addClass(self.options.style.classes.target),
         tooltip: null,
         wrapper: null,
         content: null,
         contentWrapper: null,
         title: null,
         button: null,
         tip: null,
         bgiframe: null
      };
      self.cache = {
         mouse: {},
         position: {},
         toggle: 0
      };
      self.timers = {};

      // Define exposed API methods
      $.extend(self, self.options.api,
      {
         show: function(event)
         {
            var returned, solo;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'show');

            // Only continue if element is visible
            if(self.elements.tooltip.css('display') !== 'none') return self;

            // Clear animation queue
            self.elements.tooltip.stop(true, false);

            // Call API method and if return value is false, halt
            returned = self.beforeShow.call(self, event);
            if(returned === false) return self;

            // Define afterShow callback method
            function afterShow()
            {
               // Call API method and focus if it isn't static
               if(self.options.position.type !== 'static') self.focus();
               self.onShow.call(self, event);

               // Prevent antialias from disappearing in IE7 by removing filter attribute
               if($.browser.msie) self.elements.tooltip.get(0).style.removeAttribute('filter');
            };

            // Maintain toggle functionality if enabled
            self.cache.toggle = 1;

            // Update tooltip position if it isn't static
            if(self.options.position.type !== 'static')
               self.updatePosition(event, (self.options.show.effect.length > 0));

            // Hide other tooltips if tooltip is solo
            if(typeof self.options.show.solo == 'object') solo = $(self.options.show.solo);
            else if(self.options.show.solo === true) solo = $('div.qtip').not(self.elements.tooltip);
            if(solo) solo.each(function(){ if($(this).qtip('api').status.rendered === true) $(this).qtip('api').hide(); });

            // Show tooltip
            if(typeof self.options.show.effect.type == 'function')
            {
               self.options.show.effect.type.call(self.elements.tooltip, self.options.show.effect.length);
               self.elements.tooltip.queue(function(){ afterShow(); $(this).dequeue(); });
            }
            else
            {
               switch(self.options.show.effect.type.toLowerCase())
               {
                  case 'fade':
                     self.elements.tooltip.fadeIn(self.options.show.effect.length, afterShow);
                     break;
                  case 'slide':
                     self.elements.tooltip.slideDown(self.options.show.effect.length, function()
                     {
                        afterShow();
                        if(self.options.position.type !== 'static') self.updatePosition(event, true);
                     });
                     break;
                  case 'grow':
                     self.elements.tooltip.show(self.options.show.effect.length, afterShow);
                     break;
                  default:
                     self.elements.tooltip.show(null, afterShow);
                     break;
               };

               // Add active class to tooltip
               self.elements.tooltip.addClass(self.options.style.classes.active);
            };

            // Log event and return
            return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_SHOWN, 'show');
         },

         hide: function(event)
         {
            var returned;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'hide');

            // Only continue if element is visible
            else if(self.elements.tooltip.css('display') === 'none') return self;

            // Stop show timer and animation queue
            clearTimeout(self.timers.show);
            self.elements.tooltip.stop(true, false);

            // Call API method and if return value is false, halt
            returned = self.beforeHide.call(self, event);
            if(returned === false) return self;

            // Define afterHide callback method
            function afterHide(){ self.onHide.call(self, event); };

            // Maintain toggle functionality if enabled
            self.cache.toggle = 0;

            // Hide tooltip
            if(typeof self.options.hide.effect.type == 'function')
            {
               self.options.hide.effect.type.call(self.elements.tooltip, self.options.hide.effect.length);
               self.elements.tooltip.queue(function(){ afterHide(); $(this).dequeue(); });
            }
            else
            {
               switch(self.options.hide.effect.type.toLowerCase())
               {
                  case 'fade':
                     self.elements.tooltip.fadeOut(self.options.hide.effect.length, afterHide);
                     break;
                  case 'slide':
                     self.elements.tooltip.slideUp(self.options.hide.effect.length, afterHide);
                     break;
                  case 'grow':
                     self.elements.tooltip.hide(self.options.hide.effect.length, afterHide);
                     break;
                  default:
                     self.elements.tooltip.hide(null, afterHide);
                     break;
               };

               // Remove active class to tooltip
               self.elements.tooltip.removeClass(self.options.style.classes.active);
            };

            // Log event and return
            return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_HIDDEN, 'hide');
         },

         updatePosition: function(event, animate)
         {
            var i, target, tooltip, coords, mapName, imagePos, newPosition, ieAdjust, ie6Adjust, borderAdjust, mouseAdjust, offset, curPosition, returned

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'updatePosition');

            // If tooltip is static, return
            else if(self.options.position.type == 'static')
               return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.CANNOT_POSITION_STATIC, 'updatePosition');

            // Define property objects
            target = {
               position: { left: 0, top: 0 },
               dimensions: { height: 0, width: 0 },
               corner: self.options.position.corner.target
            };
            tooltip = {
               position: self.getPosition(),
               dimensions: self.getDimensions(),
               corner: self.options.position.corner.tooltip
            };

            // Target is an HTML element
            if(self.options.position.target !== 'mouse')
            {
               // If the HTML element is AREA, calculate position manually
               if(self.options.position.target.get(0).nodeName.toLowerCase() == 'area')
               {
                  // Retrieve coordinates from coords attribute and parse into integers
                  coords = self.options.position.target.attr('coords').split(',');
                  for(i = 0; i < coords.length; i++) coords[i] = parseInt(coords[i]);

                  // Setup target position object
                  mapName = self.options.position.target.parent('map').attr('name');
                  imagePos = $('img[usemap="#'+mapName+'"]:first').offset();
                  target.position = {
                     left: Math.floor(imagePos.left + coords[0]),
                     top: Math.floor(imagePos.top + coords[1])
                  };

                  // Determine width and height of the area
                  switch(self.options.position.target.attr('shape').toLowerCase())
                  {
                     case 'rect':
                        target.dimensions = {
                           width: Math.ceil(Math.abs(coords[2] - coords[0])),
                           height: Math.ceil(Math.abs(coords[3] - coords[1]))
                        };
                        break;

                     case 'circle':
                        target.dimensions = {
                           width: coords[2] + 1,
                           height: coords[2] + 1
                        };
                        break;

                     case 'poly':
                        target.dimensions = {
                           width: coords[0],
                           height: coords[1]
                        };

                        for(i = 0; i < coords.length; i++)
                        {
                           if(i % 2 == 0)
                           {
                              if(coords[i] > target.dimensions.width)
                                 target.dimensions.width = coords[i];
                              if(coords[i] < coords[0])
                                 target.position.left = Math.floor(imagePos.left + coords[i]);
                           }
                           else
                           {
                              if(coords[i] > target.dimensions.height)
                                 target.dimensions.height = coords[i];
                              if(coords[i] < coords[1])
                                 target.position.top = Math.floor(imagePos.top + coords[i]);
                           };
                        };

                        target.dimensions.width = target.dimensions.width - (target.position.left - imagePos.left);
                        target.dimensions.height = target.dimensions.height - (target.position.top - imagePos.top);
                        break;

                     default:
                        return $.fn.qtip.log.error.call(self, 4, $.fn.qtip.constants.INVALID_AREA_SHAPE, 'updatePosition');
                        break;
                  };

                  // Adjust position by 2 pixels (Positioning bug?)
                  target.dimensions.width -= 2; target.dimensions.height -= 2;
               }

               // Target is the document
               else if(self.options.position.target.add(document.body).length === 1)
               {
                  target.position = { left: $(document).scrollLeft(), top: $(document).scrollTop() };
                  target.dimensions = { height: $(window).height(), width: $(window).width() };
               }

               // Target is a regular HTML element, find position normally
               else
               {
                  // Check if the target is another tooltip. If its animated, retrieve position from newPosition data
                  if(typeof self.options.position.target.attr('qtip') !== 'undefined')
                     target.position = self.options.position.target.qtip('api').cache.position;
                  else
                     target.position = self.options.position.target.offset();

                  // Setup dimensions objects
                  target.dimensions = {
                     height: self.options.position.target.outerHeight(),
                     width: self.options.position.target.outerWidth()
                  };
               };

               // Calculate correct target corner position
               newPosition = $.extend({}, target.position);
               if(target.corner.search(/right/i) !== -1)
                  newPosition.left += target.dimensions.width;

               if(target.corner.search(/bottom/i) !== -1)
                  newPosition.top += target.dimensions.height;

               if(target.corner.search(/((top|bottom)Middle)|center/) !== -1)
                  newPosition.left += (target.dimensions.width / 2);

               if(target.corner.search(/((left|right)Middle)|center/) !== -1)
                  newPosition.top += (target.dimensions.height / 2);
            }

            // Mouse is the target, set position to current mouse coordinates
            else
            {
               // Setup target position and dimensions objects
               target.position = newPosition = { left: self.cache.mouse.x, top: self.cache.mouse.y };
               target.dimensions = { height: 1, width: 1 };
            };

            // Calculate correct target corner position
            if(tooltip.corner.search(/right/i) !== -1)
               newPosition.left -= tooltip.dimensions.width;

            if(tooltip.corner.search(/bottom/i) !== -1)
               newPosition.top -= tooltip.dimensions.height;

            if(tooltip.corner.search(/((top|bottom)Middle)|center/) !== -1)
               newPosition.left -= (tooltip.dimensions.width / 2);

            if(tooltip.corner.search(/((left|right)Middle)|center/) !== -1)
               newPosition.top -= (tooltip.dimensions.height / 2);

            // Setup IE adjustment variables (Pixel gap bugs)
            ieAdjust = ($.browser.msie) ? 1 : 0; // And this is why I hate IE...
            ie6Adjust = ($.browser.msie && parseInt($.browser.version.charAt(0)) === 6) ? 1 : 0; // ...and even more so IE6!

            // Adjust for border radius
            if(self.options.style.border.radius > 0)
            {
               if(tooltip.corner.search(/Left/) !== -1)
                  newPosition.left -= self.options.style.border.radius;
               else if(tooltip.corner.search(/Right/) !== -1)
                  newPosition.left += self.options.style.border.radius;

               if(tooltip.corner.search(/Top/) !== -1)
                  newPosition.top -= self.options.style.border.radius;
               else if(tooltip.corner.search(/Bottom/) !== -1)
                  newPosition.top += self.options.style.border.radius;
            };

            // IE only adjustments (Pixel perfect!)
            if(ieAdjust)
            {
               if(tooltip.corner.search(/top/) !== -1)
                  newPosition.top -= ieAdjust
               else if(tooltip.corner.search(/bottom/) !== -1)
                  newPosition.top += ieAdjust

               if(tooltip.corner.search(/left/) !== -1)
                  newPosition.left -= ieAdjust
               else if(tooltip.corner.search(/right/) !== -1)
                  newPosition.left += ieAdjust

               if(tooltip.corner.search(/leftMiddle|rightMiddle/) !== -1)
                  newPosition.top -= 1
            };

            // If screen adjustment is enabled, apply adjustments
            if(self.options.position.adjust.screen === true)
               newPosition = screenAdjust.call(self, newPosition, target, tooltip);

            // If mouse is the target, prevent tooltip appearing directly under the mouse
            if(self.options.position.target === 'mouse' && self.options.position.adjust.mouse === true)
            {
               if(self.options.position.adjust.screen === true && self.elements.tip)
                  mouseAdjust = self.elements.tip.attr('rel');
               else
                  mouseAdjust = self.options.position.corner.tooltip;

               newPosition.left += (mouseAdjust.search(/right/i) !== -1) ? -6 : 6;
               newPosition.top += (mouseAdjust.search(/bottom/i) !== -1) ? -6 : 6;
            }

            // Initiate bgiframe plugin in IE6 if tooltip overlaps a select box or object element
            if(!self.elements.bgiframe && $.browser.msie && parseInt($.browser.version.charAt(0)) == 6)
            {
               $('select, object').each(function()
               {
                  offset = $(this).offset();
                  offset.bottom = offset.top + $(this).height();
                  offset.right = offset.left + $(this).width();

                  if(newPosition.top + tooltip.dimensions.height >= offset.top
                  && newPosition.left + tooltip.dimensions.width >= offset.left)
                     bgiframe.call(self);
               });
            };

            // Add user xy adjustments
            newPosition.left += self.options.position.adjust.x;
            newPosition.top += self.options.position.adjust.y;

            // Set new tooltip position if its moved, animate if enabled
            curPosition = self.getPosition();
            if(newPosition.left != curPosition.left || newPosition.top != curPosition.top)
            {
               // Call API method and if return value is false, halt
               returned = self.beforePositionUpdate.call(self, event);
               if(returned === false) return self;

               // Cache new position
               self.cache.position = newPosition;

               // Check if animation is enabled
               if(animate === true)
               {
                  // Set animated status
                  self.status.animated = true;

                  // Animate and reset animated status on animation end
                  self.elements.tooltip.animate(newPosition, 200, 'swing', function(){ self.status.animated = false });
               }

               // Set new position via CSS
               else self.elements.tooltip.css(newPosition);

               // Call API method and log event if its not a mouse move
               self.onPositionUpdate.call(self, event);
               if(typeof event !== 'undefined' && event.type && event.type !== 'mousemove')
                  $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_POSITION_UPDATED, 'updatePosition');
            };

            return self;
         },

         updateWidth: function(newWidth)
         {
            var hidden;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'updateWidth');

            // Make sure supplied width is a number and if not, return
            else if(newWidth && typeof newWidth !== 'number')
               return $.fn.qtip.log.error.call(self, 2, 'newWidth must be of type number', 'updateWidth');

            // Setup elements which must be hidden during width update
            hidden = self.elements.contentWrapper.siblings().add(self.elements.tip).add(self.elements.button);

            // Calculate the new width if one is not supplied
            if(!newWidth)
            {
               // Explicit width is set
               if(typeof self.options.style.width.value == 'number')
                  newWidth = self.options.style.width.value;

               // No width is set, proceed with auto detection
               else
               {
                  // Set width to auto initally to determine new width and hide other elements
                  self.elements.tooltip.css({ width: 'auto' });
                  hidden.hide();

                  // Set position and zoom to defaults to prevent IE hasLayout bug
                  if($.browser.msie)
                     self.elements.wrapper.add(self.elements.contentWrapper.children()).css({ zoom: 'normal' });

                  // Set the new width
                  newWidth = self.getDimensions().width + 1;

                  // Make sure its within the maximum and minimum width boundries
                  if(!self.options.style.width.value)
                  {
                     if(newWidth > self.options.style.width.max) newWidth = self.options.style.width.max
                     if(newWidth < self.options.style.width.min) newWidth = self.options.style.width.min
                  };
               };
            };

            // Adjust newWidth by 1px if width is odd (IE6 rounding bug fix)
            if(newWidth % 2 !== 0) newWidth -= 1;

            // Set the new calculated width and unhide other elements
            self.elements.tooltip.width(newWidth);
            hidden.show();

            // Set the border width, if enabled
            if(self.options.style.border.radius)
            {
               self.elements.tooltip.find('.qtip-betweenCorners').each(function(i)
               {
                  $(this).width(newWidth - (self.options.style.border.radius * 2));
               })
            };

            // IE only adjustments
            if($.browser.msie)
            {
               // Reset position and zoom to give the wrapper layout (IE hasLayout bug)
               self.elements.wrapper.add(self.elements.contentWrapper.children()).css({ zoom: '1' });

               // Set the new width
               self.elements.wrapper.width(newWidth);

               // Adjust BGIframe height and width if enabled
               if(self.elements.bgiframe) self.elements.bgiframe.width(newWidth).height(self.getDimensions.height);
            };

            // Log event and return
            return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_WIDTH_UPDATED, 'updateWidth');
         },

         updateStyle: function(name)
         {
            var tip, borders, context, corner, coordinates;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'updateStyle');

            // Return if style is not defined or name is not a string
            else if(typeof name !== 'string' || !$.fn.qtip.styles[name])
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.STYLE_NOT_DEFINED, 'updateStyle');

            // Set the new style object
            self.options.style = buildStyle.call(self, $.fn.qtip.styles[name], self.options.user.style);

            // Update initial styles of content and title elements
            self.elements.content.css( jQueryStyle(self.options.style) );
            if(self.options.content.title.text !== false)
               self.elements.title.css( jQueryStyle(self.options.style.title, true) );

            // Update CSS border colour
            self.elements.contentWrapper.css({ borderColor: self.options.style.border.color });

            // Update tip color if enabled
            if(self.options.style.tip.corner !== false)
            {
               if($('<canvas>').get(0).getContext)
               {
                  // Retrieve canvas context and clear
                  tip = self.elements.tooltip.find('.qtip-tip canvas:first');
                  context = tip.get(0).getContext('2d');
                  context.clearRect(0,0,300,300);

                  // Draw new tip
                  corner = tip.parent('div[rel]:first').attr('rel');
                  coordinates = calculateTip(corner, self.options.style.tip.size.width, self.options.style.tip.size.height);
                  drawTip.call(self, tip, coordinates, self.options.style.tip.color || self.options.style.border.color);
               }
               else if($.browser.msie)
               {
                  // Set new fillcolor attribute
                  tip = self.elements.tooltip.find('.qtip-tip [nodeName="shape"]');
                  tip.attr('fillcolor', self.options.style.tip.color || self.options.style.border.color);
               };
            };

            // Update border colors if enabled
            if(self.options.style.border.radius > 0)
            {
               self.elements.tooltip.find('.qtip-betweenCorners').css({ backgroundColor: self.options.style.border.color });

               if($('<canvas>').get(0).getContext)
               {
                  borders = calculateBorders(self.options.style.border.radius)
                  self.elements.tooltip.find('.qtip-wrapper canvas').each(function()
                  {
                     // Retrieve canvas context and clear
                     context = $(this).get(0).getContext('2d');
                     context.clearRect(0,0,300,300);

                     // Draw new border
                     corner = $(this).parent('div[rel]:first').attr('rel')
                     drawBorder.call(self, $(this), borders[corner],
                        self.options.style.border.radius, self.options.style.border.color);
                  });
               }
               else if($.browser.msie)
               {
                  // Set new fillcolor attribute on each border corner
                  self.elements.tooltip.find('.qtip-wrapper [nodeName="arc"]').each(function()
                  {
                     $(this).attr('fillcolor', self.options.style.border.color)
                  });
               };
            };

            // Log event and return
            return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_STYLE_UPDATED, 'updateStyle');
         },

         updateContent: function(content, reposition)
         {
            var parsedContent, images, loadedImages;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'updateContent');

            // Make sure content is defined before update
            else if(!content)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.NO_CONTENT_PROVIDED, 'updateContent');

            // Call API method and set new content if a string is returned
            parsedContent = self.beforeContentUpdate.call(self, content);
            if(typeof parsedContent == 'string') content = parsedContent;
            else if(parsedContent === false) return;

            // Set position and zoom to defaults to prevent IE hasLayout bug
            if($.browser.msie) self.elements.contentWrapper.children().css({ zoom: 'normal' });

            // Append new content if its a DOM array and show it if hidden
            if(content.jquery && content.length > 0)
               content.clone(true).appendTo(self.elements.content).show();

            // Content is a regular string, insert the new content
            else self.elements.content.html(content);

            // Check if images need to be loaded before position is updated to prevent mis-positioning
            images = self.elements.content.find('img[complete=false]');
            if(images.length > 0)
            {
               loadedImages = 0;
               images.each(function(i)
               {
                  $('<img src="'+ $(this).attr('src') +'" />')
                     .load(function(){ if(++loadedImages == images.length) afterLoad(); });
               });
            }
            else afterLoad();

            function afterLoad()
            {
               // Update the tooltip width
               self.updateWidth();

               // If repositioning is enabled, update positions
               if(reposition !== false)
               {
                  // Update position if tooltip isn't static
                  if(self.options.position.type !== 'static')
                     self.updatePosition(self.elements.tooltip.is(':visible'), true);

                  // Reposition the tip if enabled
                  if(self.options.style.tip.corner !== false)
                     positionTip.call(self);
               };
            };

            // Call API method and log event
            self.onContentUpdate.call(self);
            return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_CONTENT_UPDATED, 'loadContent');
         },

         loadContent: function(url, data, method)
         {
            var returned;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'loadContent');

            // Call API method and if return value is false, halt
            returned = self.beforeContentLoad.call(self);
            if(returned === false) return self;

            // Load content using specified request type
            if(method == 'post')
               $.post(url, data, setupContent);
            else
               $.get(url, data, setupContent);

            function setupContent(content)
            {
               // Call API method and log event
               self.onContentLoad.call(self);
               $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_CONTENT_LOADED, 'loadContent');

               // Update the content
               self.updateContent(content);
            };

            return self;
         },

         updateTitle: function(content)
         {
            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'updateTitle');

            // Make sure content is defined before update
            else if(!content)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.NO_CONTENT_PROVIDED, 'updateTitle');

            // Call API method and if return value is false, halt
            returned = self.beforeTitleUpdate.call(self);
            if(returned === false) return self;

            // Set the new content and reappend the button if enabled
            if(self.elements.button) self.elements.button = self.elements.button.clone(true);
            self.elements.title.html(content)
            if(self.elements.button) self.elements.title.prepend(self.elements.button);

            // Call API method and log event
            self.onTitleUpdate.call(self);
            return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_TITLE_UPDATED, 'updateTitle');
         },

         focus: function(event)
         {
            var curIndex, newIndex, elemIndex, returned;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'focus');

            else if(self.options.position.type == 'static')
               return $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.CANNOT_FOCUS_STATIC, 'focus');

            // Set z-index variables
            curIndex = parseInt( self.elements.tooltip.css('z-index') );
            newIndex = 6000 + $('div.qtip[qtip]').length - 1;

            // Only update the z-index if it has changed and tooltip is not already focused
            if(!self.status.focused && curIndex !== newIndex)
            {
               // Call API method and if return value is false, halt
               returned = self.beforeFocus.call(self, event);
               if(returned === false) return self;

               // Loop through all other tooltips
               $('div.qtip[qtip]').not(self.elements.tooltip).each(function()
               {
                  if($(this).qtip('api').status.rendered === true)
                  {
                     elemIndex = parseInt($(this).css('z-index'));

                     // Reduce all other tooltip z-index by 1
                     if(typeof elemIndex == 'number' && elemIndex > -1)
                        $(this).css({ zIndex: parseInt( $(this).css('z-index') ) - 1 });

                     // Set focused status to false
                     $(this).qtip('api').status.focused = false;
                  }
               })

               // Set the new z-index and set focus status to true
               self.elements.tooltip.css({ zIndex: newIndex });
               self.status.focused = true;

               // Call API method and log event
               self.onFocus.call(self, event);
               $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_FOCUSED, 'focus');
            };

            return self;
         },

         disable: function(state)
         {
            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'disable');

            if(state)
            {
               // Tooltip is not already disabled, proceed
               if(!self.status.disabled)
               {
                  // Set the disabled flag and log event
                  self.status.disabled = true;
                  $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_DISABLED, 'disable');
               }

               // Tooltip is already disabled, inform user via log
               else  $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.TOOLTIP_ALREADY_DISABLED, 'disable');
            }
            else
            {
               // Tooltip is not already enabled, proceed
               if(self.status.disabled)
               {
                  // Reassign events, set disable status and log
                  self.status.disabled = false;
                  $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_ENABLED, 'disable');
               }

               // Tooltip is already enabled, inform the user via log
               else $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.TOOLTIP_ALREADY_ENABLED, 'disable');
            };

            return self;
         },

         destroy: function()
         {
            var i, returned, interfaces;

            // Call API method and if return value is false, halt
            returned = self.beforeDestroy.call(self);
            if(returned === false) return self;

            // Check if tooltip is rendered
            if(self.status.rendered)
            {
               // Remove event handlers and remove element
               self.options.show.when.target.unbind('mousemove.qtip', self.updatePosition);
               self.options.show.when.target.unbind('mouseout.qtip', self.hide);
               self.options.show.when.target.unbind(self.options.show.when.event + '.qtip');
               self.options.hide.when.target.unbind(self.options.hide.when.event + '.qtip');
               self.elements.tooltip.unbind(self.options.hide.when.event + '.qtip');
               self.elements.tooltip.unbind('mouseover.qtip', self.focus);
               self.elements.tooltip.remove();
            }

            // Tooltip isn't yet rendered, remove render event
            else self.options.show.when.target.unbind(self.options.show.when.event+'.qtip-create');

            // Check to make sure qTip data is present on target element
            if(typeof self.elements.target.data('qtip') == 'object')
            {
               // Remove API references from interfaces object
               interfaces = self.elements.target.data('qtip').interfaces;
               if(typeof interfaces == 'object' && interfaces.length > 0)
               {
                  // Remove API from interfaces array
                  for(i = 0; i < interfaces.length - 1; i++)
                     if(interfaces[i].id == self.id) interfaces.splice(i, 1)
               }
            }
            delete $.fn.qtip.interfaces[self.id];

            // Set qTip current id to previous tooltips API if available
            if(typeof interfaces == 'object' && interfaces.length > 0)
               self.elements.target.data('qtip').current = interfaces.length -1;
            else
               self.elements.target.removeData('qtip');

            // Call API method and log destroy
            self.onDestroy.call(self);
            $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_DESTROYED, 'destroy');

            return self.elements.target
         },

         getPosition: function()
         {
            var show, offset;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'getPosition');

            show = (self.elements.tooltip.css('display') !== 'none') ? false : true;

            // Show and hide tooltip to make sure coordinates are returned
            if(show) self.elements.tooltip.css({ visiblity: 'hidden' }).show();
            offset = self.elements.tooltip.offset();
            if(show) self.elements.tooltip.css({ visiblity: 'visible' }).hide();

            return offset;
         },

         getDimensions: function()
         {
            var show, dimensions;

            // Make sure tooltip is rendered and if not, return
            if(!self.status.rendered)
               return $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.TOOLTIP_NOT_RENDERED, 'getDimensions');

            show = (!self.elements.tooltip.is(':visible')) ? true : false;

            // Show and hide tooltip to make sure dimensions are returned
            if(show) self.elements.tooltip.css({ visiblity: 'hidden' }).show();
            dimensions = {
               height: self.elements.tooltip.outerHeight(),
               width: self.elements.tooltip.outerWidth()
            };
            if(show) self.elements.tooltip.css({ visiblity: 'visible' }).hide();

            return dimensions;
         }
      });
   };

   // Define priamry construct function
   function construct()
   {
      var self, adjust, content, url, data, method, tempLength;
      self = this;

      // Call API method
      self.beforeRender.call(self);

      // Set rendered status to true
      self.status.rendered = true;

      // Create initial tooltip elements
      self.elements.tooltip =  '<div qtip="'+self.id+'" ' +
         'class="qtip '+(self.options.style.classes.tooltip || self.options.style)+'"' +
         'style="display:none; -moz-border-radius:0; -webkit-border-radius:0; border-radius:0;' +
         'position:'+self.options.position.type+';">' +
         '  <div class="qtip-wrapper" style="position:relative; overflow:hidden; text-align:left;">' +
         '    <div class="qtip-contentWrapper" style="overflow:hidden;">' +
         '       <div class="qtip-content '+self.options.style.classes.content+'"></div>' +
         '</div></div></div>';

      // Append to container element
      self.elements.tooltip = $(self.elements.tooltip);
      self.elements.tooltip.appendTo(self.options.position.container)

      // Setup tooltip qTip data
      self.elements.tooltip.data('qtip', { current: 0, interfaces: [self] });

      // Setup element references
      self.elements.wrapper = self.elements.tooltip.children('div:first');
      self.elements.contentWrapper = self.elements.wrapper.children('div:first').css({ background: self.options.style.background });
      self.elements.content = self.elements.contentWrapper.children('div:first').css( jQueryStyle(self.options.style) );

      // Apply IE hasLayout fix to wrapper and content elements
      if($.browser.msie) self.elements.wrapper.add(self.elements.content).css({ zoom: 1 });

      // Setup tooltip attributes
      if(self.options.hide.when.event == 'unfocus') self.elements.tooltip.attr('unfocus', true);

      // If an explicit width is set, updateWidth prior to setting content to prevent dirty rendering
      if(typeof self.options.style.width.value == 'number') self.updateWidth();

      // Create borders and tips if supported by the browser
      if($('<canvas>').get(0).getContext || $.browser.msie)
      {
         // Create border
         if(self.options.style.border.radius > 0)
            createBorder.call(self);
         else
            self.elements.contentWrapper.css({ border: self.options.style.border.width+'px solid '+self.options.style.border.color  });

         // Create tip if enabled
         if(self.options.style.tip.corner !== false)
            createTip.call(self);
      }

      // Neither canvas or VML is supported, tips and borders cannot be drawn!
      else
      {
         // Set defined border width
         self.elements.contentWrapper.css({ border: self.options.style.border.width+'px solid '+self.options.style.border.color  });

         // Reset border radius and tip
         self.options.style.border.radius = 0;
         self.options.style.tip.corner = false;

         // Inform via log
         $.fn.qtip.log.error.call(self, 2, $.fn.qtip.constants.CANVAS_VML_NOT_SUPPORTED, 'render');
      };

      // Use the provided content string or DOM array
      if((typeof self.options.content.text == 'string' && self.options.content.text.length > 0)
      || (self.options.content.text.jquery && self.options.content.text.length > 0))
         content = self.options.content.text;

      // Use title string for content if present
      else if(typeof self.elements.target.attr('title') == 'string' && self.elements.target.attr('title').length > 0)
      {
         content = self.elements.target.attr('title').replace("\\n", '<br />');
         self.elements.target.attr('title', ''); // Remove title attribute to prevent default tooltip showing
      }

      // No title is present, use alt attribute instead
      else if(typeof self.elements.target.attr('alt') == 'string' && self.elements.target.attr('alt').length > 0)
      {
         content = self.elements.target.attr('alt').replace("\\n", '<br />');
         self.elements.target.attr('alt', ''); // Remove alt attribute to prevent default tooltip showing
      }

      // No valid content was provided, inform via log
      else
      {
         content = ' ';
         $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.NO_VALID_CONTENT, 'render');
      };

      // Set the tooltips content and create title if enabled
      if(self.options.content.title.text !== false) createTitle.call(self);
      self.updateContent(content);

      // Assign events and toggle tooltip with focus
      assignEvents.call(self);
      if(self.options.show.ready === true) self.show();

      // Retrieve ajax content if provided
      if(self.options.content.url !== false)
      {
         url = self.options.content.url;
         data = self.options.content.data;
         method = self.options.content.method || 'get';
         self.loadContent(url, data, method);
      };

      // Call API method and log event
      self.onRender.call(self);
      $.fn.qtip.log.error.call(self, 1, $.fn.qtip.constants.EVENT_RENDERED, 'render');
   };

   // Create borders using canvas and VML
   function createBorder()
   {
      var self, i, width, radius, color, coordinates, containers, size, betweenWidth, betweenCorners, borderTop, borderBottom, borderCoord, sideWidth, vertWidth;
      self = this;

      // Destroy previous border elements, if present
      self.elements.wrapper.find('.qtip-borderBottom, .qtip-borderTop').remove();

      // Setup local variables
      width = self.options.style.border.width;
      radius = self.options.style.border.radius;
      color = self.options.style.border.color || self.options.style.tip.color;

      // Calculate border coordinates
      coordinates = calculateBorders(radius);

      // Create containers for the border shapes
      containers = {};
      for(i in coordinates)
      {
         // Create shape container
         containers[i] = '<div rel="'+i+'" style="'+((i.search(/Left/) !== -1) ? 'left' : 'right') + ':0; ' +
            'position:absolute; height:'+radius+'px; width:'+radius+'px; overflow:hidden; line-height:0.1px; font-size:1px">';

         // Canvas is supported
         if($('<canvas>').get(0).getContext)
            containers[i] += '<canvas height="'+radius+'" width="'+radius+'" style="vertical-align: top"></canvas>';

         // No canvas, but if it's IE use VML
         else if($.browser.msie)
         {
            size = radius * 2 + 3;
            containers[i] += '<v:arc stroked="false" fillcolor="'+color+'" startangle="'+coordinates[i][0]+'" endangle="'+coordinates[i][1]+'" ' +
               'style="width:'+size+'px; height:'+size+'px; margin-top:'+((i.search(/bottom/) !== -1) ? -2 : -1)+'px; ' +
               'margin-left:'+((i.search(/Right/) !== -1) ? coordinates[i][2] - 3.5 : -1)+'px; ' +
               'vertical-align:top; display:inline-block; behavior:url(#default#VML)"></v:arc>';

         };

         containers[i] += '</div>';
      };

      // Create between corners elements
      betweenWidth = self.getDimensions().width - (Math.max(width, radius) * 2);
      betweenCorners = '<div class="qtip-betweenCorners" style="height:'+radius+'px; width:'+betweenWidth+'px; ' +
         'overflow:hidden; background-color:'+color+'; line-height:0.1px; font-size:1px;">';

      // Create top border container
      borderTop = '<div class="qtip-borderTop" dir="ltr" style="height:'+radius+'px; ' +
         'margin-left:'+radius+'px; line-height:0.1px; font-size:1px; padding:0;">' +
         containers['topLeft'] + containers['topRight'] + betweenCorners;
      self.elements.wrapper.prepend(borderTop);

      // Create bottom border container
      borderBottom = '<div class="qtip-borderBottom" dir="ltr" style="height:'+radius+'px; ' +
         'margin-left:'+radius+'px; line-height:0.1px; font-size:1px; padding:0;">' +
         containers['bottomLeft'] + containers['bottomRight'] + betweenCorners;
      self.elements.wrapper.append(borderBottom);

      // Draw the borders if canvas were used (Delayed til after DOM creation)
      if($('<canvas>').get(0).getContext)
      {
         self.elements.wrapper.find('canvas').each(function()
         {
            borderCoord = coordinates[ $(this).parent('[rel]:first').attr('rel') ];
            drawBorder.call(self, $(this), borderCoord, radius, color);
         })
      }

      // Create a phantom VML element (IE won't show the last created VML element otherwise)
      else if($.browser.msie) self.elements.tooltip.append('<v:image style="behavior:url(#default#VML);"></v:image>');

      // Setup contentWrapper border
      sideWidth = Math.max(radius, (radius + (width - radius)) )
      vertWidth = Math.max(width - radius, 0);
      self.elements.contentWrapper.css({
         border: '0px solid ' + color,
         borderWidth: vertWidth + 'px ' + sideWidth + 'px'
      })
   };

   // Border canvas draw method
   function drawBorder(canvas, coordinates, radius, color)
   {
      // Create corner
      var context = canvas.get(0).getContext('2d');
      context.fillStyle = color;
      context.beginPath();
      context.arc(coordinates[0], coordinates[1], radius, 0, Math.PI * 2, false);
      context.fill();
   };

   // Create tip using canvas and VML
   function createTip(corner)
   {
      var self, color, coordinates, coordsize, path;
      self = this;

      // Destroy previous tip, if there is one
      if(self.elements.tip !== null) self.elements.tip.remove();

      // Setup color and corner values
      color = self.options.style.tip.color || self.options.style.border.color;
      if(self.options.style.tip.corner === false) return;
      else if(!corner) corner = self.options.style.tip.corner;

      // Calculate tip coordinates
      coordinates = calculateTip(corner, self.options.style.tip.size.width, self.options.style.tip.size.height);

      // Create tip element
      self.elements.tip =  '<div class="'+self.options.style.classes.tip+'" dir="ltr" rel="'+corner+'" style="position:absolute; ' +
         'height:'+self.options.style.tip.size.height+'px; width:'+self.options.style.tip.size.width+'px; ' +
         'margin:0 auto; line-height:0.1px; font-size:1px;">';

      // Use canvas element if supported
      if($('<canvas>').get(0).getContext)
          self.elements.tip += '<canvas height="'+self.options.style.tip.size.height+'" width="'+self.options.style.tip.size.width+'"></canvas>';

      // Canvas not supported - Use VML (IE)
      else if($.browser.msie)
      {
         // Create coordize and tip path using tip coordinates
         coordsize = self.options.style.tip.size.width + ',' + self.options.style.tip.size.height;
         path = 'm' + coordinates[0][0] + ',' + coordinates[0][1];
         path += ' l' + coordinates[1][0] + ',' + coordinates[1][1];
         path += ' ' + coordinates[2][0] + ',' + coordinates[2][1];
         path += ' xe';

         // Create VML element
         self.elements.tip += '<v:shape fillcolor="'+color+'" stroked="false" filled="true" path="'+path+'" coordsize="'+coordsize+'" ' +
            'style="width:'+self.options.style.tip.size.width+'px; height:'+self.options.style.tip.size.height+'px; ' +
            'line-height:0.1px; display:inline-block; behavior:url(#default#VML); ' +
            'vertical-align:'+((corner.search(/top/) !== -1) ? 'bottom' : 'top')+'"></v:shape>';

         // Create a phantom VML element (IE won't show the last created VML element otherwise)
         self.elements.tip += '<v:image style="behavior:url(#default#VML);"></v:image>';

         // Prevent tooltip appearing above the content (IE z-index bug)
         self.elements.contentWrapper.css('position', 'relative');
      };

      // Attach new tip to tooltip element
      self.elements.tooltip.prepend(self.elements.tip + '</div>');

      // Create element reference and draw the canvas tip (Delayed til after DOM creation)
      self.elements.tip = self.elements.tooltip.find('.'+self.options.style.classes.tip).eq(0);
      if($('<canvas>').get(0).getContext)
         drawTip.call(self, self.elements.tip.find('canvas:first'), coordinates, color);

      // Fix IE small tip bug
      if(corner.search(/top/) !== -1 && $.browser.msie && parseInt($.browser.version.charAt(0)) === 6)
         self.elements.tip.css({ marginTop: -4 });

      // Set the tip position
      positionTip.call(self, corner);
   };

   // Canvas tip drawing method
   function drawTip(canvas, coordinates, color)
   {
      // Setup properties
      var context = canvas.get(0).getContext('2d');
      context.fillStyle = color;

      // Create tip
      context.beginPath();
      context.moveTo(coordinates[0][0], coordinates[0][1]);
      context.lineTo(coordinates[1][0], coordinates[1][1]);
      context.lineTo(coordinates[2][0], coordinates[2][1]);
      context.fill();
   };

   function positionTip(corner)
   {
      var self, ieAdjust, paddingCorner, paddingSize, newMargin;
      self = this;

      // Return if tips are disabled or tip is not yet rendered
      if(self.options.style.tip.corner === false || !self.elements.tip) return;
      if(!corner) corner = self.elements.tip.attr('rel');

      // Setup adjustment variables
      ieAdjust = positionAdjust = ($.browser.msie) ? 1 : 0;

      // Set initial position
      self.elements.tip.css(corner.match(/left|right|top|bottom/)[0], 0);

      // Set position of tip to correct side
      if(corner.search(/top|bottom/) !== -1)
      {
         // Adjustments for IE6 - 0.5px border gap bug
         if($.browser.msie)
         {
            if(parseInt($.browser.version.charAt(0)) === 6)
               positionAdjust = (corner.search(/top/) !== -1) ? -3 : 1;
            else
               positionAdjust = (corner.search(/top/) !== -1) ? 1 : 2;
         };

         if(corner.search(/Middle/) !== -1)
            self.elements.tip.css({ left: '50%', marginLeft: -(self.options.style.tip.size.width / 2) });

         else if(corner.search(/Left/) !== -1)
            self.elements.tip.css({ left: self.options.style.border.radius - ieAdjust });

         else if(corner.search(/Right/) !== -1)
            self.elements.tip.css({ right: self.options.style.border.radius + ieAdjust });

         if(corner.search(/top/) !== -1)
            self.elements.tip.css({ top: -positionAdjust });
         else
            self.elements.tip.css({ bottom: positionAdjust });

      }
      else if(corner.search(/left|right/) !== -1)
      {
         // Adjustments for IE6 - 0.5px border gap bug
         if($.browser.msie)
            positionAdjust = (parseInt($.browser.version.charAt(0)) === 6) ? 1 : ((corner.search(/left/) !== -1) ? 1 : 2);

         if(corner.search(/Middle/) !== -1)
            self.elements.tip.css({ top: '50%', marginTop: -(self.options.style.tip.size.height / 2) });

         else if(corner.search(/Top/) !== -1)
            self.elements.tip.css({ top: self.options.style.border.radius - ieAdjust });

         else if(corner.search(/Bottom/) !== -1)
            self.elements.tip.css({ bottom: self.options.style.border.radius + ieAdjust });

         if(corner.search(/left/) !== -1)
            self.elements.tip.css({ left: -positionAdjust });
         else
            self.elements.tip.css({ right: positionAdjust });
      };

      // Adjust tooltip padding to compensate for tip
      paddingCorner = 'padding-' + corner.match(/left|right|top|bottom/)[0];
      paddingSize = self.options.style.tip.size[ (paddingCorner.search(/left|right/) !== -1) ? 'width' : 'height' ];
      self.elements.tooltip.css('padding', 0);
      self.elements.tooltip.css(paddingCorner, paddingSize);

      // Match content margin to prevent gap bug in IE6 ONLY
      if($.browser.msie && parseInt($.browser.version.charAt(0)) == 6)
      {
         newMargin = parseInt(self.elements.tip.css('margin-top')) || 0;
         newMargin += parseInt(self.elements.content.css('margin-top')) || 0;

         self.elements.tip.css({ marginTop: newMargin });
      };
   };

   // Create title bar for content
   function createTitle()
   {
      var self = this;

      // Destroy previous title element, if present
      if(self.elements.title !== null) self.elements.title.remove();

      // Create title element
      self.elements.title = $('<div class="'+self.options.style.classes.title+'">')
         .css( jQueryStyle(self.options.style.title, true) )
         .css({ zoom: ($.browser.msie) ? 1 : 0 })
         .prependTo(self.elements.contentWrapper);

      // Update title with contents if enabled
      if(self.options.content.title.text) self.updateTitle.call(self, self.options.content.title.text);

      // Create title close buttons if enabled
      if(self.options.content.title.button !== false
      && typeof self.options.content.title.button == 'string')
      {
         self.elements.button = $('<a class="'+self.options.style.classes.button+'" style="float:right; position: relative"></a>')
            .css( jQueryStyle(self.options.style.button, true) )
            .html(self.options.content.title.button)
            .prependTo(self.elements.title)
            .click(function(event){ if(!self.status.disabled) self.hide(event) });
      };
   };

   // Assign hide and show events
   function assignEvents()
   {
      var self, showTarget, hideTarget, inactiveEvents;
      self = this;

      // Setup event target variables
      showTarget = self.options.show.when.target;
      hideTarget = self.options.hide.when.target;

      // Add tooltip as a hideTarget is its fixed
      if(self.options.hide.fixed) hideTarget = hideTarget.add(self.elements.tooltip);

      // Check if the hide event is special 'inactive' type
      if(self.options.hide.when.event == 'inactive')
      {
         // Define events which reset the 'inactive' event handler
         inactiveEvents = ['click', 'dblclick', 'mousedown', 'mouseup', 'mousemove',
         'mouseout', 'mouseenter', 'mouseleave', 'mouseover' ];

         // Define 'inactive' event timer method
         function inactiveMethod(event)
         {
            if(self.status.disabled === true) return;

            //Clear and reset the timer
            clearTimeout(self.timers.inactive);
            self.timers.inactive = setTimeout(function()
            {
               // Unassign 'inactive' events
               $(inactiveEvents).each(function()
               {
                  hideTarget.unbind(this+'.qtip-inactive');
                  self.elements.content.unbind(this+'.qtip-inactive');
               });

               // Hide the tooltip
               self.hide(event);
            }
            , self.options.hide.delay);
         };
      }

      // Check if the tooltip is 'fixed'
      else if(self.options.hide.fixed === true)
      {
         self.elements.tooltip.bind('mouseover.qtip', function()
         {
            if(self.status.disabled === true) return;

            // Reset the hide timer
            clearTimeout(self.timers.hide);
         });
      };

      // Define show event method
      function showMethod(event)
      {
         if(self.status.disabled === true) return;

         // If set, hide tooltip when inactive for delay period
         if(self.options.hide.when.event == 'inactive')
         {
            // Assign each reset event
            $(inactiveEvents).each(function()
            {
               hideTarget.bind(this+'.qtip-inactive', inactiveMethod);
               self.elements.content.bind(this+'.qtip-inactive', inactiveMethod);
            });

            // Start the inactive timer
            inactiveMethod();
         };

         // Clear hide timers
         clearTimeout(self.timers.show);
         clearTimeout(self.timers.hide);

         // Start show timer
         self.timers.show = setTimeout(function(){ self.show(event); }, self.options.show.delay);
      };

      // Define hide event method
      function hideMethod(event)
      {
         if(self.status.disabled === true) return;

         // Prevent hiding if tooltip is fixed and event target is the tooltip
         if(self.options.hide.fixed === true
         && self.options.hide.when.event.search(/mouse(out|leave)/i) !== -1
         && $(event.relatedTarget).parents('div.qtip[qtip]').length > 0)
         {
            // Prevent default and popagation
            event.stopPropagation();
            event.preventDefault();

            // Reset the hide timer
            clearTimeout(self.timers.hide);
            return false;
         };

         // Clear timers and stop animation queue
         clearTimeout(self.timers.show);
         clearTimeout(self.timers.hide);
         self.elements.tooltip.stop(true, true);

         // If tooltip has displayed, start hide timer
         self.timers.hide = setTimeout(function(){ self.hide(event); }, self.options.hide.delay);
      };

      // Both events and targets are identical, apply events using a toggle
      if((self.options.show.when.target.add(self.options.hide.when.target).length === 1
      && self.options.show.when.event == self.options.hide.when.event
      && self.options.hide.when.event !== 'inactive')
      || self.options.hide.when.event == 'unfocus')
      {
         self.cache.toggle = 0;
         // Use a toggle to prevent hide/show conflicts
         showTarget.bind(self.options.show.when.event + '.qtip', function(event)
         {
            if(self.cache.toggle == 0) showMethod(event);
            else hideMethod(event);
         });
      }

      // Events are not identical, bind normally
      else
      {
         showTarget.bind(self.options.show.when.event + '.qtip', showMethod);

         // If the hide event is not 'inactive', bind the hide method
         if(self.options.hide.when.event !== 'inactive')
            hideTarget.bind(self.options.hide.when.event + '.qtip', hideMethod);
      };

      // Focus the tooltip on mouseover
      if(self.options.position.type.search(/(fixed|absolute)/) !== -1)
         self.elements.tooltip.bind('mouseover.qtip', self.focus);

      // If mouse is the target, update tooltip position on mousemove
      if(self.options.position.target === 'mouse' && self.options.position.type !== 'static')
      {
         showTarget.bind('mousemove.qtip', function(event)
         {
            // Set the new mouse positions if adjustment is enabled
            self.cache.mouse = { x: event.pageX, y: event.pageY };

            // Update the tooltip position only if the tooltip is visible and adjustment is enabled
            if(self.status.disabled === false
            && self.options.position.adjust.mouse === true
            && self.options.position.type !== 'static'
            && self.elements.tooltip.css('display') !== 'none')
               self.updatePosition(event);
         });
      };
   };

   // Screen position adjustment
   function screenAdjust(position, target, tooltip)
   {
      var self, adjustedPosition, adjust, newCorner, overflow, corner;
      self = this;

      // Setup corner and adjustment variable
      if(tooltip.corner == 'center') return target.position // TODO: 'center' corner adjustment
      adjustedPosition = $.extend({}, position);
      newCorner = { x: false, y: false };

      // Define overflow properties
      overflow = {
         left: (adjustedPosition.left < $.fn.qtip.cache.screen.scroll.left),
         right: (adjustedPosition.left + tooltip.dimensions.width + 2 >= $.fn.qtip.cache.screen.width + $.fn.qtip.cache.screen.scroll.left),
         top: (adjustedPosition.top < $.fn.qtip.cache.screen.scroll.top),
         bottom: (adjustedPosition.top + tooltip.dimensions.height + 2 >= $.fn.qtip.cache.screen.height + $.fn.qtip.cache.screen.scroll.top)
      };

      // Determine new positioning properties
      adjust = {
         left: (overflow.left && (tooltip.corner.search(/right/i) != -1 || (tooltip.corner.search(/right/i) == -1 && !overflow.right))),
         right: (overflow.right && (tooltip.corner.search(/left/i) != -1 || (tooltip.corner.search(/left/i) == -1 && !overflow.left))),
         top: (overflow.top && tooltip.corner.search(/top/i) == -1),
         bottom: (overflow.bottom && tooltip.corner.search(/bottom/i) == -1)
      };

      // Tooltip overflows off the left side of the screen
      if(adjust.left)
      {
         if(self.options.position.target !== 'mouse')
            adjustedPosition.left = target.position.left + target.dimensions.width;
         else
            adjustedPosition.left = self.cache.mouse.x

         newCorner.x = 'Left';
      }

      // Tooltip overflows off the right side of the screen
      else if(adjust.right)
      {
         if(self.options.position.target !== 'mouse')
            adjustedPosition.left = target.position.left - tooltip.dimensions.width;
         else
            adjustedPosition.left = self.cache.mouse.x - tooltip.dimensions.width;

         newCorner.x = 'Right';
      };

      // Tooltip overflows off the top of the screen
      if(adjust.top)
      {
         if(self.options.position.target !== 'mouse')
            adjustedPosition.top = target.position.top + target.dimensions.height;
         else
            adjustedPosition.top = self.cache.mouse.y

         newCorner.y = 'top';
      }

      // Tooltip overflows off the bottom of the screen
      else if(adjust.bottom)
      {
         if(self.options.position.target !== 'mouse')
            adjustedPosition.top = target.position.top - tooltip.dimensions.height;
         else
            adjustedPosition.top = self.cache.mouse.y - tooltip.dimensions.height;

         newCorner.y = 'bottom';
      };

      // Don't adjust if resulting position is negative
      if(adjustedPosition.left < 0)
      {
         adjustedPosition.left = position.left;
         newCorner.x = false;
      };
      if(adjustedPosition.top < 0)
      {
         adjustedPosition.top = position.top;
         newCorner.y = false;
      };

      // Change tip corner if positioning has changed and tips are enabled
      if(self.options.style.tip.corner !== false)
      {
         // Determine new corner properties
         adjustedPosition.corner = new String(tooltip.corner);
         if(newCorner.x !== false) adjustedPosition.corner = adjustedPosition.corner.replace(/Left|Right|Middle/, newCorner.x);
         if(newCorner.y !== false) adjustedPosition.corner = adjustedPosition.corner.replace(/top|bottom/, newCorner.y);

         // Adjust tip if position has changed and tips are enabled
         if(adjustedPosition.corner !== self.elements.tip.attr('rel'))
            createTip.call(self, adjustedPosition.corner);
      };

      return adjustedPosition;
   };

   // Build a jQuery style object from supplied style object
   function jQueryStyle(style, sub)
   {
      var styleObj, i;

      styleObj = $.extend(true, {}, style);
      for(i in styleObj)
      {
         if(sub === true && i.search(/(tip|classes)/i) !== -1)
            delete styleObj[i];
         else if(!sub && i.search(/(width|border|tip|title|classes|user)/i) !== -1)
            delete styleObj[i];
      };

      return styleObj;
   };

   // Sanitize styles
   function sanitizeStyle(style)
   {
      if(typeof style.tip !== 'object') style.tip = { corner: style.tip };
      if(typeof style.tip.size !== 'object') style.tip.size = { width: style.tip.size, height: style.tip.size };
      if(typeof style.border !== 'object') style.border = { width: style.border };
      if(typeof style.width !== 'object') style.width = { value: style.width };
      if(typeof style.width.max == 'string') style.width.max = parseInt(style.width.max.replace(/([0-9]+)/i, "$1"));
      if(typeof style.width.min == 'string') style.width.min = parseInt(style.width.min.replace(/([0-9]+)/i, "$1"));

      // Convert deprecated x and y tip values to width/height
      if(typeof style.tip.size.x == 'number')
      {
         style.tip.size.width = style.tip.size.x;
         delete style.tip.size.x;
      };
      if(typeof style.tip.size.y == 'number')
      {
         style.tip.size.height = style.tip.size.y;
         delete style.tip.size.y;
      };

      return style;
   };

   // Build styles recursively with inheritance
   function buildStyle()
   {
      var self, i, styleArray, styleExtend, finalStyle, ieAdjust;
      self = this;

      // Build style options from supplied arguments
      styleArray = [true, {}];
      for(i = 0; i < arguments.length; i++)
         styleArray.push(arguments[i]);
      styleExtend = [ $.extend.apply($, styleArray) ];

      // Loop through each named style inheritance
      while(typeof styleExtend[0].name == 'string')
      {
         // Sanitize style data and append to extend array
         styleExtend.unshift( sanitizeStyle($.fn.qtip.styles[ styleExtend[0].name ]) );
      };

      // Make sure resulting tooltip className represents final style
      styleExtend.unshift(true, {classes:{ tooltip: 'qtip-' + (arguments[0].name || 'defaults') }}, $.fn.qtip.styles.defaults);

      // Extend into a single style object
      finalStyle = $.extend.apply($, styleExtend);

      // Adjust tip size if needed (IE 1px adjustment bug fix)
      ieAdjust = ($.browser.msie) ? 1 : 0;
      finalStyle.tip.size.width += ieAdjust;
      finalStyle.tip.size.height += ieAdjust;

      // Force even numbers for pixel precision
      if(finalStyle.tip.size.width % 2 > 0) finalStyle.tip.size.width += 1;
      if(finalStyle.tip.size.height % 2 > 0) finalStyle.tip.size.height += 1;

      // Sanitize final styles tip corner value
      if(finalStyle.tip.corner === true)
         finalStyle.tip.corner = (self.options.position.corner.tooltip === 'center') ? false : self.options.position.corner.tooltip;

      return finalStyle;
   };

   // Tip coordinates calculator
   function calculateTip(corner, width, height)
   {
      // Define tip coordinates in terms of height and width values
      var tips = {
         bottomRight:   [[0,0],              [width,height],      [width,0]],
         bottomLeft:    [[0,0],              [width,0],           [0,height]],
         topRight:      [[0,height],         [width,0],           [width,height]],
         topLeft:       [[0,0],              [0,height],          [width,height]],
         topMiddle:     [[0,height],         [width / 2,0],       [width,height]],
         bottomMiddle:  [[0,0],              [width,0],           [width / 2,height]],
         rightMiddle:   [[0,0],              [width,height / 2],  [0,height]],
         leftMiddle:    [[width,0],          [width,height],      [0,height / 2]]
      };
      tips.leftTop = tips.bottomRight;
      tips.rightTop = tips.bottomLeft;
      tips.leftBottom = tips.topRight;
      tips.rightBottom = tips.topLeft;

      return tips[corner];
   };

   // Border coordinates calculator
   function calculateBorders(radius)
   {
      var borders;

      // Use canvas element if supported
      if($('<canvas>').get(0).getContext)
      {
         borders = {
            topLeft: [radius,radius], topRight: [0,radius],
            bottomLeft: [radius,0], bottomRight: [0,0]
         };
      }

      // Canvas not supported - Use VML (IE)
      else if($.browser.msie)
      {
         borders = {
            topLeft: [-90,90,0], topRight: [-90,90,-radius],
            bottomLeft: [90,270,0], bottomRight: [90, 270,-radius]
         };
      };

      return borders;
   };

   // BGIFRAME JQUERY PLUGIN ADAPTION
   //   Special thanks to Brandon Aaron for this plugin
   //   http://plugins.jquery.com/project/bgiframe
   function bgiframe()
   {
      var self, html, dimensions;
      self = this;
      dimensions = self.getDimensions();

      // Setup iframe HTML string
      html = '<iframe class="qtip-bgiframe" frameborder="0" tabindex="-1" src="javascript:false" '+
         'style="display:block; position:absolute; z-index:-1; filter:alpha(opacity=\'0\'); border: 1px solid red; ' +
         'height:'+dimensions.height+'px; width:'+dimensions.width+'px" />';

      // Append the new HTML and setup element reference
      self.elements.bgiframe = self.elements.wrapper.prepend(html).children('.qtip-bgiframe:first');
   };

   // Assign cache and event initialisation on document load
   $(document).ready(function()
   {
      // Setup library cache with window scroll and dimensions of document
      $.fn.qtip.cache = {
         screen: {
            scroll: { left: $(window).scrollLeft(), top: $(window).scrollTop() },
            width: $(window).width(),
            height: $(window).height()
         }
      };

      // Adjust positions of the tooltips on window resize or scroll if enabled
      var adjustTimer;
      $(window).bind('resize scroll', function(event)
      {
         clearTimeout(adjustTimer);
         adjustTimer = setTimeout(function()
         {
            // Readjust cached screen values
            if(event.type === 'scroll')
               $.fn.qtip.cache.screen.scroll = { left: $(window).scrollLeft(), top: $(window).scrollTop() };
            else
            {
               $.fn.qtip.cache.screen.width = $(window).width();
               $.fn.qtip.cache.screen.height = $(window).height();
            };

            for(i = 0; i < $.fn.qtip.interfaces.length; i++)
            {
               // Access current elements API
               var api = $.fn.qtip.interfaces[i];

               // Update position if resize or scroll adjustments are enabled
               if(api.status.rendered === true
               && (api.options.position.type !== 'static'
               || api.options.position.adjust.scroll && event.type === 'scroll'
               || api.options.position.adjust.resize && event.type === 'resize'))
               {
                  // Queue the animation so positions are updated correctly
                  api.updatePosition(event, true);
               }
            };
         }
         , 100);
      })

      // Hide unfocus toolipts on document mousedown
      $(document).bind('mousedown.qtip', function(event)
      {
         if($(event.target).parents('div.qtip').length === 0)
         {
            $('.qtip[unfocus]').each(function()
            {
               var api = $(this).qtip("api");

               // Only hide if its visible and not the tooltips target
               if($(this).is(':visible') && !api.status.disabled
               && $(event.target).add(api.elements.target).length > 1)
                  api.hide(event);
            })
         };
      })
   });

   // Define qTip API interfaces array
   $.fn.qtip.interfaces = []

   // Define log and constant place holders
   $.fn.qtip.log = { error: function(){ return this; } };
   $.fn.qtip.constants = {};

   // Define configuration defaults
   $.fn.qtip.defaults = {
      // Content
      content: {
         prerender: false,
         text: false,
         url: false,
         data: null,
         title: {
            text: false,
            button: false
         }
      },
      // Position
      position: {
         target: false,
         corner: {
            target: 'bottomRight',
            tooltip: 'topLeft'
         },
         adjust: {
            x: 0, y: 0,
            mouse: true,
            screen: false,
            scroll: true,
            resize: true
         },
         type: 'absolute',
         container: false
      },
      // Effects
      show: {
         when: {
            target: false,
            event: 'mouseover'
         },
         effect: {
            type: 'fade',
            length: 100
         },
         delay: 140,
         solo: false,
         ready: false
      },
      hide: {
         when: {
            target: false,
            event: 'mouseout'
         },
         effect: {
            type: 'fade',
            length: 100
         },
         delay: 0,
         fixed: false
      },
      // Callbacks
      api: {
         beforeRender: function(){},
         onRender: function(){},
         beforePositionUpdate: function(){},
         onPositionUpdate: function(){},
         beforeShow: function(){},
         onShow: function(){},
         beforeHide: function(){},
         onHide: function(){},
         beforeContentUpdate: function(){},
         onContentUpdate: function(){},
         beforeContentLoad: function(){},
         onContentLoad: function(){},
         beforeTitleUpdate: function(){},
         onTitleUpdate: function(){},
         beforeDestroy: function(){},
         onDestroy: function(){},
         beforeFocus: function(){},
         onFocus: function(){}
      }
   };

   $.fn.qtip.styles = {
      defaults: {
         background: 'white',
         color: '#111',
         overflow: 'hidden',
         textAlign: 'left',
         width: {
            min: 0,
            max: 250
         },
         padding: '5px 9px',
         border: {
            width: 1,
            radius: 0,
            color: '#d3d3d3'
         },
         tip: {
            corner: false,
            color: false,
            size: { width: 13, height: 13 },
            opacity: 1
         },
         title: {
            background: '#e1e1e1',
            fontWeight: 'bold',
            padding: '7px 12px'
         },
         button: {
            cursor: 'pointer'
         },
         classes: {
            target: '',
            tip: 'qtip-tip',
            title: 'qtip-title',
            button: 'qtip-button',
            content: 'qtip-content',
            active: 'qtip-active'
         }
      },
      cream: {
         border: {
            width: 3,
            radius: 0,
            color: '#F9E98E'
         },
         title: {
            background: '#F0DE7D',
            color: '#A27D35'
         },
         background: '#FBF7AA',
         color: '#A27D35',

         classes: { tooltip: 'qtip-cream' }
      },
      light: {
         border: {
            width: 3,
            radius: 0,
            color: '#E2E2E2'
         },
         title: {
            background: '#f1f1f1',
            color: '#454545'
         },
         background: 'white',
         color: '#454545',

         classes: { tooltip: 'qtip-light' }
      },
      dark: {
         border: {
            width: 3,
            radius: 0,
            color: '#303030'
         },
         title: {
            background: '#404040',
            color: '#f3f3f3'
         },
         background: '#505050',
         color: '#f3f3f3',

         classes: { tooltip: 'qtip-dark' }
      },
      red: {
         border: {
            width: 3,
            radius: 0,
            color: '#CE6F6F'
         },
         title: {
            background: '#f28279',
            color: '#9C2F2F'
         },
         background: '#F79992',
         color: '#9C2F2F',

         classes: { tooltip: 'qtip-red' }
      },
      green: {
         border: {
            width: 3,
            radius: 0,
            color: '#A9DB66'
         },
         title: {
            background: '#b9db8c',
            color: '#58792E'
         },
         background: '#CDE6AC',
         color: '#58792E',

         classes: { tooltip: 'qtip-green' }
      },
      blue: {
         border: {
            width: 3,
            radius: 0,
            color: '#ADD9ED'
         },
         title: {
            background: '#D0E9F5',
            color: '#5E99BD'
         },
         background: '#E5F6FE',
         color: '#4D9FBF',

         classes: { tooltip: 'qtip-blue' }
      }
   };
})(jQuery);