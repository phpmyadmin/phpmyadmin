/* eslint-env node, jest */

var Sql = require('phpmyadmin/sql');

describe('SQL', () => {
    test('test URL encode', () => {
        const urlDecoded = Sql.urlDecode('phpmyadmin+the+web+%C3%BB%C3%AF');
        expect(urlDecoded).toEqual('phpmyadmin the web ûï');
    });
    test('test URL decode', () => {
        const urlEncoded = Sql.urlEncode('phpmyadmin the web ûï');
        expect(urlEncoded).toEqual('phpmyadmin+the+web+%C3%BB%C3%AF');
    });
});
