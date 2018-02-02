<?php
/***************************************************************************
 *   Copyright (C) 2004-2009 by Konstantin V. Arkhipov                     *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU Lesser General Public License as        *
 *   published by the Free Software Foundation; either version 3 of the    *
 *   License, or (at your option) any later version.                       *
 *                                                                         *
 ***************************************************************************/

error_reporting(E_ALL | E_STRICT);
set_error_handler(function ($code, $string, $file, $line) {
    throw new BaseException($string . ' at ' . $file . ':' . $line, $code);
}, E_ALL | E_STRICT);

ignore_user_abort(true);
define('ONPHP_VERSION', 'master');
date_default_timezone_set('Europe/Moscow');

// file extensions
define('EXT_CLASS', '.class.php');
define('EXT_TPL', '.tpl.html');
define('EXT_MOD', '.inc.php');
define('EXT_HTML', '.html');
define('EXT_UNIT', '.unit.php');
define('EXT_LIB', '.php');

// paths
define('ONPHP_ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('ONPHP_CORE_PATH', ONPHP_ROOT_PATH . 'core' . DIRECTORY_SEPARATOR);
define('ONPHP_MAIN_PATH', ONPHP_ROOT_PATH . 'main' . DIRECTORY_SEPARATOR);
define('ONPHP_META_PATH', ONPHP_ROOT_PATH . 'meta' . DIRECTORY_SEPARATOR);
define('ONPHP_UI_PATH', ONPHP_ROOT_PATH . 'UI' . DIRECTORY_SEPARATOR);
define('ONPHP_LIB_PATH', ONPHP_ROOT_PATH . 'lib' . DIRECTORY_SEPARATOR);


//NOTE: disable by default
//see http://pgfoundry.org/docman/view.php/1000079/117/README.txt
//define('POSTGRES_IP4_ENABLED', true);

function onphpDefaultConstants() {
    // overridable constant, don't forget for trailing slash
    // also you may consider using /dev/shm/ for cache purposes
    if (!defined('ONPHP_TEMP_PATH'))
        define(
            'ONPHP_TEMP_PATH',
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'onPHP' . DIRECTORY_SEPARATOR
        );



    if (!defined('ONPHP_IPC_PERMS')) {
        define('ONPHP_IPC_PERMS', 0660);
    }
}
