<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * session handling 
 * 
 * @see     http://www.php.net/session
 * @uses    session_name()
 * @uses    session_start()
 * @uses    session_regenerate_id()
 * @uses    session_id()
 * @uses    strip_tags()
 * @uses    ini_set()
 * @uses    version_compare()
 * @uses    PHP_VERSION
 */

// disable starting of sessions before all setings are done
ini_set( 'session.auto_start', false );

// cookies are safer
ini_set( 'session.use_cookies', true );

// but not all user allow cookies
ini_set( 'session.use_only_cookies', false );
ini_set( 'session.use_trans_sid', true );
ini_set( 'url_rewriter.tags',
    'a=href,frame=src,input=src,form=fakeentry,fieldset=' );
ini_set( 'arg_separator.output' , '&amp;' );

// delete session/cookies when browser is closed
ini_set( 'session.cookie_lifetime', 0 );

// warn but dont work with bug
ini_set( 'session.bug_compat_42', false );
ini_set( 'session.bug_compat_warn', true );

// use more secure session ids (with PHP 5)
if ( version_compare( PHP_VERSION, '5.0.0', 'ge' ) ) {
    ini_set( 'session.hash_function', 1 );
    ini_set( 'session.hash_bits_per_character', 6 );
}

// start the session
session_name( 'phpMyAdmin' );
session_start();

// prevent session fixation and XSS
if ( function_exists( 'session_regenerate_id' ) ) {
    session_regenerate_id( true );
} else {
    session_id( strip_tags( session_id() ) );
}
?>