function PDFPage (db_name, page_descr, tbl_cords) {
    this.pg_nr = null;
    this.db_name = db_name;
    this.page_descr = page_descr;
    this.tbl_cords = tbl_cords;
}

function TableCoordinate (db_name, table_name, pdf_pg_nr, x, y) {
    this.id = null;
    this.db_name = db_name;
    this.table_name = table_name;
    this.pdf_pg_nr = pdf_pg_nr;
    this.x = x;
    this.y = y;
}
