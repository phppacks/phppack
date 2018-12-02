<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-30 15:26:15
 */

class extension extends basics {

    public $lock = "";

    public function bootstrap($router = '') {
        if ($router == 'single') {
            $this->single();
        }
        P($router);
        #获取配置文件
        $this->intConfig();
        #解析入口文件-返回所有数据
        $data   = $this->getEntry();
        $config = to_json($this->config);
        $lock   = to_json($this->lock);
        $url    = ROOT . "/phppack/extension/single";
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        #导入编译文件
        ob_start();
        require dirname(__DIR__) . '/bin/extension.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }

    /**
     * 获取入口文件
     * @return [type] [description]
     */
    protected function getEntry() {
        #改变目录
        chdir($this->path);
        #变量转化
        extract($this->config);
        #读取锁定列表
        $lockList = [];
        #判断锁定是否存在
        if (isset($lock) && !empty($lock)) {
            $this->lock = $lock;
            #循环读取文件-目前只支持一体化
            foreach ($lock as $fileAlias => $fileName) {
                $this->clear();
                if (is_file($fileName)) {
                    $this->deepSearchFile($fileName, $fileAlias);
                } else {
                    __PHPPACK_ERROR("找不到{$fileName}入口文件", 80011);
                }
                #执行组件读取
                $compontent = array();
                foreach ($this->templateModules as $key => $body) {
                    $retContent = $this->contentLookupReplacement($body, $key);
                    array_push($compontent, $retContent);
                }
                $this->getLoadList();
                $info = array(
                    "name" => $fileAlias,
                    "file" => $fileName,
                    "css"  => $this->isEmptyArray($this->loadModules['css']),
                    "js"   => $this->isEmptyArray($this->loadModules['js']),
                );
                $lockList[$fileAlias] = array(
                    "info"       => base64_encode(to_json($info)),
                    "compontent" => $compontent,
                    "style"      => $this->styleModules,
                );
            }
        }
        return $lockList;
    }

    /**
     * 判断是否是否为空
     * @param  string  $value [description]
     * @return boolean        [description]
     */
    public function isEmptyArray($value = '') {
        $data = [];
        if (isset($value)) {
            if (!empty($value)) {
                $data = $value;
            }
        }
        return $data;
    }

    /**
     * 页面存储-API调用
     * @param  string $value [description]
     * @return [type]        [description]
     */
    protected function single() {
        $script = base64_decode($_POST['script']);
        $style  = base64_decode($_POST['style']);
        $config = $_POST['config'];
        $info   = to_array(base64_decode(trim($_POST['info'])));
        P($config);
        P($info);
        extract($info);
        $ext_style  = "";
        $ext_script = $this->extGlobel($name, $script);
        if ($css || $style) {
            foreach ($css as $key => $value) {
                $ext_style .= file_get_contents($value);
            }
            $ext_style .= $style;
        }
        $file_css = ROOT_DIR . DS . "plugin" . DS . $name . ".css";
        $file_js  = ROOT_DIR . DS . "plugin" . DS . $name . ".js";
        if (!empty($ext_style)) {
            to_mkdir($file_css, $ext_style, true, true);
        }
        if ($ext_script) {
            to_mkdir($file_js, $ext_script, true, true);
        }
        // $ext_script = $compontent;

        // #创建编译文件
        // to_mkdir($compileTpl, $content, true, true);
        exit;
    }

    public function extGlobel($name, $content = '') {
        $content = trim($content);
        $script  = <<<SCR
(function webpackUniversalModuleDefinition(root, factory) {
    if(typeof exports === 'object' && typeof module === 'object')
        module.exports = factory(require("vue"));
    else if(typeof define === 'function' && define.amd)
        define(["vue"], factory);
    else {
        var a = typeof exports === 'object' ? factory(require("vue")) : factory(root["Vue"]);
        for(var i in a) (typeof exports === 'object' ? exports : root)[i] = a[i];
    }
})(this, function() {
    return $name = $content;
});
SCR;
        return $script;
    }

    public function getGlobel($content = '') {
        $script = <<<SCR
(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
    typeof define === 'function' && define.amd ? define(factory) :
    (global.Vue = factory());
}(this, (function () {
'use strict';
var __phppack_require = [];
var __phppack_compile = function(modules) {
    var installedModules = [];
    function require(moduleNum) {
        var numArr =  moduleNum.split('_');
        var moduleId = numArr[1];
        if (installedModules[moduleId]){
            return installedModules[moduleId].exports;
        }
        var module = installedModules[moduleId] = {
            exports: {},
            id: moduleId,
            loaded: false
        };
        modules[moduleId].call(module.exports, module, module.exports, require);
        module.loaded = true;
        return module.exports;
    }

    function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

    require.ensure = function(name){
        var _ensure = require(name);
        var _ensure2 = _interopRequireDefault(_ensure);
        return _ensure2.default;
    }
    require.m = modules;
    require.c = installedModules;
    require.p = "";
    window.onload =function(){
        return require("phppack_0_a");
    }
}
$content
return __phppack_compile(__phppack_require);
})));
SCR;
        return $script;
    }

}