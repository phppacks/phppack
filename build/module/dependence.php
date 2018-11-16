<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-16 22:46:52
 */

class dependence {

    /**
     * 配置信息
     * @var array
     */
    public $config = array();

    /**
     * 编译目录
     * @var string
     */
    public $path = '';

    /**
     * 循环解析值
     * @var integer
     */
    public $itemIndex = 0;

    /**
     * 安装模块
     * @var array
     */
    public $installedModules = array();

    /**
     * 样式模块
     */
    public $styleModules = array();

    /**
     * 插件模块
     */
    public $scriptModules = array();

    /**
     * 可编译文件类型
     * @var [type]
     */
    public $fileType = ['js', 'vue', 'htm', 'html'];

    public function demo() {
        // P(PHPPACK_DIR);
        // P(ROOT_DIR);
        // P(VENDOR_DIR);
        $this->compile("src");
    }

    /**
     * 执行编译
     * @param  string  $path      需要编译的目录
     * @param  boolean $is_full 是否为完整目录
     * @return array             返回编译数组
     */
    /**
     * [compile description]
     * @param  string  $path    [description]
     * @param  boolean $is_full [description]
     * @return [type]           [description]
     */
    public function compile($path = '', $is_full = false) {
        $phppack = "";
        #判断目录是否为完整目录
        if ($is_full) {

            $phppack = trim($path, DS) . DS . "phppack.json";
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

        #读取编译目录
        $this->path = dirname($phppack);
        $this->getEntry();
        return array();
    }

    /**
     * 获取入口文件
     * @return [type] [description]
     */
    public function getEntry() {
        #改变目录
        chdir($this->path);

        #循环读取文件-目前只支持一体化
        foreach ($this->config['entry'] as $key => $value) {
            $this->getFileInfo($key, $value);
        }
        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        #模板文件分离模式
        $compontent = array();

        #获取的数据信息
        $informations = $this->getDomDocument();
        #春促最后数据信息
        #循环分析数据
        foreach ($informations as $key => $son) {
            $index = $son['index'];
            //P([$son['index'], $son['code'], $son['file'], $son['name'], $son['type']]);

            // //P($son['type']);
            $compontent[$index] = $son;

            switch ($son['type']) {
            case 'js':
                $content = $son['body'];
                break;
            case 'html':
            case 'vue':
                $content = $son['script'];
                break;
            }

            #查询匹配
            $ruleValue = "#import\s+(\w*)?(\s+from\s+)?[\'\"]([\.\/\w]*)[\'\"]#is";
            if (preg_match_all($ruleValue, $son['body'], $array, PREG_SET_ORDER)) {
                foreach ($array as $key => $val) {
                    $file = $this->getCompontentInfo($val, $son);
                    #获取需要查找替换的内容

                    #有文件加载
                    if ($file) {
                        $code    = md5_file($file);
                        $num     = $informations[$code];
                        $replace = "import {$val[1]} from 'phppack_" . $num['index'] . "_a';";
                    }
                    #取消文件加载
                    else {
                        $replace = '';
                    }
                    $content = str_replace($val[0], $replace, $content);
                }
            }
            $compontent[$index]['body'] = $content;
        }
        #获取解析结果
        ob_start();
        require dirname(__DIR__) . '/bin/template.php';
        $content = ob_get_clean();
        echo $content;
        exit;
        #读取模板文件展示
        //         $script = '';
        //         #开始拼接页面代码
        //         foreach ($this->installedModules as $key => $value) {
        //             $function = ($value['type'] == 'js') ? $value['body'] : $value['script'];
        //             if (count($value['require'])) {
        //                 $__TPL = <<<TPL
        // function(module, exports, __phppack_require__) {
        //     {$function}
        // },
        // TPL;
        //             } else {
        //                 $__TPL = <<<TPL
        // function(module, exports) {
        //     {$function}
        // },
        // TPL;
        //             }
        //             #执行代码拼接
        //             $script .= $__TPL;
        //         }
        //         P($script);
    }

    /**
     * 获取DOM信息
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function getDomDocument($value = '') {
        $documents = array();
        #加载DOM插件
        require dirname(__DIR__) . '/bin/simple_html_dom.php';
        #循环编译文件
        foreach ($this->installedModules as $key => $value) {
            if ($value['type'] == "vue") {
                $html     = str_get_html($value['body']);
                $template = $html->find('template', 0)->innertext;
                $content  = $html->find('script', 0)->innertext;
                $style    = $html->find('style', 0)->innertext;
                $script   = "";
                #判断模板是否为空
                if ($template && $content) {
                    $this->checkScript($value['body'], $content, $value['file']);
                    #查找替换内容
                    $preg = "#export\s+default\s*{#is";
                    if (preg_match($preg, $content, $matches)) {
                        $template = str_replace('"', '\"', compress_html($template));
                        $data     = <<<TPL
export default {
  template:"{$template}",
TPL;
                        $script = preg_replace($preg, $data, $content);
                    }
                }
                $documents[$key]            = $value;
                $documents[$key]['script']  = $script;
                $documents[$key]['content'] = $content;
                $html->clear();
            } else {
                $documents[$key] = $value;
            }
        }
        return $documents;
    }

    /**
     * 检查JS代码错误
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function checkScript($body = '', $script = '', $file = '') {
        try {
            $options = array('sourceType' => "module", "jsx" => true);
            #生成AST
            $ast = \Peast\Peast::latest($script, $options)->parse();
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
     * 嵌套读取所有文件信息
     * @param  string $name     名称
     * @param  string $file     文件
     * @param  string $alias    别名
     * @return [type]        [description]
     */
    public function getFileInfo($name = '', $file = '', $alias = false) {
        $type = pathinfo($file, PATHINFO_EXTENSION);
        $body = file_get_contents($file);
        $code = md5_file($file);
        $path = dirname($file);
        #判断该模块是否存在
        if (isset($this->installedModules[$code])) {
            $this->installedModules[$code]['require'] = $alias ? $alias : $file;
            return;
        }
        #判断内容是否存在
        if ($body) {
            #存储安装模块
            $this->installedModules[$code] = array(
                "index"   => $this->itemIndex,
                "name"    => $name,
                "type"    => $type,
                "file"    => $file,
                "code"    => $code,
                "require" => $alias ? [$alias] : [],
                "body"    => $body,
            );
            $this->itemIndex++;
            #查询匹配
            $ruleValue = "#import\s+(\w*)?(\s+from\s+)?[\'\"]([\.\/\w]*)[\'\"]#is";
            if (preg_match_all($ruleValue, $body, $array)) {
                foreach ($array[3] as $key => $value) {
                    #读取扩展名
                    $extension = pathinfo($value, PATHINFO_EXTENSION);
                    #判断读取上级信息
                    $name  = $array[1][$key];
                    $alias = $value;
                    if (stripos($value, "../") !== 0 || stripos($value, "/") !== 0) {
                        if ($path !== '.') {
                            $value = $path . trim($value, '.');
                        }
                    }
                    #如果文件有扩展名
                    if ($extension) {
                        $file = $value;
                        #检查依赖文件是否存在
                        if (!is_file($file)) {
                            __PHPPACK_ERROR("找不到{$file}依赖文件", 80011);
                        }
                        #如果是CSS样式转样式模块
                        if (strtolower($extension) == "css") {
                            $this->getStyleModules($value, $extension);
                        }
                        #如果是JS插件转插件模块
                        if (strtolower($extension) == "js") {
                            $this->getScriptModules($value, $extension);
                        }
                        #如果是安装模块
                        else {
                            $this->getFileInfo($name, $file, $value);
                        }
                    } else {
                        #判断处理为插件依赖模块
                        $dependencies = array_keys($this->config['dependencies']);
                        if (in_array($alias, $dependencies)) {
                            $name = $array[1][$key];
                            $file = "./plugin/" . $value . ".js";
                            $this->getFileInfo($name, $file, $alias);
                        }
                        #判断处理非插件依赖关系
                        else {
                            #设置后缀名并加载
                            $file = $value . ".js";
                            if (!is_file($file)) {
                                $file = $value . ".vue";
                            }
                            #检查依赖文件是否存在
                            if (!is_file($file)) {
                                __PHPPACK_ERROR("找不到{$file}依赖文件", 80011);
                            }
                            $this->getFileInfo($name, $file, $alias);
                        }
                    }
                }
            }
        }
    }

    /**
     * 获取文件信息
     * @param  string $value 文件名
     * @param  string $name  父级信息
     * @return [type]        [description]
     */
    public function getCompontentInfo($oneself = '', $parent = '') {
        #读取扩展名
        $extension = pathinfo($oneself[3], PATHINFO_EXTENSION);
        #设置文件信息
        $alias = isset($oneself[1]) ? $oneself[1] : false;
        $file  = $oneself[3];
        #判断读取上级目录
        $path = dirname($parent['file']);
        #判断是在当前页面下的
        if (stripos($file, "../") !== 0 || stripos($file, "/") !== 0) {
            if ($path !== '.') {
                $file = $path . trim($file, '.');
            }
        }
        #如果文件有扩展名
        if ($extension) {
            #检查依赖文件是否存在
            if (!is_file($file)) {
                return false;
            }
            #如果是CSS样式转样式模块
            if (strtolower($extension) == "css") {
                return false;
            }
            #如果是JS插件转插件模块
            if (strtolower($extension) == "js") {
                return false;
            }
        } else {
            #判断处理为插件依赖模块
            $dependencies = array_keys($this->config['dependencies']);
            if (in_array($file, $dependencies)) {
                $file = "./plugin/" . $file . ".js";
            }
            #判断处理非插件依赖关系
            else {
                #保存原名
                $rawname = $file;
                #设置后缀名并加载
                $file = $rawname . ".js";
                if (!is_file($file)) {
                    $file = $rawname . ".vue";
                }
                #设置后缀名并加载
                if (!is_file($file)) {
                    $file = $rawname . ".html";
                }
                #检查依赖文件是否存在
                if (!is_file($file)) {
                    return false;
                }
            }
        }
        return $file;
    }

    /**
     * 存储样式模块
     * @param  string $value     [description]
     * @param  [type] $extension [description]
     * @return [type]            [description]
     */
    public function getStyleModules($value = '') {
        #获取样式信息
        $body = file_get_contents($value);
        $code = md5_file($value);
        #存储样式模块
        $this->styleModules[$code] = array(
            "file" => $value,
            "code" => $code,
            "body" => $body,
        );
    }

    /**
     * 存储插件模块
     * @param  string $value     [description]
     * @param  [type] $extension [description]
     * @return [type]            [description]
     */
    public function getScriptModules($value = '') {
        #获取唯一码
        $code = md5_file($value);
        #存储样式模块
        $this->styleModules[$code] = array(
            "file" => $value,
            "code" => $code,
        );
    }
}