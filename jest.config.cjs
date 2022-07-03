/* eslint-env node */

module.exports = {
    coverageDirectory: '<rootDir>/build/javascript/',
    collectCoverageFrom: ['<rootDir>/js/src/**/*.js'],
    projects: [
        {
            verbose: true,
            coveragePathIgnorePatterns: [
                '<rootDir>/node_modules/',
                '<rootDir>/js/vendor/',
            ],
            displayName: 'phpMyAdmin',
            testMatch: ['<rootDir>/test/javascript/**/*.js'],
            transform: {},
            moduleNameMapper: {
                '^phpmyadmin/(.*)$': '<rootDir>/js/src/$1',
                '^@vendor/(.*)$': '<rootDir>/js/vendor/$1',
            },
            testEnvironment: 'jsdom',
        }
    ]
};
