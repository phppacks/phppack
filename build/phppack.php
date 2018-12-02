<?php
namespace phppacks\phppack;
/**
 * This7 Frame
 * @Author: else
 * @Date:   2018-01-11 14:04:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-15 11:11:00
 */

class phppack {

    /**
     * 静态链接
     * @var [type]
     */
    protected static $link;

    /**
     * 单例调用
     * @return [type] [description]
     */
    protected static function single($module) {
        if (!isset(self::$link[$module])) {
            $class               = __NAMESPACE__ . '\module\\' . $module;
            self::$link[$module] = new $class();
        }
        return self::$link[$module];
    }

    public function __call($method, $params) {
        return call_user_func_array([self::single($method), 'bootstrap'], $params);
    }

    public static function __callStatic($name, $arguments) {
        return call_user_func_array([static::single(), $name], $arguments);
    }
}