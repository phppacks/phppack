<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-30 16:11:25
 */

class display extends basics {
    /**
     * 执行页面显示
     * @param  [type] $router [description]
     * @return [type]         [description]
     */
    public function bootstrap($router = '', $path = '', $is_full = false) {
        #获取配置文件
        $this->intConfig();
        #解析入口文件-返回所有数据
        $data = $this->getEntry();
        $html = $this->htmlSplitting($data);
        #分解后的数据部署
        $file = base64_decode($this->config['build']['template']);
        if (!is_file($file)) {
            __PHPPACK_ERROR("[$file]HTML文件不存在", 70001);
        }
        $file    = file_get_contents($file);
        $content = str_replace('</head>', $html, $file);
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo $content;
        exit;
    }

    /**
     * 页面组装
     * @param  string $data [description]
     * @return [type]       [description]
     */
    protected function htmlSplitting($data = '') {
        extract($data);
        $html = '<script type="text/javascript" src="' . ROOT . '/vendor/phppacks/phppack/build/bin/babel.js"></script>';
        foreach ($css as $key => $value) {
            $html .= '<link rel="stylesheet" type="text/css" href="' . $value . '">';
        }
        foreach ($js as $key => $value) {
            $html .= '<script type="text/javascript" src="' . $value . '"></script>';
        }
        $html .= '<style type="text/css">' . implode("", $this->styleModules) . '</style>';
        $html .= <<<SC
    <script type="text/javascript">
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
    </script>
SC;
        /**
         * 循环输出组件
         * @var [type]
         */
        foreach ($compontent as $key => $value) {
            $html .= '<script type="text/babel" code="' . $key . '">' . $value . '</script>';
        }
        $html .= <<<CM
    <script type="text/babel" code="999999999">
    __phppack_compile(__phppack_require);
    </script>
CM;
        return $html;
    }
}