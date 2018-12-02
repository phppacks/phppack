<?php

/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 09:55:52
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-29 16:05:45
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

if (!function_exists('compress_html')) {
    /**
     * 压缩html代码
     * @param  string $value [description]
     * @return [type]        [description]
     */
    function compress_html($string) {
        $string  = str_replace("\r\n", '', $string); //清除换行符
        $string  = str_replace("\n", '', $string); //清除换行符
        $string  = str_replace("\t", '', $string); //清除制表符
        $pattern = array(
            "/> *([^ ]*) *</", //去掉注释标记
            "/[\s]+/",
            "/<!--[^!]*-->/",
            "/\" /",
            "/ \"/",
            "'/\*[^*]*\*/'",
        );
        $replace = array(
            ">\\1<",
            " ",
            "",
            "\"",
            "\"",
            "",
        );
        return preg_replace($pattern, $replace, $string);
    }
}

if (!function_exists('base64EncodeImage')) {

    /**
     * 将图片转Base64位
     * @param  [type] $image_file [description]
     * @return [type]             [description]
     */
    function base64EncodeImage($image_file) {
        $base64_image = '';
        $image_info   = getimagesize($image_file);
        $image_data   = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }
}

if (!function_exists('to_mkdir')) {
    /**
     * 创建目录
     * @param    string    $path     目录名称，如果是文件并且不存在的情况下会自动创建
     * @param    string    $data     写入数据
     * @param    bool    $is_full  完整路径，默认False
     * @param    bool    $is_cover 强制覆盖，默认False
     * @return   bool    True|False
     */
    function to_mkdir($path = null, $data = null, $is_full = false, $is_cover = false) {
        $file = $path;
        #非完整路径进行组合
        if (!$is_full) {
            $path = ROOT_DIR . '/' . ltrim(ltrim($path, './'), '/');
        }
        #检测是否为文件
        $file_suffix = pathinfo($path, PATHINFO_EXTENSION);
        if ($file_suffix) {
            $path = pathinfo($path, PATHINFO_DIRNAME);
        } else {
            $path = rtrim($path, '/');
        }
        #执行目录创建
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                return false;
            }
            chmod($path, 0777);
        }
        #文件则进行文件创建
        if ($file_suffix) {
            if (!is_file($file)) {
                if (!file_put_contents($file, $data)) {
                    return false;
                }
            } else {
                #强制覆盖
                if ($is_cover) {
                    if (!file_put_contents($file, $data)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}

if (!function_exists('__PHPPACK_FORMAT')) {
    /**
     * 处理驼峰准换
     * @param  [type] $name [description]
     * @param  string $sign [description]
     * @return [type]       [description]
     */
    function __PHPPACK_FORMAT($name, $sign = "_") {
        $temp_array = array();
        for ($i = 0; $i < strlen($name); $i++) {
            $ascii_code = ord($name[$i]);
            if ($ascii_code >= 65 && $ascii_code <= 90) {
                if ($i == 0) {
                    $temp_array[] = chr($ascii_code + 32);
                } else {
                    $temp_array[] = $sign . chr($ascii_code + 32);
                }
            } else {
                $temp_array[] = $name[$i];
            }
        }
        return implode('', $temp_array);
    }
}

if (!function_exists('__PHPPACK_REMOVE_COMMENT')) {
/**
 * 去除PHP代码注释
 * @param  string $content 代码内容
 * @return string 去除注释之后的内容
 */
    function __PHPPACK_REMOVE_COMMENT($content) {
        return preg_replace("/(\/\*.*\*\/)|(\/\/.*?\n)/s", '', str_replace(PHP_EOL, "\n", $content));
    }
}

if (!function_exists('__PHPPACK_COLON_ESCAPE')) {
    /**
     * 转义冒号
     * @param  string $body [description]
     * @return [type]       [description]
     */
    function __PHPPACK_COLON_ESCAPE($body = '') {
        return str_replace(':', '\:', $body);
    }
}