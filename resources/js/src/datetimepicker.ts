import $ from 'jquery';

function registerDatePickerTranslations () {
    'use strict';

    if (! $.datepicker) {
        return;
    }

    $.datepicker.regional[''].closeText = window.Messages.strCalendarClose;
    $.datepicker.regional[''].prevText = window.Messages.strCalendarPrevious;
    $.datepicker.regional[''].nextText = window.Messages.strCalendarNext;
    $.datepicker.regional[''].currentText = window.Messages.strCalendarCurrent;
    $.datepicker.regional[''].monthNames = [
        window.Messages.strMonthNameJan,
        window.Messages.strMonthNameFeb,
        window.Messages.strMonthNameMar,
        window.Messages.strMonthNameApr,
        window.Messages.strMonthNameMay,
        window.Messages.strMonthNameJun,
        window.Messages.strMonthNameJul,
        window.Messages.strMonthNameAug,
        window.Messages.strMonthNameSep,
        window.Messages.strMonthNameOct,
        window.Messages.strMonthNameNov,
        window.Messages.strMonthNameDec,
    ];

    $.datepicker.regional[''].monthNamesShort = [
        window.Messages.strMonthNameJanShort,
        window.Messages.strMonthNameFebShort,
        window.Messages.strMonthNameMarShort,
        window.Messages.strMonthNameAprShort,
        window.Messages.strMonthNameMayShort,
        window.Messages.strMonthNameJunShort,
        window.Messages.strMonthNameJulShort,
        window.Messages.strMonthNameAugShort,
        window.Messages.strMonthNameSepShort,
        window.Messages.strMonthNameOctShort,
        window.Messages.strMonthNameNovShort,
        window.Messages.strMonthNameDecShort,
    ];

    $.datepicker.regional[''].dayNames = [
        window.Messages.strDayNameSun,
        window.Messages.strDayNameMon,
        window.Messages.strDayNameTue,
        window.Messages.strDayNameWed,
        window.Messages.strDayNameThu,
        window.Messages.strDayNameFri,
        window.Messages.strDayNameSat,
    ];

    $.datepicker.regional[''].dayNamesShort = [
        window.Messages.strDayNameSunShort,
        window.Messages.strDayNameMonShort,
        window.Messages.strDayNameTueShort,
        window.Messages.strDayNameWedShort,
        window.Messages.strDayNameThuShort,
        window.Messages.strDayNameFriShort,
        window.Messages.strDayNameSatShort,
    ];

    $.datepicker.regional[''].dayNamesMin = [
        window.Messages.strDayNameSunMin,
        window.Messages.strDayNameMonMin,
        window.Messages.strDayNameTueMin,
        window.Messages.strDayNameWedMin,
        window.Messages.strDayNameThuMin,
        window.Messages.strDayNameFriMin,
        window.Messages.strDayNameSatMin,
    ];

    $.datepicker.regional[''].weekHeader = window.Messages.strWeekHeader;
    $.datepicker.regional[''].showMonthAfterYear = window.Messages.strMonthAfterYear === 'calendar-year-month';
    $.datepicker.regional[''].yearSuffix = window.Messages.strYearSuffix !== 'none' ? window.Messages.strYearSuffix : '';

    // @ts-ignore
    $.extend($.datepicker._defaults, $.datepicker.regional['']); // eslint-disable-line no-underscore-dangle
}

function registerTimePickerTranslations () {
    'use strict';

    if (! $.timepicker) {
        return;
    }

    $.timepicker.regional[''].timeText = window.Messages.strCalendarTime;
    $.timepicker.regional[''].hourText = window.Messages.strCalendarHour;
    $.timepicker.regional[''].minuteText = window.Messages.strCalendarMinute;
    $.timepicker.regional[''].secondText = window.Messages.strCalendarSecond;
    $.timepicker.regional[''].millisecText = window.Messages.strCalendarMillisecond;
    $.timepicker.regional[''].microsecText = window.Messages.strCalendarMicrosecond;
    $.timepicker.regional[''].timezoneText = window.Messages.strCalendarTimezone;

    // eslint-disable-next-line no-underscore-dangle
    $.extend($.timepicker._defaults, $.timepicker.regional['']);
}

registerDatePickerTranslations();
registerTimePickerTranslations();
