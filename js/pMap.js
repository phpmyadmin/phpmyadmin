/**
 * Holds the definition and the creation of the imageMap object
 * @author Martynas Mickevicius <mmartynas@gmail.com>
 * @package phpMyAdmin
 */

/**
 * responsible for showing tooltips above the image chart
 */
var imageMap = {
    'mouseMoved': function(event, cont) {
        // return if no imageMap set
        // this can happen if server has no json
        if (!this.imageMap) {
            return;
        }
        
        // get mouse coordinated relative to image
        var mouseX = event.pageX - cont.offsetLeft;
        var mouseY = event.pageY - cont.offsetTop;

        //console.log("X: " + mouseX + ", Y: " + mouseY);

        /* Check if we are flying over a map zone
        * Lets use the following method to check if a given
        * point is in any convex polygon.
        * http://www.programmingforums.org/post168124-3.html
        */
        var found = false;
        for (var key = 0; key < this.imageMap.length; key++)
        {
            var seriesName = this.imageMap[key]['n'];
            var seriesValue = this.imageMap[key]['v'];

            var signSum = 0;
            for (var i = 0; i < this.imageMap[key]['p'].length; i++)
            {
                var index1;
                var index2;

                if (i == this.imageMap[key]['p'].length - 1)
                {
                    index1 = i;
                    index2 = 0;
                }
                else
                {
                    index1 = i;
                    index2 = i+1;
                }
                var result = this.getDeterminant(
                    this.imageMap[key]['p'][index1][0],
                    this.imageMap[key]['p'][index1][1],
                    this.imageMap[key]['p'][index2][0],
                    this.imageMap[key]['p'][index2][1],
                    mouseX,
                    mouseY
                );
                if (result > 0) { signSum += 1; } else { signSum += -1; }
            }
            
            if (Math.abs(signSum) == this.imageMap[key]['p'].length)
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
