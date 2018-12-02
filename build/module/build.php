<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-29 22:17:37
 */

class build extends basics {

    public function bootstrap($router = '') {
        if ($router == 'ensure') {
            $this->ensure();
            exit();
        }
        #获取配置文件
        $this->intConfig();
        #解析入口文件-返回所有数据
        $data = $this->getEntry();
        extract($data);
        #分解后的数据部署
        $js           = to_json($js);
        $css          = to_json($css);
        $style        = $this->styleModules;
        $config       = to_json($this->config);
        $url          = ROOT . "/phppack/build/ensure";
        $this->router = $router;
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        #导入编译文件
        ob_start();
        require dirname(__DIR__) . '/bin/build.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }

    /**
     * 创建数据打包-API-分包
     * @param  string $value [description]
     * @return [type]        [description]
     */
    protected function ensure($value = '') {
        $script = <<<SCR
function GetHttpRequest() {
    if (window.XMLHttpRequest)
        return new XMLHttpRequest();
    else if (window.ActiveXObject)
        return new ActiveXObject("MsXml2.XmlHttp");
}

function ajaxPage(sId, url) {
    var oXmlHttp = GetHttpRequest();
    oXmlHttp.onreadystatechange = function() {
        if (oXmlHttp.readyState == 4) {
            includeJS(sId, url, oXmlHttp.responseText);
        }
    }
    oXmlHttp.open('GET', url, false);
    oXmlHttp.send(null);
}

function includeJS(sId, fileUrl, source) {
    if ((source != null) && (!document.getElementById(sId))) {
        var oHead = document.getElementsByTagName('HEAD').item(0);
        var oScript = document.createElement("script");
        oScript.type = "text/javascript";
        oScript.id = sId;
        oScript.text = source;
        oHead.appendChild(oScript);
    }
}

var __phppack_require = [];
var __phppack_compile = function(modules) {
    var installedModules = [];
    function require(moduleNum) {

        ajaxPage(moduleNum, moduleNum + ".js");

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
__phppack_compile(__phppack_require);
SCR;
        $fileList = to_array(base64_decode($_POST['compontent']));
        $config   = empty($_POST['config']) ? [] : to_array($_POST['config']);
        $data     = array(
            'compontent' => $script,
            'style'      => base64_decode($_POST['style']),
            'router'     => empty($_POST['router']) ? 'home/home' : $_POST['router'],
            'config'     => $config,
            'css'        => empty($_POST['css']) ? [] : to_array($_POST['css']),
            'js'         => empty($_POST['js']) ? [] : to_array($_POST['js']),
        );
        foreach ($fileList as $key => $value) {
            $filePath = ROOT_DIR . DS . $config['build']['path'] . DS . 'phppack_' . $key . "_a.js";
            to_mkdir($filePath, $value, true, true);
        }
        $html       = $this->dataSplitting($data);
        $file       = base64_decode($config['config']['build']['template']);
        $file       = file_get_contents($file);
        $content    = str_replace('</head>', $html, $file);
        $compileTpl = ROOT_DIR . DS . $config['build']['path'] . DS . basename($file);
        #创建编译文件
        to_mkdir($compileTpl, $content, true, true);
    }
}