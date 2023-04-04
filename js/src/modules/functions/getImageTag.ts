import { escapeHtml } from './escape.ts';

/**
 * Returns an HTML IMG tag for a particular image from a theme,
 * which may be an actual file or an icon from a sprite
 *
 * @param {string} image      The name of the file to get
 * @param {string} alternate  Used to set 'alt' and 'title' attributes of the image
 * @param {object} attributes An associative array of other attributes
 *
 * @return {object} The requested image, this object has two methods:
 *                  .toString()        - Returns the IMG tag for the requested image
 *                  .attr(name)        - Returns a particular attribute of the IMG
 *                                       tag given it's name
 *                  .attr(name, value) - Sets a particular attribute of the IMG
 *                                       tag to the given value
 */
export default function getImageTag (image, alternate = undefined, attributes = undefined) {
    var alt = alternate;
    var attr = attributes;
    // custom image object, it will eventually be returned by this functions
    var retval = {
        data: {
            // this is private
            alt: '',
            title: '',
            src: 'themes/dot.gif',
        },
        attr: function (name, value) {
            if (value === undefined) {
                if (this.data[name] === undefined) {
                    return '';
                } else {
                    return this.data[name];
                }
            } else {
                this.data[name] = value;
            }
        },
        toString: function () {
            var retval = '<' + 'img';
            for (var i in this.data) {
                retval += ' ' + i + '="' + this.data[i] + '"';
            }

            retval += ' /' + '>';

            return retval;
        }
    };
    // initialise missing parameters
    if (attr === undefined) {
        attr = {};
    }

    if (alt === undefined) {
        alt = '';
    }

    // set alt
    if (attr.alt !== undefined) {
        retval.attr('alt', escapeHtml(attr.alt));
    } else {
        retval.attr('alt', escapeHtml(alt));
    }

    // set title
    if (attr.title !== undefined) {
        retval.attr('title', escapeHtml(attr.title));
    } else {
        retval.attr('title', escapeHtml(alt));
    }

    // set css classes
    retval.attr('class', 'icon ic_' + image);
    // set all other attributes
    for (var i in attr) {
        if (i === 'src') {
            // do not allow to override the 'src' attribute
            continue;
        }

        retval.attr(i, attr[i]);
    }

    return retval;
}
