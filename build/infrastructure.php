<?php

/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 09:55:52
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-15 11:17:04
 */

/**
 * 设置物理路径
 */

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

defined('PHPPACK_DIR') or define('PHPPACK_DIR', dirname(dirname(dirname(__FILE__))));

defined('ROOT_DIR') or define('ROOT_DIR', dirname(dirname(PHPPACK_DIR)));

defined('VENDOR_DIR') or define('VENDOR_DIR', ROOT_DIR . DS . 'vendor');