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

    setStartHistory: function (value: boolean): void {
        this.StartHistory = value;
        setConfigValue('StartHistory', value);
    },

    setAlwaysExpand: function (value: boolean): void {
        this.AlwaysExpand = value;
        setConfigValue('AlwaysExpand', value);
    },

    setCurrentQuery: function (value: boolean): void {
        this.CurrentQuery = value;
        setConfigValue('CurrentQuery', value);
    },

    setEnterExecutes: function (value: boolean): void {
        this.EnterExecutes = value;
        setConfigValue('EnterExecutes', value);
    },

    setDarkTheme: function (value: boolean): void {
        this.DarkTheme = value;
        setConfigValue('DarkTheme', value);
    },

    setMode: function (value: 'info'|'show'|'collapse'): void {
        this.Mode = value;
        setConfigValue('Mode', value);
    },

    setHeight: function (value: number): void {
        this.Height = value;
        setConfigValue('Height', value);
    },

    setGroupQueries: function (value: boolean): void {
        this.GroupQueries = value;
        setConfigValue('GroupQueries', value);
    },

    setOrderBy: function (value: 'exec'|'time'|'count'): void {
        this.OrderBy = value;
        setConfigValue('OrderBy', value);
    },

    setOrder: function (value: 'asc'|'desc'): void {
        this.Order = value;
        setConfigValue('Order', value);
    },
};

/**
 * @param {'StartHistory'|'AlwaysExpand'|'CurrentQuery'|'EnterExecutes'|'DarkTheme'|'Mode'|'Height'|'GroupQueries'|'OrderBy'|'Order'} key
 * @param {boolean|string|number} value
 */
function setConfigValue (key: string, value: boolean|number|string): void {
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
