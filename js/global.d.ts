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
    drawOpenLayers: () => any;
    variableNames: string[];

    sprintf(format: string, ...values: (string|number)[]): string;
}

interface JQuery {
    getPostData: () => string;

    sortableTable: (method: any) => any;

    noSelect: (p?: any) => any;

    menuResizer: (method: string|Function) => any;

    confirm: (question: string, url: string, callbackFn: Function, openCallback?: Function) => boolean;

    sortTable: (textSelector: string) => JQuery<HTMLElement>;

    filterByValue: (value: any) => any;

    uiTooltip(): JQuery;
    uiTooltip(methodName: 'destroy'): void;
    uiTooltip(methodName: 'disable'): void;
    uiTooltip(methodName: 'enable'): void;
    uiTooltip(methodName: 'open'): void;
    uiTooltip(methodName: 'close'): void;
    uiTooltip(methodName: 'widget'): JQuery;
    uiTooltip(methodName: string): JQuery;
    uiTooltip(options: JQueryUI.TooltipOptions): JQuery;
    uiTooltip(optionLiteral: string, optionName: string): any;
    uiTooltip(optionLiteral: string, options: JQueryUI.TooltipOptions): any;
    uiTooltip(optionLiteral: string, optionName: string, optionValue: any): JQuery;

    tablesorter: any;
}

interface JQueryStatic {
    timepicker: any;
    tablesorter: any;
    jqplot: any;
    uiTableFilter: any;
}
