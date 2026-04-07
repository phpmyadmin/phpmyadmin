import { ajaxShowMessage } from '../modules/ajax-message.ts';

const designerTables = [
    {
        name: 'pdf_pages',
        key: 'pgNr',
        autoIncrement: true,
    },
    {
        name: 'table_coords',
        key: 'id',
        autoIncrement: true,
    },
];

const DesignerOfflineDB = (function () {
    /**
     * @type {IDBDatabase|null}
     */
    let datastore = null;

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
        const transaction = designerDB.getTransaction(table);
        const objStore = transaction.objectStore(table);

        return objStore;
    };

    /**
     * @param {IDBTransaction} transaction
     * @param {string} table
     * @return {IDBObjectStore}
     */
    const getCursorRequest = function (transaction, table) {
        const objStore = transaction.objectStore(table);
        const keyRange = IDBKeyRange.lowerBound(0);
        const cursorRequest = objStore.openCursor(keyRange);

        return cursorRequest;
    };

    /**
     * @param {Function} callback
     */
    const open = function (callback): void {
        const version = 1;
        const request = window.indexedDB.open('pma_designer', version);

        request.onupgradeneeded = function (e) {
            const db = (e.target as IDBRequest).result;
            (e.target as IDBRequest).transaction.onerror = designerDB.onerror;

            let t;
            for (t in designerTables) {
                if (db.objectStoreNames.contains(designerTables[t].name)) {
                    db.deleteObjectStore(designerTables[t].name);
                }
            }

            for (t in designerTables) {
                db.createObjectStore(designerTables[t].name, {
                    keyPath: designerTables[t].key,
                    autoIncrement: designerTables[t].autoIncrement,
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

        const objStore = designerDB.getObjectStore(table);
        const cursorRequest = objStore.get(parseInt(id));

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

        const transaction = designerDB.getTransaction(table);
        const cursorRequest = designerDB.getCursorRequest(transaction, table);
        const results = [];

        transaction.oncomplete = function () {
            callback(results);
        };

        cursorRequest.onsuccess = function (e) {
            const result = e.target.result;
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

        const transaction = designerDB.getTransaction(table);
        const cursorRequest = designerDB.getCursorRequest(transaction, table);
        let firstResult = null;

        transaction.oncomplete = function () {
            callback(firstResult);
        };

        cursorRequest.onsuccess = function (e) {
            const result = e.target.result;
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

        const objStore = designerDB.getObjectStore(table);
        const request = objStore.put(obj);

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

        const objStore = designerDB.getObjectStore(table);
        const request = objStore.delete(parseInt(id));

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

    const designerDB = {
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
