/* eslint-env node */

module.exports = {
    extensionsToTreatAsEsm: ['.ts'],
    coverageDirectory: '<rootDir>/build/javascript/',
    collectCoverageFrom: ['<rootDir>/resources/js/**/*.ts'],
    projects: [
        {
            coveragePathIgnorePatterns: [
                '<rootDir>/node_modules/',
                '<rootDir>/public/js/vendor/',
            ],
            displayName: 'phpMyAdmin',
            testMatch: ['<rootDir>/tests/javascript/**/*.ts'],
            transform: { '\\.[jt]sx?$': 'babel-jest' },
            moduleNameMapper: {
                '^phpmyadmin/(.*)$': '<rootDir>/resources/js/$1',
                '^@vendor/(.*)$': '<rootDir>/public/js/vendor/$1',
            },
            testEnvironment: 'jsdom',
        }
    ]
};
