<?php

/* An autoloader for ReCaptcha\Foo classes. This should be require()d
 * by the user before attempting to instantiate any of the ReCaptcha
 * classes.
 */

spl_autoload_register(function ($class) {
    if (substr($class, 0, 10) !== 'ReCaptcha\\') {
      /* If the class does not lie under the "ReCaptcha" namespace,
       * then we can exit immediately.
       */
      return;
    }

    /* All of the classes have names like "ReCaptcha\Foo", so we need
     * to replace the backslashes with frontslashes if we want the
     * name to map directly to a location in the filesystem.
     */
    $class = str_replace('\\', '/', $class);

    /* First, check under the current directory. It is important that
     * we look here first, so that we don't waste time searching for
     * test classes in the common case.
     */
    $path = dirname(__FILE__).'/'.$class.'.php';
    if (is_readable($path)) {
        require_once $path;
    }

    /* If we didn't find what we're looking for already, maybe it's
     * a test class?
     */
    $path = dirname(__FILE__).'/../tests/'.$class.'.php';
    if (is_readable($path)) {
        require_once $path;
    }
});
