<?php
// Use native gettext implementation, fallback to php-gettext if not available

if (function_exists('gettext')) {
  function _bindtextdomain($domain, $path) {
      return bindtextdomain($domain, $path);
  }
  function _bind_textdomain_codeset($domain, $codeset) {
      return bind_textdomain_codeset($domain, $codeset);
  }
  function _textdomain($domain) {
      return textdomain($domain);
  }
  function _gettext($msgid) {
      return gettext($msgid);
  }
  function __($msgid) {
      return _($msgid);
  }
  function _ngettext($singular, $plural, $number) {
      return ngettext($singular, $plural, $number);
  }
  function _dgettext($domain, $msgid) {
      return dgettext($domain, $msgid);
  }
  function _dngettext($domain, $singular, $plural, $number) {
      return dngettext($domain, $singular, $plural, $number);
  }
  function _dcgettext($domain, $msgid, $category) {
      return dcgettext($domain, $msgid, $category);
  }
  function _dcngettext($domain, $singular, $plural, $number, $category) {
      return dcngettext($domain, $singular, $plural, $number, $category);
  }
  function _pgettext($context, $msgid) {
      return pgettext($context, $msgid);
  }
  function _npgettext($context, $singular, $plural, $number) {
      return npgettext($context, $singular, $plural, $number);
  }
  function _dpgettext($domain, $context, $msgid) {
      return dpgettext($domain, $context, $msgid);
  }
  function _dnpgettext($domain, $context, $singular, $plural, $number) {
      return dnpgettext($domain, $context, $singular, $plural, $number);
  }
  function _dcpgettext($domain, $context, $msgid, $category) {
      return dcpgettext($domain, $context, $msgid, $category);
  }
  function _dcnpgettext($domain, $context, $singular, $plural,
                       $number, $category) {
    return dcnpgettext($domain, $context, $singular, $plural,
                        $number, $category);
  }

  function _setlocale($category, $locale) {
      //http://stackoverflow.com/questions/10175658/is-there-a-simple-way-to-get-the-language-code-from-a-country-code-in-php
      //http://stackoverflow.com/questions/8568762/get-default-locale-for-language-in-php
      //http://stackoverflow.com/questions/3191664/list-of-all-locales-and-their-short-codes
      //http://stackoverflow.com/questions/13268930/where-can-i-find-a-list-of-language-region-codes/13269403
      //@todo windows locale names https://docs.moodle.org/dev/Table_of_locales
      switch($locale){
          case 'bn': $dialects = array('BD'); break;
          case 'ca': $dialects = array('ES'); break;
          case 'cs': $dialects = array('CZ'); break;
          case 'da': $dialects = array('DK'); break;
          case 'el': $dialects = array('GR'); break;
          case 'en': $dialects = array('US', 'GB', /* other? */); break;
          case 'et': $dialects = array('EE'); break;
          case 'gl': $dialects = array('ES'); break;
          case 'hi': $dialects = array('IN'); break;
          case 'hy': $dialects = array('AM'); break;
          case 'ia': $dialects = array('FR'); break;
          case 'ja': $dialects = array('JP'); break;
          case 'ko': $dialects = array('KR'); break;
          case 'nb': $dialects = array('NO'); break;
          case 'si': $dialects = array('LK'); break;
          case 'sl': $dialects = array('SI'); break;
          case 'sq': $dialects = array('AL'); break;
          case 'sr@latin': $locale='sr'; $dialects = array('RS@latin'); break;
          case 'sv': $dialects = array('SE'); break;
          case 'uk': $dialects = array('UA'); break;
          default: $dialects = array();
      }
      $guess_locale = array();
      foreach($dialects as $dialect){
          $guess_locale[] = $locale . '_' . $dialect . '.UTF-8'; //prefer UTF-8
      }
      $guess_locale[] = $locale . '_' . strtoupper($locale) . '.UTF-8';
      $guess_locale[] = $locale . '.UTF-8';
      foreach($dialects as $dialect){
          $guess_locale[] = $locale . '_' . $dialect;
      }
      $guess_locale[] = $locale;
      setlocale($category, $guess_locale) or trigger_error('setlocale(' . $category . ',' . join('|', $guess_locale) . ') failed');
  }

  //php-gettext specific
  function pgettext($context, $msgid) {
    $key = $context . chr(4) . $msgid;
    $ret = _($key);
    if (strpos($ret, "\004") !== FALSE) {
      return $msgid;
    } else {
      return $ret;
    }
  }

  function npgettext($context, $singular, $plural, $number) {
    $key = $context . chr(4) . $singular;
    $ret = _($key, $plural, $number);
    if (strpos($ret, "\004") !== FALSE) {
      return $singular;
    } else {
      return $ret;
    }
  }

} else {
  include_once './libraries/php-gettext/gettext.inc';
}
<?php
// Use native gettext implementation, fallback to php-gettext if not available

if (function_exists('gettext')) {
  function _bindtextdomain($domain, $path) {
      return bindtextdomain($domain, $path);
  }
  function _bind_textdomain_codeset($domain, $codeset) {
      return bind_textdomain_codeset($domain, $codeset);
  }
  function _textdomain($domain) {
      return textdomain($domain);
  }
  function _gettext($msgid) {
      return gettext($msgid);
  }
  function __($msgid) {
      return _($msgid);
  }
  function _ngettext($singular, $plural, $number) {
      return ngettext($singular, $plural, $number);
  }
  function _dgettext($domain, $msgid) {
      return dgettext($domain, $msgid);
  }
  function _dngettext($domain, $singular, $plural, $number) {
      return dngettext($domain, $singular, $plural, $number);
  }
  function _dcgettext($domain, $msgid, $category) {
      return dcgettext($domain, $msgid, $category);
  }
  function _dcngettext($domain, $singular, $plural, $number, $category) {
      return dcngettext($domain, $singular, $plural, $number, $category);
  }
  function _pgettext($context, $msgid) {
      return pgettext($context, $msgid);
  }
  function _npgettext($context, $singular, $plural, $number) {
      return npgettext($context, $singular, $plural, $number);
  }
  function _dpgettext($domain, $context, $msgid) {
      return dpgettext($domain, $context, $msgid);
  }
  function _dnpgettext($domain, $context, $singular, $plural, $number) {
      return dnpgettext($domain, $context, $singular, $plural, $number);
  }
  function _dcpgettext($domain, $context, $msgid, $category) {
      return dcpgettext($domain, $context, $msgid, $category);
  }
  function _dcnpgettext($domain, $context, $singular, $plural,
                       $number, $category) {
    return dcnpgettext($domain, $context, $singular, $plural,
                        $number, $category);
  }

  function _setlocale($category, $locale) {
      //http://stackoverflow.com/questions/10175658/is-there-a-simple-way-to-get-the-language-code-from-a-country-code-in-php
      //http://stackoverflow.com/questions/8568762/get-default-locale-for-language-in-php
      //http://stackoverflow.com/questions/3191664/list-of-all-locales-and-their-short-codes
      //http://stackoverflow.com/questions/13268930/where-can-i-find-a-list-of-language-region-codes/13269403
      //@todo windows locale names https://docs.moodle.org/dev/Table_of_locales
      switch($locale){
          case 'bn': $dialects = array('BD'); break;
          case 'ca': $dialects = array('ES'); break;
          case 'cs': $dialects = array('CZ'); break;
          case 'da': $dialects = array('DK'); break;
          case 'el': $dialects = array('GR'); break;
          case 'en': $dialects = array('US', 'GB', /* other? */); break;
          case 'et': $dialects = array('EE'); break;
          case 'gl': $dialects = array('ES'); break;
          case 'hi': $dialects = array('IN'); break;
          case 'hy': $dialects = array('AM'); break;
          case 'ia': $dialects = array('FR'); break;
          case 'ja': $dialects = array('JP'); break;
          case 'ko': $dialects = array('KR'); break;
          case 'nb': $dialects = array('NO'); break;
          case 'si': $dialects = array('LK'); break;
          case 'sl': $dialects = array('SI'); break;
          case 'sq': $dialects = array('AL'); break;
          case 'sr@latin': $locale='sr'; $dialects = array('RS@latin'); break;
          case 'sv': $dialects = array('SE'); break;
          case 'uk': $dialects = array('UA'); break;
          default: $dialects = array();
      }
      $guess_locale = array();
      foreach($dialects as $dialect){
          $guess_locale[] = $locale . '_' . $dialect . '.UTF-8'; //prefer UTF-8
      }
      $guess_locale[] = $locale . '_' . strtoupper($locale) . '.UTF-8';
      $guess_locale[] = $locale . '.UTF-8';
      foreach($dialects as $dialect){
          $guess_locale[] = $locale . '_' . $dialect;
      }
      $guess_locale[] = $locale;
      setlocale($category, $guess_locale) or trigger_error('setlocale(' . $category . ',' . join('|', $guess_locale) . ') failed');
  }

  //php-gettext specific
  function pgettext($context, $msgid) {
    $key = $context . chr(4) . $msgid;
    $ret = _($key);
    if (strpos($ret, "\004") !== FALSE) {
      return $msgid;
    } else {
      return $ret;
    }
  }

  function npgettext($context, $singular, $plural, $number) {
    $key = $context . chr(4) . $singular;
    $ret = _($key, $plural, $number);
    if (strpos($ret, "\004") !== FALSE) {
      return $singular;
    } else {
      return $ret;
    }
  }

} else {
  include_once './libraries/php-gettext/gettext.inc';
}
