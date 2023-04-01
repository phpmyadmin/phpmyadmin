import { ajaxShowMessage } from '../modules/ajax-message.ts';

var designerTables = [
    {
        name: 'pdf_pages',
        key: 'pgNr',
        autoIncrement: true
    },
    {
        name: 'table_coords',
        key: 'id',
        autoIncrement: true
    }
];

var DesignerOfflineDB = (function () {
    var designerDB = {};

    /**
     * @type {IDBDatabase|null}
     */
    var datastore = null;

    /**
     * @param {String} table
     * @return {IDBTransaction}
     */
    designerDB.getTransaction = function (table) {
        return datastore.transaction([table], 'readwrite');
    };

    /**
     * @param {String} table
     * @return {IDBObjectStore}
     */
    designerDB.getObjectStore = function (table) {
        var transaction = designerDB.getTransaction(table);
        var objStore = transaction.objectStore(table);
        return objStore;
    };

    /**
     * @param {IDBTransaction} transaction
     * @param {String} table
     * @return {IDBObjectStore}
     */
    designerDB.getCursorRequest = function (transaction, table) {
        var objStore = transaction.objectStore(table);
        var keyRange = IDBKeyRange.lowerBound(0);
        var cursorRequest = objStore.openCursor(keyRange);
        return cursorRequest;
    };

    /**
     * @param {Function} callback
     */
    designerDB.open = function (callback): void {
        var version = 1;
        var request = window.indexedDB.open('pma_designer', version);

        request.onupgradeneeded = function (e) {
            var db = e.target.result;
            e.target.transaction.onerror = designerDB.onerror;

            var t;
            for (t in designerTables) {
                if (db.objectStoreNames.contains(designerTables[t].name)) {
                    db.deleteObjectStore(designerTables[t].name);
                }
            }

            for (t in designerTables) {
                db.createObjectStore(designerTables[t].name, {
                    keyPath: designerTables[t].key,
                    autoIncrement: designerTables[t].autoIncrement
                });
            }
        };

        request.onsuccess = function (e) {
            datastore = e.target.result;
            if (typeof callback === 'function') {
                callback(true);
            }
        };

        request.onerror = function () {
            ajaxShowMessage(window.Messages.strIndexedDBNotWorking, null, 'error');
        };
    };

    /**
     * @param {String} table
     * @param {String} id
     * @param {Function} callback
     */
    designerDB.loadObject = function (table, id, callback): void {
        if (datastore === null) {
            ajaxShowMessage(window.Messages.strIndexedDBNotWorking, null, 'error');
            return;
        }

        var objStore = designerDB.getObjectStore(table);
        var cursorRequest = objStore.get(parseInt(id));

        cursorRequest.onsuccess = function (e) {
            callback(e.target.result);
        };

        cursorRequest.onerror = designerDB.onerror;
    };

    /**
     * @param {String} table
     * @param {Function} callback
     */
    designerDB.loadAllObjects = function (table, callback): void {
        if (datastore === null) {
            ajaxShowMessage(window.Messages.strIndexedDBNotWorking, null, 'error');
            return;
        }

        var transaction = designerDB.getTransaction(table);
        var cursorRequest = designerDB.getCursorRequest(transaction, table);
        var results = [];

        transaction.oncomplete = function () {
            callback(results);
        };

        cursorRequest.onsuccess = function (e) {
            var result = e.target.result;
            if (Boolean(result) === false) {
                return;
            }
            results.push(result.value);
            result.continue();
        };

        cursorRequest.onerror = designerDB.onerror;
    };

    /**
     * @param {String} table
     * @param {Function} callback
     */
    designerDB.loadFirstObject = function (table, callback): void {
        if (datastore === null) {
            ajaxShowMessage(window.Messages.strIndexedDBNotWorking, null, 'error');
            return;
        }

        var transaction = designerDB.getTransaction(table);
        var cursorRequest = designerDB.getCursorRequest(transaction, table);
        var firstResult = null;

        transaction.oncomplete = function () {
            callback(firstResult);
        };

        cursorRequest.onsuccess = function (e) {
            var result = e.target.result;
            if (Boolean(result) === false) {
                return;
            }
            firstResult = result.value;
        };

        cursorRequest.onerror = designerDB.onerror;
    };

    /**
     * @param {String} table
     * @param {Object} obj
     * @param {Function} callback
     */
    designerDB.addObject = function (table, obj, callback): void {
        if (datastore === null) {
            ajaxShowMessage(window.Messages.strIndexedDBNotWorking, null, 'error');
            return;
        }

        var objStore = designerDB.getObjectStore(table);
        var request = objStore.put(obj);

        request.onsuccess = function (e) {
            if (typeof callback === 'function') {
                callback(e.currentTarget.result);
            }
        };

        request.onerror = designerDB.onerror;
    };

    /**
     * @param {String} table
     * @param {String} id
     * @param {Function} callback
     */
    designerDB.deleteObject = function (table, id, callback): void {
        if (datastore === null) {
            ajaxShowMessage(window.Messages.strIndexedDBNotWorking, null, 'error');
            return;
        }

        var objStore = designerDB.getObjectStore(table);
        var request = objStore.delete(parseInt(id));

        request.onsuccess = function () {
            if (typeof callback === 'function') {
                callback(true);
            }
        };

        request.onerror = designerDB.onerror;
    };

    /**
     * @param {Error} e
     */
    designerDB.onerror = function (e): void {
        // eslint-disable-next-line no-console
        console.log(e);
    };

    // Export the designerDB object.
    return designerDB;
}());

export { DesignerOfflineDB };
