/* eslint-env node */

const $ = require('jquery');
global.$ = $;
global.jQuery = $;
global.CommonParams = require('phpmyadmin/common');
global.AJAX = require('phpmyadmin/ajax');
global.Functions = require('phpmyadmin/functions');
global.Stickyfill = require('@vendor/stickyfill.min');
