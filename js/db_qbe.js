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
    $("#saveSearch").die('click');
});

AJAX.registerOnload('db_qbe.js', function () {

    /**
     * Ajax event handlers for 'Select saved search'
     */
    $("#existingSavedSearches").live('change', function (event) {
        event.preventDefault();

        var selectedElement = $('#' + this.id + ' option:selected');
        var nameElement = $('#searchName');

        if (selectedElement.val() == '') {
            nameElement.val('');
            return;
        }
        nameElement.val(selectedElement.text());

        /* Code to add columns.
        $('select[name=criteriaColumnAdd]').val(3);
        $('input[name=modify]').click();
        */

        //Then : load the data.
    }); // end Select saved search

    /**
     * Ajax event handlers for 'Save search'
     */
    $("#saveSearch").live('click', function (event) {
        event.preventDefault();

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

        console.debug(JSON.stringify(jsonForm));

        //Then : load the data.
    }); // end Select saved search
});
