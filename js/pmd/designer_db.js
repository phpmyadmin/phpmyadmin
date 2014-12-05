var designer_tables = [{name: "pdf_pages", key: "pg_nr", auto_inc: true},
                       {name: "table_coords", key: "id", auto_inc: true}];

var DesignerOfflineDB = (function () {
    var designerDB = {};
    var datastore = null;

    designerDB.open = function (callback) {
        var version = 1;
        var request = window.indexedDB.open("pma_designer", version);

        request.onupgradeneeded = function (e) {
            var db = e.target.result;
            e.target.transaction.onerror = designerDB.onerror;

            for (var t in designer_tables) {
                if (db.objectStoreNames.contains(designer_tables[t].name)) {
                    db.deleteObjectStore(designer_tables[t].name);
                }
            }

            for (var t in designer_tables) {
                db.createObjectStore(designer_tables[t].name, {
                    keyPath: designer_tables[t].key,
                    autoIncrement: designer_tables[t].auto_inc
                });
            }
        };

        request.onsuccess = function (e) {
            datastore = e.target.result;
            if (typeof callback !== 'undefined' && callback !== null) {
                callback(true);
            }
        };

        request.onerror = designerDB.onerror;
    };

    designerDB.loadObject = function (table, id, callback) {
        var db = datastore;
        var transaction = db.transaction([table], 'readwrite');
        var objStore = transaction.objectStore(table);
        var cursorRequest = objStore.get(parseInt(id));

        cursorRequest.onsuccess = function (e) {
            callback(e.target.result);
        };

        cursorRequest.onerror = designerDB.onerror;
    };

    designerDB.loadAllObjects = function (table, callback) {
        var db = datastore;
        var transaction = db.transaction([table], 'readwrite');
        var objStore = transaction.objectStore(table);
        var keyRange = IDBKeyRange.lowerBound(0);
        var cursorRequest = objStore.openCursor(keyRange);
        var results = [];

        transaction.oncomplete = function (e) {
            callback(results);
        };

        cursorRequest.onsuccess = function (e) {
            var result = e.target.result;
            if (!!result === false) {
                return;
            }
            results.push(result.value);
            result.continue();
        };

        cursorRequest.onerror = designerDB.onerror;
    };

    designerDB.loadFirstObject = function(table, callback) {
        var db = datastore;
        var transaction = db.transaction([table], 'readwrite');
        var objStore = transaction.objectStore(table);
        var keyRange = IDBKeyRange.lowerBound(0);
        var cursorRequest = objStore.openCursor(keyRange);
        var firstResult = null;

        transaction.oncomplete = function(e) {
            callback(firstResult);
        };

        cursorRequest.onsuccess = function(e) {
            var result = e.target.result;
            if (!!result === false) {
                return;
            }
            firstResult = result.value;
        };

        cursorRequest.onerror = designerDB.onerror;
    };

    designerDB.addObject = function(table, obj, callback) {
        var db = datastore;
        var transaction = db.transaction([table], 'readwrite');
        var objStore = transaction.objectStore(table);
        var request = objStore.put(obj);

        request.onsuccess = function(e) {
            if (typeof callback !== 'undefined' && callback !== null) {
                callback(e.currentTarget.result);
            }
        };

        request.onerror = designerDB.onerror;
    };

    designerDB.deleteObject = function(table, id, callback) {
        var db = datastore;
        var transaction = db.transaction([table], 'readwrite');
        var objStore = transaction.objectStore(table);
        var request = objStore.delete(parseInt(id));

        request.onsuccess = function(e) {
            if (typeof callback !== 'undefined' && callback !== null) {
                callback(true);
            }
        };

        request.onerror = designerDB.onerror;
    };

    designerDB.onerror = function(e) {
        console.log(e);
    };

    // Export the designerDB object.
    return designerDB;
}());
