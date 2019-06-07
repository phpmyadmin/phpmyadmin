// eslint-disable-next-line no-unused-vars
var DesignerObjects = {
    PdfPage: function (dbName, pageDescr, tblCords) {
        // eslint-disable-next-line no-unused-vars
        var pgNr;
        this.dbName = dbName;
        this.pageDescr = pageDescr;
        this.tblCords = tblCords;
    },
    TableCoordinate: function (dbName, tableName, pdfPgNr, x, y) {
        this.dbName = dbName;
        this.tableName = tableName;
        this.pdfPgNr = pdfPgNr;
        this.x = x;
        this.y = y;
    }
};
