/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin-Designer
 */

/**
 *
 */
if (document.all) { // if IE
    document.attachEvent(
        "onreadystatechange", // document load
        function () {
            if (document.readyState == "complete") {
                var el  =  document.getElementById("canvas");
                var outerHTML = el.outerHTML;
                var newEl = document.createElement(outerHTML);
                el.parentNode.replaceChild(newEl, el);
                el = newEl;
                el.getContext = function () {
                    if (this.cont) {
                        return this.cont;
                    }
                    return this.cont = new PMD_2D(this);
                };

                el.style.width = el.attributes.width.nodeValue + "px";
                el.style.height = el.attributes.height.nodeValue + "px";
            }
        }
    );

    //*****************************************************************************************************

    function convert_style(str) {
        var m = [];
        m = str.match(/.*\((\d*),(\d*),(\d*),(\d*)\)/);
        for (var i = 1; i <= 3; i++) {
            m[i] = (m[i] * 1).toString(16).length < 2 ? '0' + (m[i] * 1).toString(16) : (m[i] * 1).toString(16);
        }
        return ['#' + m[1] + m[2] + m[3], 1];
    }
    //------------------------------------------------------------------------------
    function PMD_2D(th) {
        this.element_ = th;
        this.pmd_arr = [];
        this.strokeStyle;
        this.fillStyle;
        this.lineWidth;

        this.closePath = function () {
            this.pmd_arr.push({type: "close"});
        };

        this.clearRect = function () {
            this.element_.innerHTML = "";
            this.pmd_arr = [];
        };

        this.beginPath = function () {
            this.pmd_arr = [];
        };

        this.moveTo = function (aX, aY) {
            this.pmd_arr.push({type: "moveTo", x: aX, y: aY});
        };

        this.lineTo = function (aX, aY) {
            this.pmd_arr.push({type: "lineTo", x: aX, y: aY});
        };

        this.arc = function (aX, aY, aRadius, aStartAngle, aEndAngle, aClockwise) {
            if (!aClockwise) {
                var t = aStartAngle;
                aStartAngle = aEndAngle;
                aEndAngle = t;
            }

            var xStart = aX + (Math.cos(aStartAngle) * aRadius);
            var yStart = aY + (Math.sin(aStartAngle) * aRadius);

            var xEnd = aX + (Math.cos(aEndAngle) * aRadius);
            var yEnd = aY + (Math.sin(aEndAngle) * aRadius);

            this.pmd_arr.push({type: "arc", x: aX, y: aY,
                 radius: aRadius, xStart: xStart, yStart: yStart, xEnd: xEnd, yEnd: yEnd});
        };

        this.rect = function (aX, aY, aW, aH) {
            this.moveTo(aX, aY);
            this.lineTo(aX + aW, aY);
            this.lineTo(aX + aW, aY + aH);
            this.lineTo(aX, aY + aH);
            this.closePath();
        };

        this.fillRect = function (aX, aY, aW, aH) {
            this.beginPath();
            this.moveTo(aX, aY);
            this.lineTo(aX + aW, aY);
            this.lineTo(aX + aW, aY + aH);
            this.lineTo(aX, aY + aH);
            this.closePath();
            this.stroke(true);
        };

        this.stroke = function (aFill) {
            var Str = [];
            var a = convert_style(aFill ? this.fillStyle : this.strokeStyle);
            var color = a[0];

            Str.push('<v:shape',
            ' fillcolor="', color, '"',
            ' filled="', Boolean(aFill), '"',
            ' style="position:absolute;width:10;height:10;"',
            ' coordorigin="0 0" coordsize="10 10"',
            ' stroked="', !aFill, '"',
            ' strokeweight="', this.lineWidth, '"',
            ' strokecolor="', color, '"',
            ' path="');

            for (var i = 0; i < this.pmd_arr.length; i++) {
                var p = this.pmd_arr[i];

                if (p.type == "moveTo") {
                    Str.push(" m ");
                    Str.push(Math.floor(p.x), ",", Math.floor(p.y));
                } else if (p.type == "lineTo") {
                    Str.push(" l ");
                    Str.push(Math.floor(p.x), ",", Math.floor(p.y));
                } else if (p.type == "close") {
                    Str.push(" x ");
                } else if (p.type == "arc") {
                    Str.push(" ar ");
                    Str.push(Math.floor(p.x - p.radius), ",",
                    Math.floor(p.y - p.radius), " ",
                    Math.floor(p.x + p.radius), ",",
                    Math.floor(p.y + p.radius), " ",
                    Math.floor(p.xStart), ",", Math.floor(p.yStart), " ",
                    Math.floor(p.xEnd), ",", Math.floor(p.yEnd));
                }
            }

            Str.push(' ">');
            Str.push("</v:shape>");

            this.element_.insertAdjacentHTML("beforeEnd", Str.join(""));
            this.pmd_arr = [];
        };
    }
}
