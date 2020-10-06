"use strict";

/* eslint-disable no-unused-vars */

/**
 * Dummy implementation of the ajax page loader
 */
var AJAX = {
  registerOnload: function registerOnload(idx, func) {
    $(func);
  },
  registerTeardown: function registerTeardown(idx, func) {}
};