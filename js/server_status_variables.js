/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 *
 * @package PhpMyAdmin
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_status_variables.js', function () {
    $('#filterAlert').unbind('change');
    $('#filterText').unbind('keyup');
    $('#filterCategory').unbind('change');
    $('#dontFormat').unbind('change');
});

AJAX.registerOnload('server_status_variables.js', function () {

    // Filters for status variables
    var textFilter = null;
    var alertFilter = $('#filterAlert').prop('checked');
    var categoryFilter = $('#filterCategory').find(':selected').val();
    var odd_row = false;
    var text = ''; // Holds filter text

    /* 3 Filtering functions */
    $('#filterAlert').change(function () {
        alertFilter = this.checked;
        filterVariables();
    });

    $('#filterCategory').change(function () {
        categoryFilter = $(this).val();
        filterVariables();
    });

    $('#dontFormat').change(function () {
        // Hiding the table while changing values speeds up the process a lot
        $('#serverstatusvariables').hide();
        $('#serverstatusvariables').find('td.value span.original').toggle(this.checked);
        $('#serverstatusvariables').find('td.value span.formatted').toggle(! this.checked);
        $('#serverstatusvariables').show();
    }).trigger('change');

    $('#filterText').keyup(function (e) {
        var word = $(this).val().replace(/_/g, ' ');
        if (word.length === 0) {
            textFilter = null;
        } else {
            try {
                textFilter = new RegExp("(^| )" + word, 'i');
                $(this).removeClass('error');
            } catch(e) {
                if (e instanceof SyntaxError) {
                    $(this).addClass('error');
                    textFilter = null;
                }
            }
        }
        text = word;
        filterVariables();
    }).trigger('keyup');

    /* Filters the status variables by name/category/alert in the variables tab */
    function filterVariables() {
        var useful_links = 0;
        var section = text;

        if (categoryFilter.length > 0) {
            section = categoryFilter;
        }

        if (section.length > 1) {
            $('#linkSuggestions').find('span').each(function () {
                if ($(this).attr('class').indexOf('status_' + section) != -1) {
                    useful_links++;
                    $(this).css('display', '');
                } else {
                    $(this).css('display', 'none');
                }
            });
        }

        if (useful_links > 0) {
            $('#linkSuggestions').css('display', '');
        } else {
            $('#linkSuggestions').css('display', 'none');
        }

        odd_row = false;
        $('#serverstatusvariables').find('th.name').each(function () {
            if ((textFilter === null || textFilter.exec($(this).text())) &&
                (! alertFilter || $(this).next().find('span.attention').length > 0) &&
                (categoryFilter.length === 0 || $(this).parent().hasClass('s_' + categoryFilter))
            ) {
                odd_row = ! odd_row;
                $(this).parent().css('display', '');
                if (odd_row) {
                    $(this).parent().addClass('odd');
                    $(this).parent().removeClass('even');
                } else {
                    $(this).parent().addClass('even');
                    $(this).parent().removeClass('odd');
                }
            } else {
                $(this).parent().css('display', 'none');
            }
        });
    }
});
