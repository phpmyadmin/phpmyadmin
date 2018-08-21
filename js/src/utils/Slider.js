/**
 * Changes status of slider
 */
function PMA_set_status_label ($element) {
    var text;
    if ($element.css('display') === 'none') {
        text = '+ ';
    } else {
        text = '- ';
    }
    $element.closest('.slide-wrapper').prev().find('span').text(text);
}

/**
 * Initializes slider effect.
 */
export function PMA_init_slider () {
    $('div.pma_auto_slider').each(function () {
        var $this = $(this);
        if ($this.data('slider_init_done')) {
            return;
        }
        var $wrapper = $('<div>', { 'class': 'slide-wrapper' });
        $wrapper.toggle($this.is(':visible'));
        $('<a>', { href: '#' + this.id, 'class': 'ajax' })
            .text($this.attr('title'))
            .prepend($('<span>'))
            .insertBefore($this)
            .on('click', function () {
                var $wrapper = $this.closest('.slide-wrapper');
                var visible = $this.is(':visible');
                if (!visible) {
                    $wrapper.show();
                }
                $this[visible ? 'hide' : 'show']('blind', function () {
                    $wrapper.toggle(!visible);
                    $wrapper.parent().toggleClass('print_ignore', visible);
                    PMA_set_status_label($this);
                });
                return false;
            });
        $this.wrap($wrapper);
        $this.removeAttr('title');
        PMA_set_status_label($this);
        $this.data('slider_init_done', 1);
    });
}
