import $ from 'jquery';
import { ajaxShowMessage } from '../modules/ajax-message.ts';
import { escapeHtml } from '../modules/functions/escape.ts';
import { DesignerConfig } from './config.ts';
import { DesignerOfflineDB } from './database.ts';
import { DesignerMove } from './move.ts';
import { DesignerObjects } from './objects.ts';

function showTablesInLandingPage (db) {
    DesignerPage.loadFirstPage(db, function (page) {
        if (page) {
            DesignerPage.loadHtmlForPage(page.pgNr);
            DesignerConfig.selectedPage = page.pgNr;
        } else {
            DesignerPage.showNewPageTables(true);
        }
    });
}

function saveToNewPage (db, pageName, tablePositions, callback) {
    DesignerPage.createNewPage(db, pageName, function (page) {
        if (page) {
            const tblCords = [];
            const saveCallback = function (id) {
                tblCords.push(id);
                if (tablePositions.length === tblCords.length) {
                    page.tblCords = tblCords;
                    DesignerOfflineDB.addObject('pdf_pages', page);
                }
            };

            for (let pos = 0; pos < tablePositions.length; pos++) {
                tablePositions[pos].pdfPgNr = page.pgNr;
                DesignerPage.saveTablePositions(tablePositions[pos], saveCallback);
            }

            if (typeof callback !== 'undefined') {
                callback(page);
            }
        }
    });
}

function saveToSelectedPage (db, pageId, pageName, tablePositions, callback) {
    DesignerPage.deletePage(pageId);
    DesignerPage.saveToNewPage(db, pageName, tablePositions, function (page) {
        if (typeof callback !== 'undefined') {
            callback(page);
        }

        DesignerConfig.selectedPage = page.pgNr;
    });
}

function createNewPage (db, pageName, callback) {
    const newPage = new DesignerObjects.PdfPage(db, pageName, []);
    DesignerOfflineDB.addObject('pdf_pages', newPage, function (pgNr) {
        newPage.pgNr = pgNr;
        if (typeof callback !== 'undefined') {
            callback(newPage);
        }
    });
}

function saveTablePositions (positions, callback) {
    DesignerOfflineDB.addObject('table_coords', positions, callback);
}

function createPageList (db, callback) {
    DesignerOfflineDB.loadAllObjects('pdf_pages', function (pages) {
        let html = '';
        for (let p = 0; p < pages.length; p++) {
            const page = pages[p];
            if (page.dbName === db) {
                html += '<option value="' + page.pgNr + '">';
                html += escapeHtml(page.pageDescr) + '</option>';
            }
        }

        if (typeof callback !== 'undefined') {
            callback(html);
        }
    });
}

function deletePage (pageId, callback = undefined) {
    DesignerOfflineDB.loadObject('pdf_pages', pageId, function (page) {
        if (page) {
            for (let i = 0; i < page.tblCords.length; i++) {
                DesignerOfflineDB.deleteObject('table_coords', page.tblCords[i]);
            }

            DesignerOfflineDB.deleteObject('pdf_pages', pageId, callback);
        }
    });
}

function loadFirstPage (db, callback) {
    DesignerOfflineDB.loadAllObjects('pdf_pages', function (pages) {
        let firstPage = null;
        for (let i = 0; i < pages.length; i++) {
            const page = pages[i];
            if (page.dbName === db) {
                // give preference to a page having same name as the db
                if (page.pageDescr === db) {
                    callback(page);

                    return;
                }

                if (firstPage === null) {
                    firstPage = page;
                }
            }
        }

        callback(firstPage);
    });
}

function showNewPageTables (check) {
    const allTables = ($('#id_scroll_tab').find('td input:checkbox') as JQuery<HTMLInputElement>);
    allTables.prop('checked', check);
    for (let tab = 0; tab < allTables.length; tab++) {
        const input = allTables[tab];
        if (input.value) {
            const element = document.getElementById(input.value);
            element.style.top = DesignerPage.getRandom(550, 20) + 'px';
            element.style.left = DesignerPage.getRandom(700, 20) + 'px';
            DesignerMove.visibleTab(input, input.value);
        }
    }

    DesignerConfig.selectedPage = -1;
    $('#page_name').text(window.Messages.strUntitled);
    DesignerMove.markUnsaved();
}

function loadHtmlForPage (pageId) {
    DesignerPage.showNewPageTables(true);
    DesignerPage.loadPageObjects(pageId, function (page, tblCords) {
        $('#name-panel').find('#page_name').text(page.pageDescr);
        let tableMissing = false;
        for (let t = 0; t < tblCords.length; t++) {
            const tbId = DesignerConfig.db + '.' + tblCords[t].tableName;
            const table = document.getElementById(tbId);
            if (table === null) {
                tableMissing = true;
                continue;
            }

            table.style.top = tblCords[t].y + 'px';
            table.style.left = tblCords[t].x + 'px';

            const checkbox = (document.getElementById('check_vis_' + tbId) as HTMLInputElement);
            checkbox.checked = true;
            DesignerMove.visibleTab(checkbox, checkbox.value);
        }

        DesignerMove.markSaved();
        if (tableMissing === true) {
            DesignerMove.markUnsaved();
            ajaxShowMessage(window.Messages.strSavedPageTableMissing);
        }

        DesignerConfig.selectedPage = page.pgNr;
    });
}

function loadPageObjects (pageId, callback) {
    DesignerOfflineDB.loadObject('pdf_pages', pageId, function (page) {
        const tblCords = [];
        const count = page.tblCords.length;
        for (let i = 0; i < count; i++) {
            DesignerOfflineDB.loadObject('table_coords', page.tblCords[i], function (tblCord) {
                tblCords.push(tblCord);
                if (tblCords.length === count) {
                    if (typeof callback !== 'undefined') {
                        callback(page, tblCords);
                    }
                }
            });
        }
    });
}

function getRandom (max, min) {
    const val = Math.random() * (max - min) + min;

    return Math.floor(val);
}

const DesignerPage = {
    showTablesInLandingPage: showTablesInLandingPage,
    saveToNewPage: saveToNewPage,
    saveToSelectedPage: saveToSelectedPage,
    createNewPage: createNewPage,
    saveTablePositions: saveTablePositions,
    createPageList: createPageList,
    deletePage: deletePage,
    loadFirstPage: loadFirstPage,
    showNewPageTables: showNewPageTables,
    loadHtmlForPage: loadHtmlForPage,
    loadPageObjects: loadPageObjects,
    getRandom: getRandom,
};

export { DesignerPage };
