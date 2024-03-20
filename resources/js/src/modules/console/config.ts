import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.ts';
import { CommonParams } from '../common.ts';

/**
 * @link https://docs.phpmyadmin.net/en/latest/config.html#console-settings
 */
export const Config = {
    /**
     * @type {boolean}
     */
    StartHistory: false,
    /**
     * @type {boolean}
     */
    AlwaysExpand: false,
    /**
     * @type {boolean}
     */
    CurrentQuery: true,
    /**
     * @type {boolean}
     */
    EnterExecutes: false,
    /**
     * @type {boolean}
     */
    DarkTheme: false,
    /**
     * @type {'info'|'show'|'collapse'}
     */
    Mode: 'info',
    /**
     * @type {number}
     */
    Height: 92,
    /**
     * @type {boolean}
     */
    GroupQueries: false,
    /**
     * @type {'exec'|'time'|'count'}
     */
    OrderBy: 'exec',
    /**
     * @type {'asc'|'desc'}
     */
    Order: 'asc',

    init: function (dataset: DOMStringMap): void {
        this.StartHistory = dataset.startHistory === 'true';
        this.AlwaysExpand = dataset.alwaysExpand === 'true';
        this.CurrentQuery = dataset.currentQuery !== undefined ? dataset.currentQuery === 'true' : true;
        this.EnterExecutes = dataset.enterExecutes === 'true';
        this.DarkTheme = dataset.darkTheme === 'true';
        this.Mode = dataset.mode === 'show' || dataset.mode === 'collapse' ? dataset.mode : 'info';
        const height = Number(dataset.height);
        this.Height = height > 0 ? height : 92;
        this.GroupQueries = dataset.groupQueries === 'true';
        this.OrderBy = dataset.orderBy === 'time' || dataset.orderBy === 'count' ? dataset.orderBy : 'exec';
        this.Order = dataset.order === 'desc' ? 'desc' : 'asc';
    },

    /**
     * @param {'StartHistory'|'AlwaysExpand'|'CurrentQuery'|'EnterExecutes'|'DarkTheme'|'Mode'|'Height'|'GroupQueries'|'OrderBy'|'Order'} key
     * @param {boolean|string|number} value
     */
    set: function (key, value): void {
        this[key] = value;
        setConfigValue(key, value);
    },

    /**
     * Used for update console config
     */
    update: function (): void {
        this.set('AlwaysExpand', !! (document.getElementById('consoleOptionsAlwaysExpandCheckbox') as HTMLInputElement).checked);
        this.set('StartHistory', !! (document.getElementById('consoleOptionsStartHistoryCheckbox') as HTMLInputElement).checked);
        this.set('CurrentQuery', !! (document.getElementById('consoleOptionsCurrentQueryCheckbox') as HTMLInputElement).checked);
        this.set('EnterExecutes', !! (document.getElementById('consoleOptionsEnterExecutesCheckbox') as HTMLInputElement).checked);
        this.set('DarkTheme', !! (document.getElementById('consoleOptionsDarkThemeCheckbox') as HTMLInputElement).checked);
        /* Setting the dark theme of the console*/
        const consoleContent = document.getElementById('pma_console').querySelector('.content');
        if (this.DarkTheme) {
            consoleContent.classList.add('console_dark_theme');
        } else {
            consoleContent.classList.remove('console_dark_theme');
        }
    }
};

/**
 * @param {'StartHistory'|'AlwaysExpand'|'CurrentQuery'|'EnterExecutes'|'DarkTheme'|'Mode'|'Height'|'GroupQueries'|'OrderBy'|'Order'} key
 * @param {boolean|string|number} value
 */
function setConfigValue (key, value): void {
    // Updating value in local storage.
    const serialized = JSON.stringify(value);
    localStorage.setItem('Console/' + key, serialized);

    $.ajax({
        url: 'index.php?route=/console/update-config',
        type: 'POST',
        dataType: 'json',
        data: {
            'ajax_request': true,
            key: key,
            server: CommonParams.get('server'),
            value: serialized,
        },
        success: function (data) {
            if (data.success !== true) {
                // Try to find a message to display
                if (data.error || data.message) {
                    ajaxShowMessage(data.error || data.message);
                }
            }
        }
    });
}
