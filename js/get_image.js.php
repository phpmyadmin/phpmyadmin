<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides the functionality for retreiving images
 * which may be actual images or an icon from a sprite
 *
 * @package PhpMyAdmin
 */
chdir('..');

// Send correct type:
header('Content-Type: text/javascript; charset=UTF-8');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Avoid loading the full common.inc.php because this would add many
// non-js-compatible stuff like DOCTYPE
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';

$buffer = PMA\libraries\OutputBuffering::getInstance();
$buffer->start();
register_shutdown_function(
    function () {
        echo PMA\libraries\OutputBuffering::getInstance()->getContents();
    }
);

// Get the data for the sprites, if it's available
if (is_readable($_SESSION['PMA_Theme']->getPath() . '/sprites.lib.php')) {
    include $_SESSION['PMA_Theme']->getPath() . '/sprites.lib.php';
}
$sprites = array();
if (function_exists('PMA_sprites')) {
    $sprites = PMA_sprites();
}
// We only need the keys from the array of sprites data,
// since they contain the (partial) class names
$keys = array();
foreach ($sprites as $key => $value) {
    $keys[] = "'$key'";
}

?>
/**
 * Returns an HTML IMG tag for a particular image from a theme,
 * which may be an actual file or an icon from a sprite
 *
 * @param string image      The name of the file to get
 * @param string alternate  Used to set 'alt' and 'title' attributes of the image
 * @param object attributes An associative array of other attributes
 *
 * @return Object The requested image, this object has two methods:
 *                  .toString()        - Returns the IMG tag for the requested image
 *                  .attr(name)        - Returns a particular attribute of the IMG
 *                                       tag given it's name
 *                  .attr(name, value) - Sets a particular attribute of the IMG
 *                                       tag to the given value
 *                And one property:
 *                  .isSprite          - Whether the image is a sprite or not
 */
function PMA_getImage(image, alternate, attributes) {
    var in_array = function (needle, haystack) {
        for (var i in haystack) {
            if (haystack[i] == needle) {
                return true;
            }
        }
        return false;
    };
    var sprites = [
        <?php echo implode($keys, ",\n        ") , "\n"; ?>
    ];
    // custom image object, it will eventually be returned by this functions
    var retval = {
        data: {
            // this is private
            alt: '',
            title: '',
            src: (typeof PMA_TEST_THEME == 'undefined' ? '' : '../')
                + 'themes/dot.gif'
        },
        isSprite: true,
        attr: function (name, value) {
            if (value == undefined) {
                if (this.data[name] == undefined) {
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
    if (attributes == undefined) {
        attributes = {};
    }
    if (alternate == undefined) {
        alternate = '';
    }
    // set alt
    if (attributes.alt != undefined) {
        retval.attr('alt', attributes.alt);
    } else {
        retval.attr('alt', alternate);
    }
    // set title
    if (attributes.title != undefined) {
        retval.attr('title', attributes.title);
    } else {
        retval.attr('title', alternate);
    }
    // set src
    var klass = image.replace('.gif', '').replace('.png', '');
    if (in_array(klass, sprites)) {
        // it's an icon from a sprite
        retval.attr('class', 'icon ic_' + klass);
    } else {
        // it's an image file
        retval.isSprite = false;
        retval.attr(
            'src',
            "<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>" + image
        );
    }
    // set all other attrubutes
    for (var i in attributes) {
        if (i == 'src') {
            // do not allow to override the 'src' attribute
            continue;
        }

        if (i == 'class') {
            retval.attr(i, retval.attr('class') + ' ' + attributes[i]);
        } else {
            retval.attr(i, attributes[i]);
        }
    }

    return retval;
}
//
