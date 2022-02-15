function registerDatePickerTranslations () {
    'use strict';

    if (! $.datepicker) {
        return;
    }

    $.datepicker.regional[''].closeText = Messages.strCalendarClose;
    $.datepicker.regional[''].prevText = Messages.strCalendarPrevious;
    $.datepicker.regional[''].nextText = Messages.strCalendarNext;
    $.datepicker.regional[''].currentText = Messages.strCalendarCurrent;
    $.datepicker.regional[''].monthNames = [
        Messages.strMonthNameJan,
        Messages.strMonthNameFeb,
        Messages.strMonthNameMar,
        Messages.strMonthNameApr,
        Messages.strMonthNameMay,
        Messages.strMonthNameJun,
        Messages.strMonthNameJul,
        Messages.strMonthNameAug,
        Messages.strMonthNameSep,
        Messages.strMonthNameOct,
        Messages.strMonthNameNov,
        Messages.strMonthNameDec,
    ];
    $.datepicker.regional[''].monthNamesShort = [
        Messages.strMonthNameJanShort,
        Messages.strMonthNameFebShort,
        Messages.strMonthNameMarShort,
        Messages.strMonthNameAprShort,
        Messages.strMonthNameMayShort,
        Messages.strMonthNameJunShort,
        Messages.strMonthNameJulShort,
        Messages.strMonthNameAugShort,
        Messages.strMonthNameSepShort,
        Messages.strMonthNameOctShort,
        Messages.strMonthNameNovShort,
        Messages.strMonthNameDecShort,
    ];
    $.datepicker.regional[''].dayNames = [
        Messages.strDayNameSun,
        Messages.strDayNameMon,
        Messages.strDayNameTue,
        Messages.strDayNameWed,
        Messages.strDayNameThu,
        Messages.strDayNameFri,
        Messages.strDayNameSat,
    ];
    $.datepicker.regional[''].dayNamesShort = [
        Messages.strDayNameSunShort,
        Messages.strDayNameMonShort,
        Messages.strDayNameTueShort,
        Messages.strDayNameWedShort,
        Messages.strDayNameThuShort,
        Messages.strDayNameFriShort,
        Messages.strDayNameSatShort,
    ];
    $.datepicker.regional[''].dayNamesMin = [
        Messages.strDayNameSunMin,
        Messages.strDayNameMonMin,
        Messages.strDayNameTueMin,
        Messages.strDayNameWedMin,
        Messages.strDayNameThuMin,
        Messages.strDayNameFriMin,
        Messages.strDayNameSatMin,
    ];
    $.datepicker.regional[''].weekHeader = Messages.strWeekHeader;
    $.datepicker.regional[''].showMonthAfterYear = Messages.strMonthAfterYear === 'calendar-year-month';
    $.datepicker.regional[''].yearSuffix = Messages.strYearSuffix !== 'none' ? Messages.strYearSuffix : '';

    // eslint-disable-next-line no-underscore-dangle
    $.extend($.datepicker._defaults, $.datepicker.regional['']);
}

function registerTimePickerTranslations () {
    'use strict';

    if (! $.timepicker) {
        return;
    }

    $.timepicker.regional[''].timeText = Messages.strCalendarTime;
    $.timepicker.regional[''].hourText = Messages.strCalendarHour;
    $.timepicker.regional[''].minuteText = Messages.strCalendarMinute;
    $.timepicker.regional[''].secondText = Messages.strCalendarSecond;

    // eslint-disable-next-line no-underscore-dangle
    $.extend($.timepicker._defaults, $.timepicker.regional['']);
}

registerDatePickerTranslations();
registerTimePickerTranslations();
