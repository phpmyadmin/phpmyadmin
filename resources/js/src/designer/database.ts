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
    /**
     * @type {IDBDatabase|null}
     */
    var datastore = null;

    /**
     * @param {string} table
     * @return {IDBTransaction}
     */
    const getTransaction = function (table) {
        return datastore.transaction([table], 'readwrite');
    };

    /**
     * @param {string} table
     * @return {IDBObjectStore}
     */
    const getObjectStore = function (table) {
        var transaction = designerDB.getTransaction(table);
        var objStore = transaction.objectStore(table);

        return objStore;
    };

    /**
     * @param {IDBTransaction} transaction
     * @param {string} table
     * @return {IDBObjectStore}
     */
    const getCursorRequest = function (transaction, table) {
        var objStore = transaction.objectStore(table);
        var keyRange = IDBKeyRange.lowerBound(0);
        var cursorRequest = objStore.openCursor(keyRange);

        return cursorRequest;
    };

    /**
     * @param {Function} callback
     */
    const open = function (callback): void {
        var version = 1;
        var request = window.indexedDB.open('pma_designer', version);

        request.onupgradeneeded = function (e) {
            var db = (e.target as IDBRequest).result;
            (e.target as IDBRequest).transaction.onerror = designerDB.onerror;

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
            datastore = (e.target as IDBRequest).result;
            if (typeof callback === 'function') {
                callback(true);
            }
        };

        request.onerror = function () {
            ajaxShowMessage(window.Messages.strIndexedDBNotWorking, null, 'error');
        };
    };

    /**
     * @param {string} table
     * @param {string} id
     * @param {Function} callback
     */
    const loadObject = function (table, id, callback): void {
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
     * @param {string} table
     * @param {Function} callback
     */
    const loadAllObjects = function (table, callback): void {
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
     * @param {string} table
     * @param {Function} callback
     */
    const loadFirstObject = function (table, callback): void {
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
     * @param {string} table
     * @param {Object} obj
     * @param {Function} callback
     */
    const addObject = function (table, obj, callback = undefined): void {
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
     * @param {string} table
     * @param {string} id
     * @param {Function} callback
     */
    const deleteObject = function (table, id, callback = undefined): void {
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
    const onerror = function (e): void {
        // eslint-disable-next-line no-console
        console.log(e);
    };

    var designerDB = {
        getTransaction: getTransaction,
        getObjectStore: getObjectStore,
        getCursorRequest: getCursorRequest,
        open: open,
        loadObject: loadObject,
        loadAllObjects: loadAllObjects,
        loadFirstObject: loadFirstObject,
        addObject: addObject,
        deleteObject: deleteObject,
        onerror: onerror,
    };

    // Export the designerDB object.
    return designerDB;
}());

export { DesignerOfflineDB };
