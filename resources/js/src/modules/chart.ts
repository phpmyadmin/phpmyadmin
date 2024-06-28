/**
 * Column type enumeration
 */
export const ColumnType = {
    STRING: 'string',
    NUMBER: 'number',
    BOOLEAN: 'boolean',
    DATE: 'date'
};

/**
 * The data table contains column information and data for the chart.
 */
export const DataTable = function () {
    const columns = [];
    let data = null;

    this.addColumn = function (type, name) {
        columns.push({
            'type': type,
            'name': name
        });
    };

    this.getColumns = function () {
        return columns;
    };

    this.setData = function (rows) {
        data = rows;
        fillMissingValues();
    };

    this.getData = function () {
        return data;
    };

    const fillMissingValues = function () {
        if (columns.length === 0) {
            throw new Error('Set columns first');
        }

        let row;
        for (let i = 0; i < data.length; i++) {
            row = data[i];
            if (row.length > columns.length) {
                row.splice(columns.length - 1, row.length - columns.length);
            } else if (row.length < columns.length) {
                for (let j = row.length; j < columns.length; j++) {
                    row.push(null);
                }
            }
        }
    };
};
