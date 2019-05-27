function PDFPage (dbName, pageDescr, tblCords) {
    // no dot set the auto increment before put() in the database
    // issue #12900
    // this.pg_nr = null;
    this.db_name = dbName;
    this.page_descr = pageDescr;
    this.tbl_cords = tblCords;
}

function TableCoordinate (dbName, tableName, pdfPgNr, x, y) {
    // no dot set the auto increment before put() in the database
    // issue #12900
    // this.id = null;
    this.db_name = dbName;
    this.table_name = tableName;
    this.pdf_pg_nr = pdfPgNr;
    this.x = x;
    this.y = y;
}
