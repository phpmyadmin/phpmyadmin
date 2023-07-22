/* eslint-env node, jest */

const Change = require('phpmyadmin/table/change');

describe('change', () => {
    function testSingle (fn) {
        test('empty string', function () {
            expect(fn('')).toBe(false);
        });
        test('whitespace string', function () {
            expect(fn('  ')).toBe(false);
        });
        test('space around', function () {
            expect(fn('  2  ')).toBe(true);
        });
        test('exponential', function () {
            expect(fn('102823466E+38')).toBe(true);
        });
        test('negative decimal', function () {
            expect(fn('-3.402823466')).toBe(true);
        });
        test('positive decimal', function () {
            expect(fn('+3.402823466')).toBe(true);
        });
        test('positive integer', function () {
            expect(fn('+3402823466')).toBe(true);
        });
        test('super small negative exponential', function () {
            expect(fn('-2.2250738585072014E-308')).toBe(true);
        });
        test('hexadecimal', function () {
            expect(fn('0x12341')).toBe(true);
        });
        test('negative hexadecimal', function () {
            expect(fn('-0xaf')).toBe(true);
        });
        test('multiple signs', function () {
            expect(fn('-+-2')).toBe(true);
        });
        test('No decimals for hex', function () {
            expect(fn('0x2.2')).toBe(false);
        });
        test('Accept a letter for hex', function () {
            expect(fn('0xf')).toBe(true);
        });
        test('Accept multiple letters for hex', function () {
            expect(fn('0xffabc')).toBe(true);
        });
        test('Upper case hex', function () {
            expect(fn('0xFAFB')).toBe(true);
        });
        test('Forbid invalid hex chars', function () {
            expect(fn('0xag')).toBe(false);
        });
        test('Not matches chars', function () {
            expect(fn('abcdef')).toBe(false);
        });
        test('pipe seperator', function () {
            expect(fn('0123456789|0123456789')).toBe(false);
        });
        test('Leading , is not accepted', function () {
            expect(fn(',0x12341')).toBe(false);
        });
        test('Trailing , is not accepted', function () {
            expect(fn('0x12341,')).toBe(false);
        });
    }

    describe('validationFunctionForMultipleInt', () => {
        const fn = Change.validationFunctionForMultipleInt;
        testSingle(fn);

        test('two numbers with leading zeros', function () {
            expect(fn('0123456789,0123456789')).toBe(true);
        });
        test('negative exponential and decimal with leading zero', function () {
            expect(fn('-3.402823466E+38,0123456789')).toBe(true);
        });
        test('negative decimal followed by negative exponential', function () {
            expect(fn('-3.402823466,-3.402823466E+38')).toBe(true);
        });
        test('Multi hex & int', function () {
            expect(fn('0xf,0xa,0xb,124')).toBe(true);
        });
        test('Forbid invalid hex chars', function () {
            expect(fn('0xaf,0xag')).toBe(false);
        });
        test('Multi hex more complex', function () {
            expect(fn('0xaf,0x0ad')).toBe(true);
        });
        test('Space after comma numbers', function () {
            expect(fn('1, 2, 3')).toBe(true);
        });
        test('Spaces everywhere', function () {
            expect(fn('  1 ,  2  ,  3  ')).toBe(true);
        });
    });

    describe('validationFunctionForInt', () => {
        const fn = Change.validationFunctionForInt;
        testSingle(fn);

        test('two numbers', function () {
            expect(fn('123,465')).toBe(false);
        });
    });
});
