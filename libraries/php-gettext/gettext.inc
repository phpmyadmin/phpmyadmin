<?php
/*
   Copyright (c) 2005 Steven Armstrong <sa at c-area dot ch>
   Copyright (c) 2009 Danilo Segan <danilo@kvota.net>

   Drop in replacement for native gettext.

   This file is part of PHP-gettext.

   PHP-gettext is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-gettext is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-gettext; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/
/*
LC_CTYPE        0
LC_NUMERIC      1
LC_TIME         2
LC_COLLATE      3
LC_MONETARY     4
LC_MESSAGES     5
LC_ALL          6
*/


// LC_MESSAGES is not available if php-gettext is not loaded
// while the other constants are already available from session extension.
if (!defined('LC_MESSAGES')) {
  define('LC_MESSAGES',	5);
}

require('streams.php');
require('gettext.php');


// Variables

global $text_domains, $default_domain, $LC_CATEGORIES, $EMULATEGETTEXT, $CURRENTLOCALE;
$text_domains = array();
$default_domain = 'messages';
$LC_CATEGORIES = array('LC_CTYPE', 'LC_NUMERIC', 'LC_TIME', 'LC_COLLATE', 'LC_MONETARY', 'LC_MESSAGES', 'LC_ALL');
$EMULATEGETTEXT = 0;
$CURRENTLOCALE = '';

/* Class to hold a single domain included in $text_domains. */
class domain {
  var $l10n;
  var $path;
  var $codeset;
}

// Utility functions

/**
 * Utility function to get a StreamReader for the given text domain.
 */
function _get_reader($domain=null, $category=5, $enable_cache=true) {
    global $text_domains, $default_domain, $LC_CATEGORIES;
    if (!isset($domain)) $domain = $default_domain;
    if (!isset($text_domains[$domain]->l10n)) {
        // get the current locale
        $locale = _setlocale(LC_MESSAGES, 0);
        $bound_path = isset($text_domains[$domain]->path) ?
          $text_domains[$domain]->path : './';
        $subpath = $LC_CATEGORIES[$category] ."/$domain.mo";
        /* Figure out all possible locale names and start with the most
           specific ones.  I.e. for sr_CS.UTF-8@latin, look through all of
           sr_CS.UTF-8@latin, sr_CS@latin, sr@latin, sr_CS.UTF-8, sr_CS, sr.
        */
        $locale_names = array();
        if (preg_match("/([a-z]{2,3})"            // language code
                       ."(_([A-Z]{2}))?"          // country code
                       ."(\.([-A-Za-z0-9_]))?"    // charset
                       ."(@([-A-Za-z0-9_]+))?/",  // @ modifier
                       $locale, $matches)) {
          list(,$lang,,$country,,$charset,,$modifier) = $matches;
          if ($modifier) {
            $locale_names = array("${lang}_$country.$charset@$modifier",
                                  "${lang}_$country@$modifier",
                                  "$lang@$modifier");
          }
          array_push($locale_names,
                     "${lang}_$country.$charset", "${lang}_$country", "$lang");
        }
        array_push($locale_names, $locale);

        $input = null;
        foreach ($locale_names as $locale) {
          $full_path = $bound_path . $locale . "/" . $subpath;
          if (file_exists($full_path)) {
            $input = new FileReader($full_path);
            break;
          }
        }

        if (!array_key_exists($domain, $text_domains)) {
          // Initialize an empty domain object.
          $text_domains[$domain] = new domain();
        }
        $text_domains[$domain]->l10n = new gettext_reader($input,
                                                          $enable_cache);
    }
    return $text_domains[$domain]->l10n;
}

/**
 * Returns whether we are using our emulated gettext API or PHP built-in one.
 */
function locale_emulation() {
    global $EMULATEGETTEXT;
    return $EMULATEGETTEXT;
}

/**
 * Checks if the current locale is supported on this system.
 */
function _check_locale() {
    global $EMULATEGETTEXT;
    return !$EMULATEGETTEXT;
}

/**
 * Get the codeset for the given domain.
 */
function _get_codeset($domain=null) {
    global $text_domains, $default_domain, $LC_CATEGORIES;
    if (!isset($domain)) $domain = $default_domain;
    return (isset($text_domains[$domain]->codeset))? $text_domains[$domain]->codeset : ini_get('mbstring.internal_encoding');
}

/**
 * Convert the given string to the encoding set by bind_textdomain_codeset.
 */
function _encode($text) {
    $source_encoding = mb_detect_encoding($text);
    $target_encoding = _get_codeset();
    if ($source_encoding != $target_encoding) {
        return mb_convert_encoding($text, $target_encoding, $source_encoding);
    }
    else {
        return $text;
    }
}




// Custom implementation of the standard gettext related functions

/**
 * Sets a requested locale, if needed emulates it.
 */
function _setlocale($category, $locale) {
    global $CURRENTLOCALE, $EMULATEGETTEXT;
    if ($locale === 0) { // use === to differentiate between string "0"
        if ($CURRENTLOCALE != '')
            return $CURRENTLOCALE;
        else
            // obey LANG variable, maybe extend to support all of LC_* vars
            // even if we tried to read locale without setting it first
            return _setlocale($category, $CURRENTLOCALE);
    } else {
        $ret = 0;
        if (function_exists('setlocale')) // I don't know if this ever happens ;)
           $ret = setlocale($category, $locale);
        if (($ret and $locale == '') or ($ret == $locale)) {
            $EMULATEGETTEXT = 0;
            $CURRENTLOCALE = $ret;
        } else {
          if ($locale == '') // emulate variable support
             $CURRENTLOCALE = getenv('LANG');
        else
            $CURRENTLOCALE = $locale;
            $EMULATEGETTEXT = 1;
        }
        // Allow locale to be changed on the go for one translation domain.
        global $text_domains, $default_domain;
        unset($text_domains[$default_domain]->l10n);
        return $CURRENTLOCALE;
    }
}

/**
 * Sets the path for a domain.
 */
function _bindtextdomain($domain, $path) {
    global $text_domains;
    // ensure $path ends with a slash ('/' should work for both, but lets still play nice)
    if (substr(php_uname(), 0, 7) == "Windows") {
      if ($path[strlen($path)-1] != '\\' and $path[strlen($path)-1] != '/')
        $path .= '\\';
    } else {
      if ($path[strlen($path)-1] != '/')
        $path .= '/';
    }
    if (!array_key_exists($domain, $text_domains)) {
      // Initialize an empty domain object.
      $text_domains[$domain] = new domain();
    }
    $text_domains[$domain]->path = $path;
}

/**
 * Specify the character encoding in which the messages from the DOMAIN message catalog will be returned.
 */
function _bind_textdomain_codeset($domain, $codeset) {
    global $text_domains;
    $text_domains[$domain]->codeset = $codeset;
}

/**
 * Sets the default domain.
 */
function _textdomain($domain) {
    global $default_domain;
    $default_domain = $domain;
}

/**
 * Lookup a message in the current domain.
 */
function _gettext($msgid) {
    $l10n = _get_reader();
    //return $l10n->translate($msgid);
    return _encode($l10n->translate($msgid));
}
/**
 * Alias for gettext.
 */
function __($msgid) {
    return _gettext($msgid);
}
/**
 * Plural version of gettext.
 */
function _ngettext($single, $plural, $number) {
    $l10n = _get_reader();
    //return $l10n->ngettext($single, $plural, $number);
    return _encode($l10n->ngettext($single, $plural, $number));
}

/**
 * Override the current domain.
 */
function _dgettext($domain, $msgid) {
    $l10n = _get_reader($domain);
    //return $l10n->translate($msgid);
    return _encode($l10n->translate($msgid));
}
/**
 * Plural version of dgettext.
 */
function _dngettext($domain, $single, $plural, $number) {
    $l10n = _get_reader($domain);
    //return $l10n->ngettext($single, $plural, $number);
    return _encode($l10n->ngettext($single, $plural, $number));
}

/**
 * Overrides the domain and category for a single lookup.
 */
function _dcgettext($domain, $msgid, $category) {
    $l10n = _get_reader($domain, $category);
    //return $l10n->translate($msgid);
    return _encode($l10n->translate($msgid));
}
/**
 * Plural version of dcgettext.
 */
function _dcngettext($domain, $single, $plural, $number, $category) {
    $l10n = _get_reader($domain, $category);
    //return $l10n->ngettext($single, $plural, $number);
    return _encode($l10n->ngettext($single, $plural, $number));
}



// Wrappers to use if the standard gettext functions are available, but the current locale is not supported by the system.
// Use the standard impl if the current locale is supported, use the custom impl otherwise.

function T_setlocale($category, $locale) {
    return _setlocale($category, $locale);
}

function T_bindtextdomain($domain, $path) {
    if (_check_locale()) return bindtextdomain($domain, $path);
    else return _bindtextdomain($domain, $path);
}
function T_bind_textdomain_codeset($domain, $codeset) {
    // bind_textdomain_codeset is available only in PHP 4.2.0+
    if (_check_locale() and function_exists('bind_textdomain_codeset')) return bind_textdomain_codeset($domain, $codeset);
    else return _bind_textdomain_codeset($domain, $codeset);
}
function T_textdomain($domain) {
    if (_check_locale()) return textdomain($domain);
    else return _textdomain($domain);
}
function T_gettext($msgid) {
    if (_check_locale()) return gettext($msgid);
    else return _gettext($msgid);
}
function T_($msgid) {
    if (_check_locale()) return _($msgid);
    return __($msgid);
}
function T_ngettext($single, $plural, $number) {
    if (_check_locale()) return ngettext($single, $plural, $number);
    else return _ngettext($single, $plural, $number);
}
function T_dgettext($domain, $msgid) {
    if (_check_locale()) return dgettext($domain, $msgid);
    else return _dgettext($domain, $msgid);
}
function T_dngettext($domain, $single, $plural, $number) {
    if (_check_locale()) return dngettext($domain, $single, $plural, $number);
    else return _dngettext($domain, $single, $plural, $number);
}
function T_dcgettext($domain, $msgid, $category) {
    if (_check_locale()) return dcgettext($domain, $msgid, $category);
    else return _dcgettext($domain, $msgid, $category);
}
function T_dcngettext($domain, $single, $plural, $number, $category) {
    if (_check_locale()) return dcngettext($domain, $single, $plural, $number, $category);
    else return _dcngettext($domain, $single, $plural, $number, $category);
}



// Wrappers used as a drop in replacement for the standard gettext functions

if (!function_exists('gettext')) {
    function bindtextdomain($domain, $path) {
        return _bindtextdomain($domain, $path);
    }
    function bind_textdomain_codeset($domain, $codeset) {
        return _bind_textdomain_codeset($domain, $codeset);
    }
    function textdomain($domain) {
        return _textdomain($domain);
    }
    function gettext($msgid) {
        return _gettext($msgid);
    }
    function _($msgid) {
        return __($msgid);
    }
    function ngettext($single, $plural, $number) {
        return _ngettext($single, $plural, $number);
    }
    function dgettext($domain, $msgid) {
        return _dgettext($domain, $msgid);
    }
    function dngettext($domain, $single, $plural, $number) {
        return _dngettext($domain, $single, $plural, $number);
    }
    function dcgettext($domain, $msgid, $category) {
        return _dcgettext($domain, $msgid, $category);
    }
    function dcngettext($domain, $single, $plural, $number, $category) {
        return _dcngettext($domain, $single, $plural, $number, $category);
    }
}

?>
