<?php

/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 09:55:52
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-15 11:33:34
 */
if (!function_exists('__PHPPACK_ERROR')) {
    /**
     * 错误调试信息
     * @param  string  $msg  错误提示
     * @param  integer $code 错误编码
     * @return [type]        [description]
     */
    function __PHPPACK_ERROR($msg = "未知错误", $code = -1) {
        exit($code . ":" . $msg);
    }
}

if (!function_exists('to_json')) {
    /**
     * 数组转JSON
     * @param  array  $array 数组数据
     * @return json          返回JSON数据
     */
    function to_json($array = array()) {
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

}

if (!function_exists('to_array')) {
    /**
     * JSON转数组
     * @param  string $json JSON数据
     * @return array        返回数组数据
     */
    function to_array($json = '') {
        return json_decode($json, true);
    }
}

if (!function_exists('get_json')) {
    /**
     * 获取JSON并自动转数组
     * @param  string  $file JSON文件
     * @param  boolean $is_array   是否以输出输出,默认TRUE
     * @param  boolean $rm_comment 是否去掉注释,默认TRUE
     * @return json     返回JSON数据
     */
    function get_json($file, $is_array = true, $rm_comment = true) {
        $json_string = file_get_contents($file);
        $json_string = str_replace("/", "\/", $json_string);
        if ($rm_comment) {
            $json_string = remove_comment($json_string);
        }
        if ($is_array) {
            return to_array($json_string);
        } else {
            return $json_string;
        }
    }
}

if (!function_exists('remove_comment')) {
    /**
     * 去除PHP代码注释
     * @param  string $content 代码内容
     * @return string 去除注释之后的内容
     */
    function remove_comment($content) {
        $content = preg_replace("/\:\/\//s", '@ubhtpp@', $content);
        $content = preg_replace("/(\/\*.*\*\/)|(#.*?\n)|(\/\/.*?\n)/s", '', str_replace(array("\r\n", "\r"), "\n", $content));
        $content = preg_replace("/@ubhtpp@/s", '://', $content);
        return $content;
    }

}

if (!function_exists('__PHPPACK_')) {

}

if (!function_exists('__PHPPACK_')) {

}

if (!function_exists('__PHPPACK_')) {

}

if (!function_exists('__PHPPACK_')) {

}

if (!function_exists('__PHPPACK_')) {

}

if (!function_exists('__PHPPACK_')) {

}
