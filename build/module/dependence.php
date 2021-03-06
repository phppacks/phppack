<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-29 22:17:37
 */

class dependence {

    /**
     * 配置信息
     * @var array
     */
    private $config = array();

    /**
     * 编译目录
     * @var string
     */
    private $path = '';

    /**
     * 循环解析值
     * @var integer
     */
    private $itemIndex = 0;

    /**
     * 可编译文件类型
     * @var [type]
     */
    private $fileType = ['js', 'vue', 'htm', 'html'];

    /**
     * 安装模块
     * @var array
     */
    private $installedModules = array();

    /**
     * 模板模块
     * @var array
     */
    private $templateModules = array();

    /**
     * 样式模块
     * @var array
     */
    private $styleModules = array();

    /**
     * 插件模块
     * @var array
     */
    private $fileModules = array();

    /**
     * 加载模板
     * @var array
     */
    private $loadModules = array();

    /**
     * HTMLD结构
     * @var [type]
     */
    public $simpleHtmlDom;

    /**
     * 加载依赖
     * @var array
     */
    private $import = array();

    /**
     * 节点查询模式match|ast
     * @var string
     */
    private $module = "match";

    private $router;

    private $less;

    private $scss;

    private $working;

    public function __construct() {
        $this->working['starttime'] = microtime(true);
        ini_set("xdebug.max_nesting_level", 600);
        set_time_limit(0);
    }

    /**
     * 执行页面显示
     * @param  [type] $router [description]
     * @return [type]         [description]
     */
    public function display($router = '', $path = '', $is_full = false) {
        $this->router = $router;
        #获取配置文件
        $this->intConfig($path, $is_full);
        #解析入口文件-返回所有数据
        $data = $this->getEntry();
        $html = $this->htmlSplitting($data);
        #分解后的数据部署
        $file = base64_decode($this->config['build']['template']);
        if (!is_file($file)) {
            __PHPPACK_ERROR("[$file]HTML文件不存在", 70001);
        }
        $file                     = file_get_contents($file);
        $content                  = str_replace('</head>', $html, $file);
        $this->working['endtime'] = microtime(true);
        $this->working['runtime'] = round($this->working['endtime'] - $this->working['starttime'], 5);
        $this->working['memory']  = memory_get_usage();
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        echo $content;
        exit;
    }

    /**
     * 执行编译-API调用
     */
    public function compile($router = '', $path = '', $is_full = false) {
        #获取配置文件
        $this->intConfig($path, $is_full);
        #解析入口文件-返回所有数据
        $data = $this->getEntry();
        extract($data);
        #分解后的数据部署
        $js           = to_json($js);
        $css          = to_json($css);
        $style        = $this->styleModules;
        $config       = to_json($this->config);
        $this->router = $router;
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        #导入编译文件
        ob_start();
        require dirname(__DIR__) . '/bin/compile.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }

    /**
     * 打包编译-API
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function build($router = '', $path = '', $is_full = false) {
        #获取配置文件
        $this->intConfig($path, $is_full);
        #解析入口文件-返回所有数据
        $data = $this->getEntry();
        extract($data);
        #分解后的数据部署
        $js           = to_json($js);
        $css          = to_json($css);
        $style        = $this->styleModules;
        $config       = to_json($this->config);
        $url          = ROOT . "/phppack/code/ensure";
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
     * 调用不同的方法
     * @param  string $action [description]
     * @return [type]         [description]
     */
    public function code($action = '') {
        switch ($action) {
        case 'single':
            $this->single();
            break;
        case 'ensure':
            $this->ensure();
            break;
        default:
            echo "找不到方法";
            break;
        }
    }

    /**
     * 创建数据打包-API-分包
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function ensure($value = '') {
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
        $compileTpl = ROOT_DIR . DS . $config['build']['path'] . DS . md5($data['router']) . ".html";
        #创建编译文件
        to_mkdir($compileTpl, $content, true, true);
    }

    /**
     * 页面存储-API调用
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function single() {
        $data = array(
            'compontent' => base64_decode($_POST['compontent']),
            'style'      => base64_decode($_POST['style']),
            'router'     => empty($_POST['router']) ? 'home/home' : $_POST['router'],
            'config'     => empty($_POST['config']) ? [] : to_array($_POST['config']),
            'css'        => empty($_POST['css']) ? [] : to_array($_POST['css']),
            'js'         => empty($_POST['js']) ? [] : to_array($_POST['js']),
        );

        $html = $this->dataSplitting($data);
        $file = base64_decode($data['config']['build']['template']);
        if (!is_file($file)) {
            __PHPPACK_ERROR("找不到{$file}模板文件", 80017);
        }
        $body       = file_get_contents($file);
        $content    = str_replace('</head>', $html, $body);
        $compileTpl = ROOT_DIR . DS . $data['config']['build']['path'] . DS . basename($file);
        #创建编译文件
        to_mkdir($compileTpl, $content, true, true);
        exit;
    }

    /**
     * 读取配置文件
     * @param  string  $path      需要编译的目录
     * @param  boolean $is_full   是否为完整目录
     * @return array              返回编译数组
     */
    public function intConfig($path = '', $is_full = false) {
        $phppack = "";
        #判断目录是否为完整目录
        if ($is_full) {
            $phppack = trim($path, DS) . DS . "phppack.json";
        }

        #判断如果目录为空
        if (empty($path)) {
            $phppack = ROOT_DIR . DS . "phppack.json";
        }

        #如果不存在 执行目录拼接
        if (!is_file($phppack)) {
            $phppack = ROOT_DIR . DS . trim($path, DS) . DS . "phppack.json";
        }

        #依然不存在 执行错误提示
        if (!is_file($phppack)) {
            __PHPPACK_ERROR("找不到phppack.json配置文件", 80010);
        }

        #读取配置信息
        $this->config = get_json($phppack);

        /**
         * 设置配置目录
         * base64_encode 用于在WIN系统下地址无法被JSON解析问题,转64位后解析
         */
        if (isset($this->config['build']['template']) && !empty($this->config['build']['template'])) {
            $this->config['build']['template'] = base64_encode(ROOT_DIR . DS . $this->config['build']['template']);
        } else {
            $this->config['build']['template'] = base64_encode(dirname(dirname(__FILE__)) . "/bin/template.html");
        }
        #读取编译目录
        $this->path = dirname($phppack);
    }

    /**
     * 数据拼接-API调用
     * @param  string $data [description]
     * @return [type]       [description]
     */
    public function dataSplitting($data = '') {
        extract($data);
        $html = "";
        foreach ($css as $key => $value) {
            $html .= '<link rel="stylesheet" type="text/css" href="' . $value . '">';
        }
        foreach ($js as $key => $value) {
            $html .= '<script type="text/javascript" src="' . $value . '"></script>';
        }
        $html .= '<style type="text/css">' . $style . '</style>';
        $html .= '<script type="text/javascript">' . $compontent . '</script>';
        return $html;
    }

    /**
     * 页面组装
     * @param  string $data [description]
     * @return [type]       [description]
     */
    public function htmlSplitting($data = '') {
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
        return require("phppack_0");
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

    /**
     * 获取入口文件
     * @return [type] [description]
     */
    private function getEntry() {
        #改变目录
        chdir($this->path);
        #循环读取文件-目前只支持一体化
        foreach ($this->config['entry'] as $fileAlias => $fileName) {
            if (is_file($fileName)) {
                $this->deepSearchFile($fileName, $fileAlias);
            } else {
                __PHPPACK_ERROR("找不到{$fileName}入口文件", 80011);
            }
        }
        $compontent = array();
        foreach ($this->templateModules as $key => $body) {
            $retContent = $this->contentLookupReplacement($body, $key);
            array_push($compontent, $retContent);
        }
        $style = $this->styleModules;

        $this->getLoadList();

        return array(
            "url"        => isset($this->build['url']) && !empty($this->build['url']) ? $this->build['url'] : ROOT . "/phppack/code/single",
            "compontent" => $compontent,
            "css"        => isset($this->loadModules['css']) && !empty($this->loadModules['css']) ? $this->loadModules['css'] : [],
            "js"         => isset($this->loadModules['js']) && !empty($this->loadModules['js']) ? $this->loadModules['js'] : []
        );
        exit();
    }

    /**
     * 深层查找文件
     * @param  string $fileName  文件目录   例：./main.js
     * @param  string $fileAlias 文件别名   例：app
     * @return string            [description]
     */
    private function deepSearchFile($fileName = "", $fileAlias = "") {
        if (empty($fileName)) {
            return;
        }
        /**
         * 文件扩展名
         * @var [type]
         */
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        /**
         * 获取文件内容
         * @var [type]
         */
        $fileContent = file_get_contents($fileName);
        /**
         * 获取文件唯一码
         * @var [type]
         */
        $fileCode = md5_file($fileName);
        /**
         * 获取文件路径
         * @var [type]
         */
        $filePath = dirname($fileName);

        #判断该模块是否存在
        if (isset($this->installedModules[$fileCode])) {
            return;
        }

        #文件内容存在则执行
        if ($fileContent) {
            #根据文件类型处理
            switch ($extension) {
            #处理JS类型
            case 'js':
                #保存文件信息
                $this->saveModules($fileCode, $fileName, $fileAlias, $extension, $fileContent);
                #获取加载文件
                $import = $this->getImportDeclaration($fileContent, $fileContent, $fileName, $fileCode);
                #嵌套循环查询
                foreach ($import as $key => $value) {
                    $itemName = $this->getFileFullPath($value, $value, $fileName);
                    if ($itemName) {
                        $this->deepSearchFile($itemName, $itemName);
                    }
                }
                break;
            #处理样式文件
            case 'css':
                $this->styleModules[$fileCode] = $fileContent;
                break;
            case 'less':
                $this->less($fileName);
                break;
            case 'scss':
            case 'sass':
                $this->scss($fileName);
                break;
            #处理DOM结构
            case 'vue':
            case 'htm':
            case 'html':
                #保存文件信息
                $this->saveModules($fileCode, $fileName, $fileAlias, $extension, $fileContent);
                #获取加载文件
                $import = $this->getDomDocument($fileName, $fileContent, $fileCode);
                #嵌套循环查询
                foreach ($import as $key => $value) {
                    $itemName = $this->getFileFullPath($value, $value, $fileName);
                    if ($itemName) {
                        $this->deepSearchFile($itemName, $itemName);
                    }
                }
                break;
            }
        }
    }

    /**
     * 模块存储
     * @param  string $code      唯一码
     * @param  string $name      文件名
     * @param  string $alias     别名
     * @param  string $extension 扩展
     * @param  string $content   内容
     */
    public function saveModules($code = '', $name = '', $alias = '', $extension = '', $content = '') {
        #存储安装模块
        $this->installedModules[$code] = array(
            "index" => $this->itemIndex,
            "name"  => $alias,
            "type"  => $extension,
            "file"  => $name,
            "code"  => $code,
        );
        #保存文件信息
        $this->templateModules[$code] = $content;
        $this->itemIndex++;
    }

    /**
     * 获取JS节点信息
     * @param  string $fileContent 内容
     * @param  string $body        完整BODY
     * @param  string $file        文件
     * @param  string $code        唯一码
     * @return [type]              [description]
     */
    private function getImportDeclaration($fileContent = '', $body = "", $file = "", $code = "") {
        switch ($this->module) {
        case 'match':
            return $this->phpRegexMatch($fileContent, $body, $file, $code);
            break;
        case 'ast':
            return $this->javascriptAST($fileContent, $body, $file, $code);
            break;
        }
    }

    /**
     * 通过PHP正则获取节点
     * @param  string $fileContent [description]
     * @param  string $body        [description]
     * @param  string $file        [description]
     * @return [type]              [description]
     */
    private function phpRegexMatch($fileContent = '', $body = "", $file = "", $code = "") {
        $fileList = array();
        if ($this->config['build']['check']) {
            $fileContent = $this->checkScriptError($fileContent, $body, $file, $code);
        }
        $ruleValue = "#import\s+([\w]*|\{[\s\S]*?\})?([\s+]from[\s+])?[\'\"]([\.\/\w\-\_]*)[\'\"]#is";
        if (preg_match_all($ruleValue, $fileContent, $array)) {
            $fileList = array_merge($fileList, $array[3]);
        }
        $ruleValue = "#require\.ensure\([\'\"](.+?)[\'\"]\)#is";
        if (preg_match_all($ruleValue, $fileContent, $array)) {
            $fileList = array_merge($fileList, $array[1]);
        }
        return $fileList;
    }

    /**
     * 通过AST虚拟树获取节点
     * @param  string $script [description]
     * @param  string $body   [description]
     * @param  string $file   [description]
     * @return [type]         [description]
     */
    private function javascriptAST($script = '', $body = '', $file = '', $code = "") {
        try {
            $this->import = array();
            $options      = array('sourceType' => "module", "jsx" => true);
            #生成AST
            $ast = \Peast\Peast::latest($script, $options)->parse();
            #建立遍历树
            $traverser = new \Peast\Traverser;
            $traverser->addFunction(function ($node) {
                if ($node->getType() === "ImportDeclaration") {
                    $this->import[] = $node->getSource()->getValue();
                }
            });
            #Start traversing
            $traverser->traverse($ast);
            return $this->import;
        } catch (\Peast\Syntax\Exception $e) {
            #获取错误行
            $line = $e->getPosition()->getLine();
            #获取错误位置
            $column = $e->getPosition()->getColumn();
            #获取代码列表
            $codeList = explode(PHP_EOL, $body);
            #获取所在行
            $onLine = $this->getRowsread($codeList);
            #获取错误所在行
            $errLine = $onLine + $line - 1;
            #获取遍历开始行
            $steLine = ($errLine >= 3) ? $errLine - 2 : 0;
            include_once dirname(__DIR__) . DS . "/bin/error_report.php";
            exit();
        }
    }

    /**
     * 检查JS代码错误
     * @param  string $value [description]
     * @return [type]        [description]
     */
    private function checkScriptError($script = '', $body = '', $file = '', $code = '') {
        try {
            #AST参数选项
            $options = array('sourceType' => "module", "jsx" => false);
            #生成AST
            $ast = \Peast\Peast::latest($script, $options)->parse();
            #创建渲染器
            $renderer = new \Peast\Renderer;
            #把格式化程序
            $renderer->setFormatter(new \Peast\Formatter\PrettyPrint);
            #渲染AST
            $content = $renderer->render($ast);
            #存储代码
            $this->templateModules[$code] = $content;
            // P($file);
            // highlight_string($content);
            // echo "<br>";
            return $content;
        } catch (\Peast\Syntax\Exception $e) {
            #获取错误行
            $line = $e->getPosition()->getLine();
            #获取错误位置
            $column = $e->getPosition()->getColumn();
            #获取代码列表
            $codeList = explode(PHP_EOL, $body);
            #获取所在行
            $onLine = $this->getRowsread($codeList);
            #获取错误所在行
            $errLine = $onLine + $line - 1;
            #获取遍历开始行
            $steLine = ($errLine >= 3) ? $errLine - 2 : 0;
            include_once dirname(__DIR__) . DS . "/bin/error_report.php";
            exit();
        }
    }

    /**
     * 获取DOM结构数据
     * @param  string $fileName    处理文件名
     * @param  string $fileContent DOM数据文件
     * @param  [type] $fileCode    文件唯一码
     * @return [type]              [description]
     */
    private function getDomDocument($fileName = '', $fileContent = '', $fileCode = '') {
        $import = [];
        #判断DOM是否实例化
        if (empty($this->simpleHtmlDom)) {
            require dirname(__DIR__) . '/bin/simple_html_dom.php';
            $html = $this->simpleHtmlDom = new \simple_html_dom();
        } else {
            $html = $this->simpleHtmlDom;
        }
        #加载DOM信息
        $html->load($fileContent);
        #处理样式-以及作用域另
        $scoped = $this->intStyleInfo($html->find('style', 0), $fileCode);
        #处理模板-以及作用域另
        $template = $this->intTemplateinfo($html->find('template', 0), $scoped, $fileName);
        #处理JS
        $script = $html->find('script', 0)->innertext;
        #判断模板是否为空
        if ($template && $script) {
            $import = $this->getImportDeclaration($script, $fileContent, $fileName, $fileCode);
            #查找替换内容
            $preg = "#export\s+default\s*{#is";
            if (preg_match($preg, $script, $matches)) {
                $template = addslashes(compress_html($template));
                $data     = <<<TPL
export default {
    template:"{$template}",
TPL;
                $script = preg_replace($preg, $data, $script);
                #替换原有的数据
                $this->templateModules[$fileCode] = $script;
            }
        }
        #如果没有template
        if (empty($template) && $script) {
            $this->templateModules[$fileCode] = $script;
        }
        return $import;
    }

    /**
     * 文件内容查找替换
     * @return [type] [description]
     */
    private function contentLookupReplacement($content = "", $code = "") {
        /**
         * 节点查询模式match|ast
         * @var string
         */
        switch ($this->module) {
        case 'match':
            return $this->phpRegexReplace($content, $code);
            break;
        case 'ast':
            return $this->jsReplaceAST($content, $code);
            break;
        }
    }

    /**
     * 通过正则替换
     * @param  string $content [description]
     * @param  string $code    [description]
     * @return [type]          [description]
     */
    public function phpRegexReplace($content = "", $code = "") {
        $parentPath = $this->installedModules[$code]['file'];
        $ruleValue  = "#import\s+([\w]*|\{[\s\S]*?\})?([\s+]from[\s+])?[\'\"]([\.\/\w\-\_]*)[\'\"]#is";
        if (preg_match_all($ruleValue, $content, $array, PREG_SET_ORDER)) {
            foreach ($array as $key => $import) {
                if (empty($import[1])) {
                    $content = str_replace($import[0], "", $content);
                } else {
                    $file = $this->getFileFullPath($import[3], $import[3], $parentPath);
                    if ($file) {
                        $icode    = md5_file($file);
                        $name     = "phppack_" . $this->installedModules[$icode]['index'] . "_a";
                        $toimport = "import $import[1] from '$name'";
                        $content  = str_replace($import[0], $toimport, $content);
                    } else {
                        $content = str_replace($import[0], "", $content);
                    }

                }
            }
        }
        $ruleValue = "#require\.ensure\([\'\"](.+?)[\'\"]\)#is";
        if (preg_match_all($ruleValue, $content, $array, PREG_SET_ORDER)) {
            foreach ($array as $key => $import) {
                $file = $this->getFileFullPath($import[1], $import[1], $parentPath);
                if ($file) {
                    $icode    = md5_file($file);
                    $name     = "phppack_" . $this->installedModules[$icode]['index'] . "_a";
                    $toimport = "require.ensure('$name')";
                    $content  = str_replace($import[0], $toimport, $content);
                } else {
                    $content = str_replace($import[0], "", $content);
                }
            }
        }
        return $content;
    }

    /**
     * 通过AST替换
     * @param  string $content [description]
     * @param  string $code    [description]
     * @return [type]          [description]
     */
    public function jsReplaceAST($content = "", $code = "") {
        $this->file = $this->installedModules[$code]['file'];
        try {
            $options = array('sourceType' => "module", "jsx" => true);
            #生成AST
            $ast       = \Peast\Peast::latest($content, $options)->parse();
            $traverser = new \Peast\Traverser;
            $traverser->addFunction(function ($node) {
                if ($node->getType() === "ImportDeclaration") {
                    $import = $node->getSource()->getValue();
                    $ifile  = $this->getFileFullPath($import, $import, $this->file);
                    #检查是否存在from
                    $from = $node->getSpecifiers();
                    if ($from && $ifile) {
                        $name     = $from[0]->getLocal()->getName();
                        $icode    = md5_file($ifile);
                        $name     = "$name from phppack_" . $this->installedModules[$icode]['index'] . "_a";
                        $literal  = new \Peast\Syntax\Node\ImportDeclaration();
                        $newValue = $node->getSource()->setValue($name);
                        return $literal->setSource($newValue);
                    } else {
                        return \Peast\Traverser::REMOVE_NODE;
                    }
                }
            });
            #Start traversing
            $traverser->traverse($ast);
            #创建渲染器
            $renderer = new \Peast\Renderer;
            #把格式化程序
            $renderer->setFormatter(new \Peast\Formatter\PrettyPrint);
            #渲染AST
            return $renderer->render($ast);
        } catch (\Peast\Syntax\Exception $e) {
            $file = $this->file;
            #获取错误行
            $line = $e->getPosition()->getLine();
            #获取错误位置
            $column = $e->getPosition()->getColumn();
            #获取代码列表
            $codeList = explode(PHP_EOL, $content);
            #获取所在行
            $onLine = $this->getRowsread($codeList);
            #获取错误所在行
            $errLine = $onLine + $line - 1;
            #获取遍历开始行
            $steLine = ($errLine >= 3) ? $errLine - 2 : 0;
            include_once dirname(__DIR__) . DS . "/bin/error_report.php";
            exit();
        }
    }

    /**
     * 获取文件完整路径
     * @param  string $fileName   文件名
     * @param  string $fileAlias  文件别名
     * @param  string $parentPath 父级文件
     * @return [type]             [description]
     */
    public function getFileFullPath($fileName = '', $fileAlias = '', $parentPath = '') {
        #获取父级路径
        $parentPath = pathinfo($parentPath, PATHINFO_EXTENSION) ? dirname($parentPath) : $parentPath;
        #读取扩展名
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        ##判断是否是../目录
        if (stripos($fileAlias, "../") === 0) {
            $fileAlias = substr($fileName, 3);
            $fileName  = dirname($parentPath) . DS . $fileAlias;
        }
        #目录机构不是顶级或当前
        else {
            if ($parentPath !== '.' && stripos($fileName, "/") !== 0) {
                $fileName = $parentPath . trim($fileName, '.');
            }
        }
        #如果文件有扩展名
        if ($extension) {
            #检查依赖文件是否存在
            if (!is_file($fileName)) {
                return false;
            }
        } else {
            #判断处理为插件依赖模块
            $dependencies = array_keys($this->config['dependencies']);
            if (in_array($fileAlias, $dependencies)) {
                $fileName = "./plugin/" . $fileAlias . ".js";
                $this->intFileInfo($fileName, 'js');
                return false;
            }
            #判断处理非插件依赖关系
            else {
                #保存原名
                $rawname = $fileName;
                #设置后缀名并加载
                $fileName = $rawname . ".js";
                if (!is_file($fileName)) {
                    $fileName = $rawname . ".vue";
                }
                #设置后缀名并加载
                if (!is_file($fileName)) {
                    $fileName = $rawname . ".html";
                }
                #检查依赖文件是否存在
                if (!is_file($fileName)) {
                    return false;
                }
            }
        }
        return $fileName;
    }

    /**
     * 返回代码所在行
     * @return [type] [description]
     */
    public function getRowsread($content) {
        $line = 1;
        foreach ($content as $key => $value) {
            $preg = "#\<script(.*?)\>#is";
            if (preg_match($preg, $value, $matches)) {
                $s = trim($matches[1]);
                if (md5($s) !== md5('type="text/json"') && $s !== md5("type='text/json'")) {
                    $line = $key;
                }
            }
        }
        return $line;
    }

    /**
     * 获取加载列表
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function getLoadList() {
        $path = getcwd() . "/" . $this->config['build']['path'];
        $url  = $this->web() . "/" . $this->config['build']['path'];
        foreach ($this->fileModules as $key => $value) {
            $file = $key . "." . $value['type'];
            to_mkdir($path . "/" . $file, $value['body'], true, true);
            switch ($value['type']) {
            case 'css':
                $this->loadModules['css'][] = $url . "/" . $file;
                break;
            case 'js':
                $this->loadModules['js'][] = $url . "/" . $file;
                break;
            }
        }
    }

    /**
     * 处理文件信息
     * @param  string $file [description]
     * @param  string $type [description]
     * @return [type]       [description]
     */
    public function intFileInfo($file = '', $type = 'js') {
        #判断是否为文件
        if (is_file($file)) {
            $code = md5_file($file);
            #判断如果该文件为加载则存储
            if (!isset($this->fileModules[$code])) {
                $body = file_get_contents($file);
                #写入文件模组
                $this->fileModules[$code] = array(
                    "body" => $body,
                    "type" => $type,
                );
            }
        } elseif (!empty($file)) {
            $code = md5($file);
            #判断如果该文件为加载则存储
            if (!isset($this->fileModules[$code])) {
                #写入文件模组
                $this->fileModules[$code] = array(
                    "body" => $file,
                    "type" => $type,
                );
            }
        }
    }

    /**
     * 处理CSS样式信息
     * @param  string $value [description]
     * @return [type]        [description]
     */
    private function intStyleInfo($style = '', $code = '') {
        if (!$style) {
            return false;
        }
        #设置作用域参数
        $scoped  = $style->scoped ? "data-v-" . substr($code, 0, 8) : false;
        $content = $style->innertext;
        $istyle  = $scoped ? $this->cssScoped($content, $scoped) : $content;
        #CSS语法问题
        if ($lang = $style->lang) {
            switch ($lang) {
            case 'less':
                $this->less($istyle);
                break;
            case 'scss':
            case 'sass':
                $this->scss($istyle);
                break;
            }
        } else {
            array_push($this->styleModules, $istyle);
        }
        return $scoped;
    }

    /**
     * 获取模板内容信息
     * @param  string $document [description]
     * @return [type]           [description]
     */
    private function intTemplateinfo($tpl = '', $scoped = false, $fileName = '') {
        if ($tpl) {
            $this->intDomImage($tpl->find('img'), $fileName);
            #处理template子集
            foreach ($tpl->children() as $key => $val) {
                $this->tagDispose($val, $scoped);
            }
            return $tpl->innertext;
        } else {
            return '';
        }
    }

    public function intDomImage($img = '', $fileName) {
        if ($img) {
            foreach ($img as $key => $value) {
                if ($src = $value->src) {
                    $file       = $this->getFileFullPath($src, $src, $fileName);
                    $value->src = base64EncodeImage($file);
                }
            }
        }
    }

    /**
     * CSS作用域
     * @Author   Sean       Yan
     * @DateTime 2018-09-05
     * @param    string     $value [description]
     * @return   [type]            [description]
     */
    private function cssScoped($content = '', $_data = false) {
        if ($_data) {
            $data    = "[$_data]";
            $preg    = "#(\w*)\s*\{#is";
            $content = preg_replace($preg, '\1' . $data . '{', $content);
        }
        return $content;
    }

    /**
     * 处理驼峰并处理CSS作用域
     * @param  string $children [description]
     * @param  string $_data    [description]
     * @return [type]           [description]
     */
    private function tagDispose(&$children = '', $_data = false) {
        #处理驼峰标签
        $children->tag = __PHPPACK_FORMAT($children->tag, '-');
        #是否设置作用域
        if ($_data) {
            $children->setAttribute($_data, true);
        }
        #循环遍历子集
        if ($variable = $children->children()) {
            foreach ($variable as $key => $value) {
                $this->tagDispose($value, $_data);
            }
        }
    }

    /**
     * 编译Less文件
     * @param  string $file [description]
     * @return [type]       [description]
     */
    public function less($file = '') {
        if (!$this->less) {
            require_once dirname(dirname(__FILE__)) . "/bin/lessc.inc.php";
            $this->less = new \lessc();
        }
        if (is_file($file)) {
            $content = $this->less->compileFile($file);
            $this->intFileInfo($content, "css");
        } else {
            $content = $this->less->compile($file);
            array_push($this->styleModules, $content);
        }
    }

    /**
     * 编译scss文件
     * @param  string $file [description]
     * @return [type]       [description]
     */
    public function scss($file = '') {
        if (!$this->scss) {
            require_once dirname(dirname(__FILE__)) . "/bin/scss.inc.php";
            $this->scss = new \scssc();
        }
        if (is_file($file)) {
            $content = $this->scss->compileFile($file);
            $this->intFileInfo($content, "css");
        } else {
            $content = $this->scss->compile($file);
            array_push($this->styleModules, $content);
        }

    }

    /**
     * 网站域名
     *
     * @return string
     */
    private function domain() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        return defined('RUN_MODE') && RUN_MODE != 'HTTP' ? ''
        : trim($protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/\\');
    }

    /**
     * 根据伪静态配置
     * 添加带有入口文件的链接
     *
     * @return string
     */
    private function web() {
        $root = self::domain();
        $path = trim(str_replace(ROOT_DIR, "", getcwd()), DS);
        return $root . $path;
    }

}