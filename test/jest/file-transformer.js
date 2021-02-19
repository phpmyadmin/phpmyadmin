/* eslint-env node */

module.exports = {
    process (sourceContents, filename) {
        // The file is a source file and can contain @test-module annotations
        if (filename.indexOf('js/src') !== -1) {
            if (sourceContents.includes('@test-module')) {
                // Annotation detected, add some code to the source file
                const moduleName = sourceContents.match(/@test-module (.*)/)[1];
                // eslint-disable-next-line no-param-reassign
                sourceContents += '\r\nmodule.exports = ' + moduleName + ';\r\n';
            }
            return sourceContents;
        }
        return sourceContents;
    },
};
