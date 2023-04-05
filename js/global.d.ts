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
}

interface JQueryStatic {
    timepicker: JQueryUI.Datepicker;

    jqplot: any;
}
