const DesignerObjects = {
    PdfPage: function (dbName, pageDescr, tblCords) {
        // Do not set this.pgNr here. IndexedDB generates the key on put(),
        // and a null key makes put() fail (issue #12900).
        this.dbName = dbName;
        this.pageDescr = pageDescr;
        this.tblCords = tblCords;
    },
    TableCoordinate: function (dbName, tableName, pdfPgNr, x, y) {
        // Do not set this.id here. IndexedDB generates the key on put(),
        // and a null key makes put() fail (issue #12900).
        this.dbName = dbName;
        this.tableName = tableName;
        this.pdfPgNr = pdfPgNr;
        this.x = x;
        this.y = y;
    },
};

export { DesignerObjects };
