/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in QBE for DB
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Ajax event handlers here for db_qbe.php
 *
 * Actions Ajaxified here:
 * Select saved search
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('db_qbe.js', function () {
    $("#existingSavedSearches").die('change');
<<<<<<< HEAD
    $("#saveSearch").die('click');
});

$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

=======
});

>>>>>>> 1ef99b285bb5e70ea7c9975075f4598cd087aacf
AJAX.registerOnload('db_qbe.js', function () {

    /**
     * Ajax event handlers for 'Select saved search'
     */
    $("#existingSavedSearches").live('change', function (event) {
        event.preventDefault();

<<<<<<< HEAD
        //$('#formQBE').submit();

        /*var selectedElement = $('#' + this.id + ' option:selected');
=======
        var selectedElement = $('#' + this.id + ' option:selected');
>>>>>>> 1ef99b285bb5e70ea7c9975075f4598cd087aacf
        var nameElement = $('#searchName');

        if (selectedElement.val() == '') {
            nameElement.val('');
            return;
        }
<<<<<<< HEAD
        nameElement.val(selectedElement.text());*/

        /*// Code to add columns.
        $('select[name=criteriaColumnAdd]').val(3);
        $('input[name=modify]').click();
        */

        /*// Code to add rows.
        $('select[name=criteriaRowAdd]').val(1);
        $('input[name=modify]').click();
        */
    });

    /**
     * Ajax event handlers for 'Save search'
     */
    $("#saveSearch").live('click', function (event) {
        //event.preventDefault();

        //Next section will generate the JSON to save.

        //List of select and text.
        var criteriaList = new Array(
            'criteriaColumn',
            'criteriaSort',
            'criteria'
        );
        //List of radio.
        var criteriaRadioList = new Array(
            'criteriaAndOrRow',
            'criteriaAndOrColumn'
        );

        var jsonForm = {};
        var nbColumns = 1;
        var nbCriterias = 2;

        var selectedElement = $('#formQBE :input');
        selectedElement.each(function() {
            var element = $(this);
            var shortName = this.name;
            var pos = shortName.indexOf('[');
            if (-1 != pos) {
                shortName = shortName.substr(0, pos);

                var nbColumnsTemp = this.name.substr(pos);
                nbColumnsTemp = nbColumnsTemp.substr(1, nbColumnsTemp.length-2);
                if (nbColumnsTemp > nbColumns) {
                    nbColumns = nbColumnsTemp;
                }
            }

            //List of select and text.
            if (
                -1 != $.inArray(shortName, criteriaList)
                || 'Or' == shortName.substr(0, 2)
            ) {
                jsonForm[this.name] = element.val();

                if ('Or' == shortName.substr(0, 2)) {
                    var nbCriteriasTemp = shortName.substr(2);
                    if (nbCriteriasTemp > nbCriterias) {
                        nbCriterias = nbCriteriasTemp;
                    }
                }

                return;
            }
            //List of radio.
            if (-1 != $.inArray(shortName, criteriaRadioList)) {
                if (element.prop('checked')) {
                    jsonForm[this.name] =  element.val();
                }
                return;
            }
            //Checkbox to show the a column in the query.
            if ('criteriaShow' == shortName) {
                jsonForm[this.name] =  element.prop('checked');
                return;
            }
        });

        jsonForm['nbColumns'] = nbColumns;
        jsonForm['nbCriterias'] = nbCriterias;

        $('#criterias').val(JSON.stringify(jsonForm));
=======
        nameElement.val(selectedElement.text());

        //Then : load the data.
>>>>>>> 1ef99b285bb5e70ea7c9975075f4598cd087aacf
    }); // end Select saved search
});
