/* eslint-env node */

module.exports = {
    extensionsToTreatAsEsm: ['.ts'],
    coverageDirectory: '<rootDir>/build/javascript/',
    collectCoverageFrom: ['<rootDir>/js/src/**/*.ts'],
    projects: [
        {
            coveragePathIgnorePatterns: [
                '<rootDir>/node_modules/',
                '<rootDir>/js/vendor/',
            ],
            displayName: 'phpMyAdmin',
            testMatch: ['<rootDir>/test/javascript/**/*.ts'],
            transform: { '\\.[jt]sx?$': 'babel-jest' },
            moduleNameMapper: {
                '^phpmyadmin/(.*)$': '<rootDir>/js/src/$1',
                '^@vendor/(.*)$': '<rootDir>/js/vendor/$1',
            },
            testEnvironment: 'jsdom',
        }
    ]
};
