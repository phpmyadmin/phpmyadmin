 /*
     pMap - a JavaScript to add image map support to pChart graphs!
     Copyright (C) 2008 Jean-Damien POGOLOTTI
     Copyright (C) 2010 Martynas Mickevicius
     Version  1.1 last updated on 08/20/08
     Version  1.2 last updated on 07/22/10

     http://pchart.sourceforge.net

     This program is free software: you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published by
     the Free Software Foundation, either version 1,2,3 of the License, or
     (at your option) any later version.

     This program is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

var imageMap = {
    'mouseMoved': function(event, cont) {
        // get mouse coordinated relative to image
        mouseX = event.pageX - cont.offsetLeft;
        mouseY = event.pageY - cont.offsetTop;

        //console.log("X: " + mouseX + ", Y: " + mouseY);

        /* Check if we are flying over a map zone
        * Lets use the following method to check if a given
        * point is in any convex polygon.
        * http://www.programmingforums.org/post168124-3.html
        */
        found = false;
        for (key in this.imageMap)
        {
            values = key.split("--");
            seriesName = values[0];
            seriesValue = values[1];

            signSum = 0;
            for (i = 0; i <= this.imageMap[key].length - 1; i++)
            {
                if (i == this.imageMap[key].length - 1)
                {
                    index1 = i;
                    index2 = 0;
                }
                else
                {
                    index1 = i;
                    index2 = i+1;
                }
                result = this.getDeterminant(
                    this.imageMap[key][index1][0],
                    this.imageMap[key][index1][1],
                    this.imageMap[key][index2][0],
                    this.imageMap[key][index2][1],
                    mouseX,
                    mouseY
                );
                if (result > 0) { signSum += 1; } else { signSum += -1; }
            }
            
            if (Math.abs(signSum) == this.imageMap[key].length)
            {
                found = true;
                if (this.currentKey != key)
                {
                    this.tooltip.show();
                    this.tooltip.title(seriesName);
                    this.tooltip.text(seriesValue);
                    this.currentKey = key;
                }
                this.tooltip.move(mouseX + 20, mouseY + 20);
            }
        }
        if (!found && this.currentKey != -1 )
        {
            this.tooltip.hide();
            this.currentKey = -1;
        }
    },

    'getDeterminant': function (X1, Y1, X2, Y2, X3, Y3) {
        return (X2*Y3 - X3*Y2) - (X1*Y3 - X3*Y1) + (X1*Y2 - X2*Y1);
    },

    'loadImageMap': function(map) {
        this.imageMap = JSON.parse(map);
        for (key in this.imageMap)
        {
            // FIXME
            // without this loop image map does not work
            // on IE8 in the status page
        }
    },

    'init': function() {
        this.tooltip.init();

        $("div#chart").bind('mousemove',function(e) {
            imageMap.mouseMoved(e, this);
        });

        this.tooltip.attach("div#chart");

        this.currentKey = -1;
    },

    'tooltip': {
        'init': function () {
            this.el = $('<div></div>');
            this.el.css('position', 'absolute');
            this.el.css('font-family', 'tahoma');
            this.el.css('background-color', '#373737');
            this.el.css('color', '#BEBEBE');
            this.el.css('padding', '3px');

            var title = $('<p></p>');
            title.attr('id', 'title');
            title.css('margin', '0px');
            title.css('padding', '3px');
            title.css('background-color', '#606060');
            title.css('text-align', 'center');
            title.html('Title');
            this.el.append(title);

            var text = $('<p></p>');
            text.attr('id', 'text');
            text.css('margin', '0');
            text.html('Text');
            this.el.append(text);
            
            this.hide();
        },

        'attach': function (element) {
            $(element).prepend(this.el);
        },

        'move': function (x, y) {
            this.el.css('margin-left', x);
            this.el.css('margin-top', y);
        },

        'hide': function () {
            this.el.css('display', 'none');
        },

        'show': function () {
            this.el.css('display', 'block');
        },

        'title': function (title) {
            this.el.find("p#title").html(title);
        },

        'text': function (text) {
            this.el.find("p#text").html(text.replace(/;/g, "<br />"));
        }
    }
};

$(document).ready(function() {
    imageMap.init();
});
