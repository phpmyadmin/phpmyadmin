//! annyang
//! version : 1.5.0
//! author  : Tal Ater @TalAter
//! license : MIT
//! https://www.TalAter.com/annyang/

(function (undefined) {
  "use strict";

  /**
   * # Quick Tutorial, Intro and Demos
   *
   * The quickest way to get started is to visit the [annyang homepage](https://www.talater.com/annyang/).
   *
   * For a more in-depth look at annyang, read on.
   *
   * # API Reference
   */

  // Save a reference to the global object (window in the browser)
  var root = this;

  // Get the SpeechRecognition object, while handling browser prefixes
  var SpeechRecognition = root.SpeechRecognition ||
                          root.webkitSpeechRecognition ||
                          root.mozSpeechRecognition ||
                          root.msSpeechRecognition ||
                          root.oSpeechRecognition;

  // Check browser support
  // This is done as early as possible, to make it as fast as possible for unsupported browsers
  if (!SpeechRecognition) {
    root.annyang = null;
    return undefined;
  }

  var commandsList = [];
  var recognition;
  var callbacks = { start: [], error: [], end: [], result: [], resultMatch: [], resultNoMatch: [], errorNetwork: [], errorPermissionBlocked: [], errorPermissionDenied: [] };
  var autoRestart;
  var lastStartedAt = 0;
  var debugState = false;
  var debugStyle = 'font-weight: bold; color: #00f;';

  // The command matching code is a modified version of Backbone.Router by Jeremy Ashkenas, under the MIT license.
  var optionalParam = /\s*\((.*?)\)\s*/g;
  var optionalRegex = /(\(\?:[^)]+\))\?/g;
  var namedParam    = /(\(\?)?:\w+/g;
  var splatParam    = /\*\w+/g;
  var escapeRegExp  = /[\-{}\[\]+?.,\\\^$|#]/g;
  var commandToRegExp = function(command) {
    command = command.replace(escapeRegExp, '\\$&')
                  .replace(optionalParam, '(?:$1)?')
                  .replace(namedParam, function(match, optional) {
                    return optional ? match : '([^\\s]+)';
                  })
                  .replace(splatParam, '(.*?)')
                  .replace(optionalRegex, '\\s*$1?\\s*');
    return new RegExp('^' + command + '$', 'i');
  };

  // This method receives an array of callbacks to iterate over, and invokes each of them
  var invokeCallbacks = function(callbacks) {
    callbacks.forEach(function(callback) {
      callback.callback.apply(callback.context);
    });
  };

  var initIfNeeded = function() {
    if (!isInitialized()) {
      root.annyang.init({}, false);
    }
  };

  var isInitialized = function() {
    return recognition !== undefined;
  };

  root.annyang = {

    /**
     * Initialize annyang with a list of commands to recognize.
     *
     * ### Examples:
     *
     *     var commands = {'hello :name': helloFunction};
     *     var commands2 = {'hi': helloFunction};
     *
     *     // initialize annyang, overwriting any previously added commands
     *     annyang.init(commands, true);
     *     // adds an additional command without removing the previous commands
     *     annyang.init(commands2, false);
     *
     * As of v1.1.0 it is no longer required to call init(). Just start() listening whenever you want, and addCommands() whenever, and as often as you like.
     *
     * @param {Object} commands - Commands that annyang should listen to
     * @param {Boolean} [resetCommands=true] - Remove all commands before initializing?
     * @method init
     * @deprecated
     * @see [Commands Object](#commands-object)
     */
    init: function(commands, resetCommands) {

      // resetCommands defaults to true
      if (resetCommands === undefined) {
        resetCommands = true;
      } else {
        resetCommands = !!resetCommands;
      }

      // Abort previous instances of recognition already running
      if (recognition && recognition.abort) {
        recognition.abort();
      }

      // initiate SpeechRecognition
      recognition = new SpeechRecognition();

      // Set the max number of alternative transcripts to try and match with a command
      recognition.maxAlternatives = 5;

      // In HTTPS, turn off continuous mode for faster results.
      // In HTTP,  turn on  continuous mode for much slower results, but no repeating security notices
      recognition.continuous = root.location.protocol === 'http:';

      // Sets the language to the default 'en-US'. This can be changed with annyang.setLanguage()
      recognition.lang = 'en-US';

      recognition.onstart   = function()      { invokeCallbacks(callbacks.start); };

      recognition.onerror   = function(event) {
        invokeCallbacks(callbacks.error);
        switch (event.error) {
        case 'network':
          invokeCallbacks(callbacks.errorNetwork);
          break;
        case 'not-allowed':
        case 'service-not-allowed':
          // if permission to use the mic is denied, turn off auto-restart
          autoRestart = false;
          // determine if permission was denied by user or automatically.
          if (new Date().getTime()-lastStartedAt < 200) {
            invokeCallbacks(callbacks.errorPermissionBlocked);
          } else {
            invokeCallbacks(callbacks.errorPermissionDenied);
          }
          break;
        }
      };

      recognition.onend     = function() {
        invokeCallbacks(callbacks.end);
        // annyang will auto restart if it is closed automatically and not by user action.
        if (autoRestart) {
          // play nicely with the browser, and never restart annyang automatically more than once per second
          var timeSinceLastStart = new Date().getTime()-lastStartedAt;
          if (timeSinceLastStart < 1000) {
            setTimeout(root.annyang.start, 1000-timeSinceLastStart);
          } else {
            root.annyang.start();
          }
        }
      };

      recognition.onresult  = function(event) {
        invokeCallbacks(callbacks.result);
        var results = event.results[event.resultIndex];
        var commandText;
        // go over each of the 5 results and alternative results received (we've set maxAlternatives to 5 above)
        for (var i = 0; i<results.length; i++) {
          // the text recognized
          commandText = results[i].transcript.trim();
          if (debugState) {
            root.console.log('Speech recognized: %c'+commandText, debugStyle);
          }

          // try and match recognized text to one of the commands on the list
          for (var j = 0, l = commandsList.length; j < l; j++) {
            var result = commandsList[j].command.exec(commandText);
            if (result) {
              var parameters = result.slice(1);
              if (debugState) {
                root.console.log('command matched: %c'+commandsList[j].originalPhrase, debugStyle);
                if (parameters.length) {
                  root.console.log('with parameters', parameters);
                }
              }
              // execute the matched command
              commandsList[j].callback.apply(this, parameters);
              invokeCallbacks(callbacks.resultMatch);
              return true;
            }
          }
        }
        invokeCallbacks(callbacks.resultNoMatch);
        return false;
      };

      // build commands list
      if (resetCommands) {
        commandsList = [];
      }
      if (commands.length) {
        this.addCommands(commands);
      }
    },

    /**
     * Start listening.
     * It's a good idea to call this after adding some commands first, but not mandatory.
     *
     * Receives an optional options object which supports the following options:
     * - `autoRestart` (Boolean, default: true) Should annyang restart itself if it is closed indirectly, because of silence or window conflicts?
     * - `continuous`  (Boolean, default: undefined) Allow forcing continuous mode on or off. Annyang is pretty smart about this, so only set this if you know what you're doing.
     *
     * ### Examples:
     *     // Start listening, don't restart automatically
     *     annyang.start({ autoRestart: false });
     *     // Start listening, don't restart automatically, stop recognition after first phrase recognized
     *     annyang.start({ autoRestart: false, continuous: false });
     *
     * @param {Object} [options] - Optional options.
     * @method start
     */
    start: function(options) {
      initIfNeeded();
      options = options || {};
      if (options.autoRestart !== undefined) {
        autoRestart = !!options.autoRestart;
      } else {
        autoRestart = true;
      }
      if (options.continuous !== undefined) {
        recognition.continuous = !!options.continuous;
      }

      lastStartedAt = new Date().getTime();
      recognition.start();
    },

    /**
     * Stop listening.
     *
     * @method abort
     */
    abort: function() {
      autoRestart = false;
      if (isInitialized) {
        recognition.abort();
      }
    },

    /**
     * Turn on output of debug messages to the console. Ugly, but super-handy!
     *
     * @param {Boolean} [newState=true] - Turn on/off debug messages
     * @method debug
     */
    debug: function(newState) {
      if (arguments.length > 0) {
        debugState = !!newState;
      } else {
        debugState = true;
      }
    },

    /**
     * Set the language the user will speak in. If this method is not called, defaults to 'en-US'.
     *
     * @param {String} language - The language (locale)
     * @method setLanguage
     * @see [Languages](#languages)
     */
    setLanguage: function(language) {
      initIfNeeded();
      recognition.lang = language;
    },

    /**
     * Add commands that annyang will respond to. Similar in syntax to init(), but doesn't remove existing commands.
     *
     * ### Examples:
     *
     *     var commands = {'hello :name': helloFunction, 'howdy': helloFunction};
     *     var commands2 = {'hi': helloFunction};
     *
     *     annyang.addCommands(commands);
     *     annyang.addCommands(commands2);
     *     // annyang will now listen to all three commands
     *
     *
     * @param {Object} commands - Commands that annyang should listen to
     * @method addCommands
     * @see [Commands Object](#commands-object)
     */
    addCommands: function(commands) {
      var cb,
          command;

      initIfNeeded();

      for (var phrase in commands) {
        if (commands.hasOwnProperty(phrase)) {
          cb = root[commands[phrase]] || commands[phrase];
          if (typeof cb !== 'function') {
            continue;
          }
          //convert command to regex
          command = commandToRegExp(phrase);

          commandsList.push({ command: command, callback: cb, originalPhrase: phrase });
        }
      }
      if (debugState) {
        root.console.log('Commands successfully loaded: %c'+commandsList.length, debugStyle);
      }
    },

    /**
     * Remove existing commands. Called with a single phrase, array of phrases, or methodically. Pass no params to remove all commands.
     *
     * ### Examples:
     *
     *     var commands = {'hello': helloFunction, 'howdy': helloFunction, 'hi': helloFunction};
     *
     *     // Remove all existing commands
     *     annyang.removeCommands();
     *
     *     // Add some commands
     *     annyang.addCommands(commands);
     *
     *     // Don't respond to hello
     *     annyang.removeCommands('hello');
     *
     *     // Don't respond to howdy or hi
     *     annyang.removeCommands(['howdy', 'hi']);
     *
     * @param {String|Array|Undefined} [commandsToRemove] - Commands to remove
     * @method removeCommands
     */
    removeCommands: function(commandsToRemove) {
      if (commandsToRemove === undefined) {
        commandsList = [];
        return;
      }
      commandsToRemove = Array.isArray(commandsToRemove) ? commandsToRemove : [commandsToRemove];
      commandsList = commandsList.filter(function(command) {
        for (var i = 0; i<commandsToRemove.length; i++) {
          if (commandsToRemove[i] === command.originalPhrase) {
            return false;
          }
        }
        return true;
      });
    },

    /**
     * Add a callback function to be called in case one of the following events happens:
     *
     * start, error, end, result, resultMatch, resultNoMatch, errorNetwork, errorPermissionBlocked, errorPermissionDenied.
     *
     * ### Examples:
     *
     *     annyang.addCallback('error', function () {
     *       $('.myErrorText').text('There was an error!');
     *     });
     *
     *     // pass local context to a global function called notConnected
     *     annyang.addCallback('errorNetwork', notConnected, this);
     *
     * @param {String} type - Name of event that will trigger this callback
     * @param {Function} callback - The function to call when event is triggered
     * @param {Object} [context] - Optional context for the callback function
     * @method addCallback
     */
    addCallback: function(type, callback, context) {
      if (callbacks[type]  === undefined) {
        return;
      }
      var cb = root[callback] || callback;
      if (typeof cb !== 'function') {
        return;
      }
      callbacks[type].push({callback: cb, context: context || this});
    }
  };

}).call(this);