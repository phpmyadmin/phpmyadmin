/* eslint-env node */

module.exports = {
    extensionsToTreatAsEsm: ['.ts'],
    coverageDirectory: '<rootDir>/build/javascript/',
    collectCoverageFrom: ['<rootDir>/resources/js/src/**/*.ts'],
    projects: [
        {
            coveragePathIgnorePatterns: [
                '<rootDir>/node_modules/',
                '<rootDir>/public/js/vendor/',
            ],
            displayName: 'phpMyAdmin',
            testMatch: ['<rootDir>/test/javascript/**/*.ts'],
            transform: { '\\.[jt]sx?$': 'babel-jest' },
            moduleNameMapper: {
                '^phpmyadmin/(.*)$': '<rootDir>/resources/js/src/$1',
                '^@vendor/(.*)$': '<rootDir>/public/js/vendor/$1',
            },
            testEnvironment: 'jsdom',
        }
    ]
};
