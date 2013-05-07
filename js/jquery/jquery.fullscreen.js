// jQuery.FullScreen plugin

// Triple-licensed: Public Domain, MIT and WTFPL license - share and enjoy!

(function($) {
  function isFullScreen() {
    return document[!prefix ? 'fullScreen' :
        'webkit' === prefix ? 'webkitIsFullScreen' :
                     prefix + 'FullScreen'];
  }
  function cancelFullScreen() {
    return document[prefix ? prefix + 'CancelFullScreen'
                           : 'cancelFullScreen']();
  }

  var supported = typeof document.cancelFullScreen !== 'undefined'
    , prefixes = ['webkit', 'moz', 'o', 'ms', 'khtml']
    , prefix = ''
    , noop = function() {}
    , i
    ;

  if (!supported) {
    for (i = 0; prefix = prefixes[i]; i++) {
      if (typeof document[prefix + 'CancelFullScreen'] !== 'undefined') {
        supported = true;
        break;
      }
    }
  }

  if (supported) {
    $.fn.requestFullScreen = function() {
      return this.each(function() {
        return this[prefix ? prefix + 'RequestFullScreen'
                           : 'requestFullScreen']();
      });
    };
    $.fn.fullScreenChange = function(fn) {
      var ar = [prefix + 'fullscreenchange'].concat([].slice.call(arguments, 0))
        , $e = $(this);
      return $e.bind.apply($e, ar);
    };
    $.FullScreen =
      { isFullScreen: isFullScreen
      , cancelFullScreen: cancelFullScreen
      , prefix: prefix
      , supported: supported
      };
  }
  else {
    $.fn.requestFullScreen = $.fn.fullScreenChange = noop;
    $.FullScreen =
      { isFullScreen: function() { return false; }
      , cancelFullScreen: noop
      , prefix: prefix
      , supported: supported
      };
  }
})(jQuery);
