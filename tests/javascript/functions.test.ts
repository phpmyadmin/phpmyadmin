/* eslint-env node, jest */

import { stringifyJSON } from '../../resources/js/modules/functions.ts';

describe('Functions', () => {
    describe('Testing stringifyJSON', function () {
        test('Should return the stringified JSON input', () => {
            const stringifiedJSON = stringifyJSON('{ "lang": "php"}', null, 4);
            expect(stringifiedJSON).toEqual('{\n    "lang": "php"\n}');
        });

        test('Should return the input as it is', () => {
            const stringifiedJSON = stringifyJSON('{ "name": "notvalid}');
            expect(stringifiedJSON).toEqual('{ "name": "notvalid}');
        });
    });
});
