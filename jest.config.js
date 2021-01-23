/* eslint-env node */

module.exports = {
    coverageDirectory: '<rootDir>/build/javascript/',
    collectCoverageFrom: ['<rootDir>/js/src/**/*.js'],
    projects: [
        {
            verbose: true,
            setupFiles: ['<rootDir>/test/jest/test-env.js'],
            coveragePathIgnorePatterns: [
                '<rootDir>/node_modules/',
                '<rootDir>/js/vendor/',
            ],
            displayName: 'phpMyAdmin',
            testMatch: ['<rootDir>/test/javascript/**/*.js'],
            transform: {
                '^.+\\.js$': '<rootDir>/test/jest/file-transformer.js'
            },
            moduleNameMapper: {
                '^phpmyadmin/(.*)$': '<rootDir>/js/src/$1',
                '^@vendor/(.*)$': '<rootDir>/js/vendor/$1',
            },
        }
    ]
};
