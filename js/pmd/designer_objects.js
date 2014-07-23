function PDFPage(db_name, page_descr, tbl_cords)
{
    if (page_descr == null) {
        this.pg_nr = db_name.pg_nr;
        this.db_name = db_name.db_name;
        this.page_descr = db_name.page_descr;
        this.tbl_cords = db_name.tbl_cords;
    } else {
        this.pg_nr;
        this.db_name = db_name;
        this.page_descr = page_descr;
        this.tbl_cords = tbl_cords;
    }
}

function TableCoordinate(obj)
{
    this.id = obj.id;
    this.db_name = obj.db_name;
    this.table_name = obj.table_name;
    this.pdf_pg_nr = obj.pdf_pg_nr;
    this.x = obj.x;
    this.y = obj.y;
}

function TableCoordinate(db_name, table_name, pdf_pg_nr, x, y)
{
    this.id;
    this.db_name = db_name;
    this.table_name = table_name;
    this.pdf_pg_nr = pdf_pg_nr;
    this.x = x;
    this.y = y;
}
