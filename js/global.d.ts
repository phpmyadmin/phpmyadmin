// libraries/classes/Controllers/JavaScriptMessagesController.php
declare var Messages: { [p: string]: string };

// templates/javascript/variables.twig
declare var firstDayOfCalendar: string;
declare var themeImagePath: string;
declare var mysqlDocTemplate: string;
declare var maxInputVars: number;

declare function sprintf(format: string, ...values: (string|number)[]): string;

interface Window {
    ol: any;
    opera: any;
    zxcvbnts: any;
    msCrypto: any;
    drawOpenLayers: () => any;
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
