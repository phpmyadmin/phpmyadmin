import $ from 'jquery';
import { ajaxShowMessage } from '../ajax-message.ts';
import { CommonParams } from '../common.ts';
import { escapeHtml } from '../functions/escape.ts';

/**
 * @link https://docs.phpmyadmin.net/en/latest/config.html#console-settings
 */
export default class Config {
    startHistory: boolean;

    alwaysExpand: boolean;

    currentQuery: boolean;

    enterExecutes: boolean;

    darkTheme: boolean;

    mode: 'info'|'show'|'collapse';

    height: number;

    groupQueries: boolean;

    orderBy: 'exec'|'time'|'count';

    order: 'asc'|'desc';

    constructor (
        startHistory: boolean,
        alwaysExpand: boolean,
        currentQuery: boolean,
        enterExecutes: boolean,
        darkTheme: boolean,
        mode: 'info'|'show'|'collapse',
        height: number,
        groupQueries: boolean,
        orderBy: 'exec'|'time'|'count',
        order: 'asc'|'desc',
    ) {
        this.startHistory = startHistory;
        this.alwaysExpand = alwaysExpand;
        this.currentQuery = currentQuery;
        this.enterExecutes = enterExecutes;
        this.darkTheme = darkTheme;
        this.mode = mode;
        this.height = height;
        this.groupQueries = groupQueries;
        this.orderBy = orderBy;
        this.order = order;
    }

    static createFromDataset (dataset: DOMStringMap): Config {
        const height = Number(dataset.height);

        return new this(
            dataset.startHistory === 'true',
            dataset.alwaysExpand === 'true',
            dataset.currentQuery !== undefined ? dataset.currentQuery === 'true' : true,
            dataset.enterExecutes === 'true',
            dataset.darkTheme === 'true',
            dataset.mode === 'show' || dataset.mode === 'collapse' ? dataset.mode : 'info',
            height > 0 ? height : 92,
            dataset.groupQueries === 'true',
            dataset.orderBy === 'time' || dataset.orderBy === 'count' ? dataset.orderBy : 'exec',
            dataset.order === 'desc' ? 'desc' : 'asc',
        );
    }

    setStartHistory (value: boolean): void {
        this.startHistory = value;
        setConfigValue('StartHistory', value);
    }

    setAlwaysExpand (value: boolean): void {
        this.alwaysExpand = value;
        setConfigValue('AlwaysExpand', value);
    }

    setCurrentQuery (value: boolean): void {
        this.currentQuery = value;
        setConfigValue('CurrentQuery', value);
    }

    setEnterExecutes (value: boolean): void {
        this.enterExecutes = value;
        setConfigValue('EnterExecutes', value);
    }

    setDarkTheme (value: boolean): void {
        this.darkTheme = value;
        setConfigValue('DarkTheme', value);
    }

    setMode (value: 'info'|'show'|'collapse'): void {
        this.mode = value;
        setConfigValue('Mode', value);
    }

    setHeight (value: number): void {
        this.height = value;
        setConfigValue('Height', value);
    }

    setGroupQueries (value: boolean): void {
        this.groupQueries = value;
        setConfigValue('GroupQueries', value);
    }

    setOrderBy (value: 'exec'|'time'|'count'): void {
        this.orderBy = value;
        setConfigValue('OrderBy', value);
    }

    setOrder (value: 'asc'|'desc'): void {
        this.order = value;
        setConfigValue('Order', value);
    }
}

/**
 * @param {'StartHistory'|'AlwaysExpand'|'CurrentQuery'|'EnterExecutes'|'DarkTheme'|'Mode'|'Height'|'GroupQueries'|'OrderBy'|'Order'} key
 * @param {boolean|string|number} value
 */
function setConfigValue (key: string, value: boolean|number|string): void {
    $.post(
        'index.php?route=/console/update-config',
        {
            'ajax_request': true,
            server: CommonParams.get('server'),
            key: key,
            value: value,
        },
    ).fail(function (data) {
        const message = '<div class="alert alert-danger" role="alert">' + escapeHtml(data.responseJSON.error) + '</div>';
        ajaxShowMessage(message, false);
    });
}
