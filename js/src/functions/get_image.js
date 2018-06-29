/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { escapeHtml } from '../utils/Sanitise';

/**
 * Returns an HTML IMG tag for a particular image from a theme,
 * which may be an actual file or an icon from a sprite
 *
 * @access public
 *
 * @param {string} image      The name of the file to get
 *
 * @param {string} alternate  Used to set 'alt' and 'title' attributes of the image
 *
 * @param {object} attributes An associative array of other attributes
 *
 * @return {Object} The requested image, this object has two methods:
 *                  .toString()        - Returns the IMG tag for the requested image
 *                  .attr(name)        - Returns a particular attribute of the IMG
 *                                       tag given it's name
 *                  .attr(name, value) - Sets a particular attribute of the IMG
 *                                       tag to the given value
 */
function PMA_getImage (image, alternate, attributes) {
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
    if (attributes === undefined) {
        attributes = {};
    }
    if (alternate === undefined) {
        alternate = '';
    }
    // set alt
    if (attributes.alt !== undefined) {
        retval.attr('alt', escapeHtml(attributes.alt));
    } else {
        retval.attr('alt', escapeHtml(alternate));
    }
    // set title
    if (attributes.title !== undefined) {
        retval.attr('title', escapeHtml(attributes.title));
    } else {
        retval.attr('title', escapeHtml(alternate));
    }
    // set css classes
    retval.attr('class', 'icon ic_' + image);
    // set all other attrubutes
    for (var i in attributes) {
        if (i === 'src') {
            // do not allow to override the 'src' attribute
            continue;
        }

        retval.attr(i, attributes[i]);
    }

    return retval;
}

/**
 * Module export
 */
export {
    PMA_getImage
};
