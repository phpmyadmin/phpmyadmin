/* eslint-env node, jest */

require('@vendor/jquery/jquery-ui.min.js');
require('@vendor/jquery/jquery-ui-timepicker-addon.js');
require('phpmyadmin/name-conflict-fixes');

const Functions = require('phpmyadmin/functions');

describe('Functions', () => {
    describe('Testing stringifyJSON', function () {
        test('Should return the stringified JSON input', () => {
            const stringifiedJSON = Functions.stringifyJSON('{ "lang": "php"}', null, 4);
            expect(stringifiedJSON).toEqual('{\n    "lang": "php"\n}');
        });

        test('Should return the input as it is', () => {
            const stringifiedJSON = Functions.stringifyJSON('{ "name": "notvalid}');
            expect(stringifiedJSON).toEqual('{ "name": "notvalid}');
        });
    });

    describe('Testing addDatepicker', function () {
        beforeAll(() => {
            global.themeImagePath = '';
            global.Messages = { strMysqlAllowedValuesTipTime: '' };
            // jsdom has no layout, so the picker cannot be positioned
            jest.spyOn($.datepicker, '_findPos').mockReturnValue([0, 0]);
            jest.spyOn($.datepicker, '_checkOffset').mockImplementation((inst, offset) => offset);
        });

        afterEach(() => {
            $('#test_input').datepicker('hide');
            document.body.innerHTML = '';
        });

        const createPicker = (type, timeFormat, value) => {
            document.body.innerHTML = '<input id="test_input" type="text">';
            const $input = $('#test_input').val(value);
            Functions.addDatepicker($input, type, {
                showMillisec: timeFormat.indexOf('l') !== -1,
                showMicrosec: timeFormat.indexOf('c') !== -1,
                timeFormat: timeFormat,
            });
            $input.datepicker('show');

            return $input;
        };

        test.each([
            ['time', 'HH:mm:ss.l', '10:00:00.500', '12:34:56.12', '12:34:56.120'],
            ['time', 'HH:mm:ss.l', '10:00:00.500', '12:34:56.04', '12:34:56.040'],
            ['time', 'HH:mm:ss.lc', '10:00:00.500000', '12:34:56.1234', '12:34:56.123400'],
            ['datetime', 'HH:mm:ss.l', '2026-02-08 10:00:00.500', '12:34:56.5', '2026-02-08 12:34:56.500'],
            ['time', 'HH:mm:ss', '10:00:00', '12:34:56', '12:34:56'],
        ])('Should keep the fractional seconds typed in the time input of a %s picker (%s)', (
            type, timeFormat, value, typedTime, expected
        ) => {
            const $input = createPicker(type, timeFormat, value);

            $('.ui_tpicker_time_input').val(typedTime).trigger('change');

            expect($input.val()).toEqual(expected);
        });

        test.each([
            ['time', 'HH:mm:ss.l', '10:00:00.500', '12:34:56.12', '12:34:56.120'],
            ['time', 'HH:mm:ss.lc', '10:00:00.500000', '12:34:56.1234', '12:34:56.123400'],
            ['datetime', 'HH:mm:ss.l', '2026-02-08 10:00:00.500', '2026-02-08 12:34:56.5', '2026-02-08 12:34:56.500'],
        ])('Should keep the fractional seconds typed in a %s field (%s)', (
            type, timeFormat, value, typedValue, expected
        ) => {
            const $input = createPicker(type, timeFormat, value);

            $input.val(typedValue).trigger(new $.Event('keyup', { which: 50, keyCode: 50 }));
            $input.datepicker('hide');

            expect($input.val()).toEqual(expected);
        });
    });
});
