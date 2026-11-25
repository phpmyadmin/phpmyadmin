/* eslint-env node, jest */

import $ from 'jquery';
import { AJAX } from 'phpmyadmin/modules/ajax.ts';
import 'phpmyadmin/export.ts';

const exportForm = (selectedFormat = 'sql', exportMethod = 'quick') => `
    <form name="dump">
        <input type="hidden" name="export_type" value="table">
        <div id="quick_or_custom">
            <input type="radio" name="quick_or_custom" value="quick" id="radio_quick_export" ${exportMethod === 'quick' ? 'checked' : ''}>
            <input type="radio" name="quick_or_custom" value="custom" id="radio_custom_export" ${exportMethod === 'custom' ? 'checked' : ''}>
        </div>
        <select id="plugins">
            <option value="sql" ${selectedFormat === 'sql' ? 'selected' : ''}>SQL</option>
            <option value="csv" ${selectedFormat === 'csv' ? 'selected' : ''}>CSV</option>
            <option value="json" ${selectedFormat === 'json' ? 'selected' : ''}>JSON</option>
        </select>
        <div id="format_specific_opts" class="d-none">
            <div id="sql_options" class="format_specific_options d-none"><h3>SQL</h3></div>
            <div id="csv_options" class="format_specific_options d-none"><h3>CSV</h3></div>
            <div id="json_options" class="format_specific_options d-none"><h3>JSON</h3></div>
        </div>
        <div id="rows" class="d-none"></div>
        <div id="output" class="d-none"></div>
        <div id="output_quick_export" class="d-none"></div>
    </form>`;

function loadExportPage (selectedFormat = 'sql', exportMethod = 'quick') {
    document.body.innerHTML = exportForm(selectedFormat, exportMethod);
    AJAX.fireOnload('export.js');
}

function visibleFormatOptions () {
    return $('#format_specific_opts .format_specific_options:not(.d-none)')
        .map(function () {
            return this.id;
        })
        .get();
}

describe('Export format options', () => {
    afterEach(() => {
        AJAX.fireTeardown('export.js');
        document.body.innerHTML = '';
        jest.restoreAllMocks();
    });

    test('uses the current format when CSV is selected before switching to Custom', () => {
        loadExportPage('sql', 'quick');

        $('#plugins').val('csv').trigger('change');
        $('#radio_custom_export').prop('checked', true).trigger('change');

        expect(visibleFormatOptions()).toEqual(['csv_options']);
    });

    test('shows only CSV when Custom is initially loaded with CSV selected', () => {
        loadExportPage('csv', 'custom');

        expect(visibleFormatOptions()).toEqual(['csv_options']);
    });

    test('switches from SQL to CSV while Custom is active', () => {
        loadExportPage('sql', 'custom');
        $('#plugins').val('csv').trigger('change');

        expect(visibleFormatOptions()).toEqual(['csv_options']);
    });

    test('switches CSV to SQL to CSV and keeps exactly one panel visible', () => {
        loadExportPage('csv', 'custom');

        $('#plugins').val('sql').trigger('change');
        expect(visibleFormatOptions()).toEqual(['sql_options']);

        $('#plugins').val('csv').trigger('change');
        expect(visibleFormatOptions()).toEqual(['csv_options']);
    });

    test('shows the current format on Quick to Custom and repeated format changes', () => {
        loadExportPage('sql', 'quick');
        expect(visibleFormatOptions()).toEqual([]);

        $('#plugins').val('json').trigger('change');
        $('#radio_custom_export').prop('checked', true).trigger('change');
        expect(visibleFormatOptions()).toEqual(['json_options']);

        $('#plugins').val('csv').trigger('change');
        expect(visibleFormatOptions()).toEqual(['csv_options']);

        $('#plugins').val('sql').trigger('change');
        expect(visibleFormatOptions()).toEqual(['sql_options']);
    });

    test('rebinds one Quick/Custom handler after AJAX teardown and page load', () => {
        const quickCustomChangeHandlerCount = () => {
            // eslint-disable-next-line no-underscore-dangle
            const handlers = $._data($('#radio_custom_export')[0], 'events');

            return handlers?.change?.length ?? 0;
        };

        loadExportPage('sql', 'quick');
        expect(quickCustomChangeHandlerCount()).toBe(1);

        AJAX.fireTeardown('export.js');
        loadExportPage('csv', 'quick');

        expect(quickCustomChangeHandlerCount()).toBe(1);
        $('#plugins').val('csv').trigger('change');
        $('#radio_custom_export').prop('checked', true).trigger('change');
        expect(visibleFormatOptions()).toEqual(['csv_options']);
    });
});
