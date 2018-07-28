/**
 * FileHandler to handle loading of files on a particular page
 * and for particular user preferences
 */
import { AJAX } from '../ajax';
import { PhpToJsFileMapping } from '../consts/files';

export default class FileHandler {
    constructor () {
        this.fileMapping = PhpToJsFileMapping;
        this.indexPage = null;
    }
    /**
     * Method to initialise loading of files on first page
     *
     * @return void
     */
    init () {
        this.getIndexPage();
        this.addUserPreferenceFiles();
        this.loadCommonFiles();
        this.loafIndexFiles();
    }

    /**
     * Method to find the first loading page. Possible values
     * index.php?target=fileName.php
     * fileName.php
     *
     * @return void
     */
    getIndexPage () {
        let firstPage = window.location.pathname.replace('/', '').replace('.php', '');
        let indexStart = window.location.search.indexOf('target') + 7;
        let indexEnd = window.location.search.indexOf('.php');
        let indexPage = window.location.search.slice(indexStart, indexEnd);
        if (firstPage.toLocaleLowerCase() !== 'index') {
            this.indexPage = firstPage;
        } else {
            this.indexPage = indexPage;
        }
    }

    /**
     * Method to add user preference files.
     *
     * @return void
     */
    addUserPreferenceFiles () {
        /**
         * Add files required on the basis of user preference like ErrorReport,
         * PMA_Console, CodeMirror and so on.
         */
        return;
    }
    /**
     * Method to load Common files required for all the pages.
     *
     * @return void
     */
    loadCommonFiles () {
        /**
         * Adding common files for every page
         */
        for (let i in this.fileMapping.global) {
            AJAX.scriptHandler.add(this.fileMapping.global[i], 1);
        }
    }

    /**
     * Method to load page related files.
     *
     * @return void
     */
    loafIndexFiles () {
        if (typeof this.fileMapping[this.indexPage] !== 'undefined') {
            for (let i in this.fileMapping[this.indexPage]) {
                AJAX.scriptHandler.add(this.fileMapping[this.indexPage][i], 1);
            }
        }
    }
}
