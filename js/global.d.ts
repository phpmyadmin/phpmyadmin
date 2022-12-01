// libraries/classes/Controllers/JavaScriptMessagesController.php
declare var Messages: { [p: string]: string };

// templates/javascript/variables.twig
declare var firstDayOfCalendar: string;
declare var themeImagePath: string;
declare var mysqlDocTemplate: string;
declare var maxInputVars: number;

declare function sprintf(format: string, ...values: (string|number)[]): string;
