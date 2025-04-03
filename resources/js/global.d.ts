interface Window {
    Messages: { [p: string]: string };
    maxInputVars: number;
    mysqlDocTemplate: string;
    themeImagePath: string;
    firstDayOfCalendar: string;
    ol: any;
    opera: any;
    zxcvbnts: any;
    msCrypto: any;
    u2f: any;
    variableNames: string[];
    bootstrap: typeof bootstrap;

    sprintf(format: string, ...values: (string|number)[]): string;
}

interface JQuery {
    sortableTable: (method: any) => any;

    menuResizer: (method: string|Function) => any;

    filterByValue: (value: any) => any;

    tablesorter: any;
}

interface JQueryStatic {
    timepicker: any;
    tablesorter: any;
    uiTableFilter: any;
}
