(function($){
    $.fn.doubletap = function(callback, delay) {
        delay = delay == null ? 500 : delay;
        $(this).bind('click', function(event) {
            var now = new Date().getTime();
            var lastTouch = $(this).data('lastTouch') || now + 1;
            var delta = now - lastTouch;
            if (delta < 500 && delta > 0) {
                if ($.isFunction(callback)) {
                    callback.call(this, event);
                }
            }
            $(this).data('lastTouch', now);
        });
        return this;
    };
})(jQuery);
