<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-19 14:34:41
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
     * 样式存储
     * @var [type]
     */
    public $style;

    /**
     * less处理类
     * @var [type]
     */
    public $less;

    /**
     * scss处理类
     * @var [type]
     */
    public $scss;

    /**
     * 路由插槽
     * @var [type]
     */
    public $router = "home/home";

    public $phpRouter = "";

    public $sonRouter = "";

    public $is_router = true;

    /**
     * 可编译文件类型
     * @var [type]
     */
    public $fileType = ['js', 'vue', 'htm', 'html'];

    /**
     * 执行页面显示
     * @param  [type] $router [description]
     * @return [type]         [description]
     */
    public function display($router) {
        $this->router = $router;
        $this->compile("src");
    }

    /**
     * 执行编译
     * @param  string  $path      需要编译的目录
     * @param  boolean $is_full   是否为完整目录
     * @return array              返回编译数组
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
    private function getEntry() {
        #改变目录
        chdir($this->path);
        #执行路由
        $this->routerProcesser();
        #循环读取文件-目前只支持一体化
        foreach ($this->config['entry'] as $key => $value) {
            $this->getFileInfo($key, $value);
        }
        // P($this->installedModules);
        // exit();
        #模板文件分离模式
        $compontent = array();
        #获取的数据信息
        $informations = $this->getDomDocument();
        #春促最后数据信息
        #循环分析数据
        foreach ($informations as $key => $son) {
            #获取唯一标识符
            $index = $son['index'];
            #获取数据
            $compontent[$index] = $son;
            #根据类型选择
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
            // $ruleValue = "#import\s+(\w*)?(\s+from\s+)?[\'\"]([\.\/\w]*)[\'\"]#is";
            // if (preg_match_all($ruleValue, $son['body'], $array, PREG_SET_ORDER)) {

            //     foreach ($array as $key => $item) {
            //         $retFIle         = $item;
            //         $file            = $this->getTraceFile($item[3], $item[3], dirname($son['file']));
            //         $retFIle['path'] = dirname($son['file']);
            //         $retFIle['ret']  = $file;
            //         #有文件加载
            //         if (is_string($file)) {
            //             $code    = md5_file($file);
            //             $num     = $informations[$code];
            //             $replace = "import {$item[1]} from 'phppack_" . $num['index'] . "_a';";
            //         }
            //         #删除不需要加载的文件
            //         else {
            //             $replace = '';
            //         }
            //         $content = str_replace($item[0], $replace, $content);
            //     }
            // }

            #正则匹配规则数组
            $ruleArray = array(
                "#import\s+([\w\_\,\-\{\}\s]*)(from)\s*[\'\"]([\.\/\w\-\_]*)[\'\"]#is",
                "#import\s+[\'\"]([\.\/\w\-\_]*)[\'\"]#is",
            );
            #循环查询匹配数据
            foreach ($ruleArray as $key => $ruleValue) {
                if (preg_match_all($ruleValue, $son['body'], $array, PREG_SET_ORDER)) {
                    foreach ($array as $key => $item) {
                        $retFIle         = $item;
                        $itemName        = isset($item[3]) ? $item[3] : $item[1];
                        $file            = $this->getTraceFile($itemName, $itemName, dirname($son['file']));
                        $retFIle['path'] = dirname($son['file']);
                        $retFIle['ret']  = $file;
                        #有文件加载
                        if (is_string($file)) {
                            $code    = md5_file($file);
                            $num     = $informations[$code];
                            $replace = "import {$item[1]} from 'phppack_" . $num['index'] . "_a';";
                        }
                        #删除不需要加载的文件
                        else {
                            $replace = '';
                        }
                        $content = str_replace($item[0], $replace, $content);
                    }
                }
            }
            $compontent[$index]['body'] = $content;
        }

        #清除之前的缓存
        if (ob_get_level()) {
            ob_end_clean();
        }
        #获取解析结果
        ob_start();
        require dirname(__DIR__) . '/bin/template.php';
        $content = ob_get_clean();
        echo $content;
        exit;
    }

    /**
     * 路由处理器
     * @param  string $value [description]
     * @return [type]        [description]
     */
    private function routerProcesser() {
        if ($this->is_router) {
            $router = file_get_contents("./router.json");
            $router = to_array($router);
            $page   = array();
            #循环获取当前页面
            foreach ($router as $key => $value) {
                if ($value['path'] == $this->router) {
                    $this->sonRouter = "./pages/" . $this->router;
                    $page            = $value;
                }
            }
            if ($page) {
                #循环读取
                while ($page['parent']) {
                    $page = $router[$page['parent']];
                };
                $this->phpRouter = $page['path'];
            } else {
                $this->phpRouter = $this->sonRouter = "./pages/" . $this->router;
            }
        }
    }

    /**
     * 嵌套读取所有文件信息
     * @param  string $name     名称
     * @param  string $file     文件
     * @param  string $alias    别名
     * @return [type]        [description]
     */
    private function getFileInfo($name = '', $file = '', $alias = false) {
        #========
        #$__Demo['file'] = $file;
        #========
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
            // $ruleValue  = "#import\s+(\w*)?(\s+from\s+)?[\'\"]([\.\/\w\-\_]*)[\'\"]#is";
            // if (preg_match_all($ruleValue, $body, $array, PREG_SET_ORDER)) {
            //     $ret['son'] = $array;
            //     foreach ($array as $key => $import) {
            //         $item_name  = $import[1];
            //         $item_alias = $import[3];
            //         $item_file  = $import[3];
            //         #获取完整文件路径
            //         $fileName = $this->getTraceFile($item_file, $item_alias, $path);
            //         if (is_array($fileName)) {
            //             $this->setDatasTore($fileName[0], $fileName[1]);
            //         } elseif (is_string($fileName)) {
            //             $this->getFileInfo($item_name, $fileName, $item_alias);
            //         } else {
            //             __PHPPACK_ERROR("找不到{$item_file}依赖文件", 80011);
            //         }
            //     }
            // }

            $ruleArray = array(
                "#import\s+([\w\,\_\-\{\}\s]*)(from)\s*[\'\"]([\.\/\w\-\_]*)[\'\"]#is",
                "#import\s+[\'\"]([\.\/\w\-\_]*)[\'\"]#is",
            );
            #循环查询匹配多种情况的import
            foreach ($ruleArray as $key => $ruleValue) {
                #查询匹配
                if (preg_match_all($ruleValue, $body, $array, PREG_SET_ORDER)) {
                    #========$__Demo
                    #$__Demo['import'] = $array;
                    #========
                    foreach ($array as $key => $import) {
                        $item_name  = $import[1];
                        $item_alias = isset($import[3]) ? $import[3] : $import[1];
                        $item_file  = $item_alias;
                        #获取完整文件路径
                        $fileName = $this->getTraceFile($item_file, $item_alias, $path);
                        #========$__Demo
                        #$__Demo['import'][$key]['fileName'] = $fileName;
                        #========
                        if (is_array($fileName)) {
                            $this->setDatasTore($fileName[0], $fileName[1]);
                        } elseif (is_string($fileName)) {
                            $this->getFileInfo($item_name, $fileName, $item_alias);
                        } else {
                            __PHPPACK_ERROR("找不到{$item_file}依赖文件", 80011);
                        }
                    }

                }
            }
            #========$__Demo
            #P($__Demo);
            #========
        }
    }

    /**
     * 获取DOM信息
     * @param  string $value [description]
     * @return [type]        [description]
     */
    private function getDomDocument($value = '') {
        $documents = array();
        #加载DOM插件
        require dirname(__DIR__) . '/bin/simple_html_dom.php';
        #循环编译文件
        foreach ($this->installedModules as $key => $value) {
            if ($value['type'] == "vue") {
                #初始化配置
                $scoped = false;
                $script = "";
                #获取DOM数据信息
                $html = str_get_html($value['body']);
                #处理CSS样式做作用域
                $style = $this->intStyleInfo($html->find('style', 0), $value['code']);
                #存储样式
                $this->style[] = $style && @$style[0] ? $style[0] : '';
                #处理样式作用域
                $scoped = $style && @$style[1] ? $style[1] : false;
                #处理模板功能
                $template = $this->intTemplateinfo($html->find('template', 0), $scoped);
                #获取JS文件信息
                $content = $html->find('script', 0)->innertext;
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
     * 文件信息
     * @param  string $fileName   文件路径
     * @param  string $fileAlias  文件别名
     * @param  string $parentPath 父级目录
     * @return [type]             [description]
     */
    public function getTraceFile2($fileName = '', $fileAlias = '', $parentPath = "") {
        #读取扩展名
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        #判断是否是../目录
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
        #判断是否为路由
        if ($fileAlias == $fileName && $fileAlias == "phpRouter" && $this->is_router) {
            $fileName = $this->phpRouter;
        }
        if ($fileAlias == $fileName && $fileAlias == "sonRouter" && $this->is_router) {
            $fileName = $this->sonRouter;
        }
        #如果文件有扩展名
        if ($extension) {
            #检查依赖文件是否存在
            if (!is_file($fileName)) {
                return false;
            }
            #如果是CSS样式转样式模块
            if (strtolower($extension) == "css") {
                return [$fileName, $extension];
            }
            #如果是JS插件转插件模块
            if (strtolower($extension) == "js") {
                return [$fileName, $extension];
            }
        } else {
            #判断处理为插件依赖模块
            $dependencies = array_keys($this->config['dependencies']);
            if (in_array($fileAlias, $dependencies)) {
                $fileName = "./plugin/" . $fileAlias . ".js";
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
     * 文件信息
     * @param  string $fileName   文件路径
     * @param  string $fileAlias  文件别名
     * @param  string $parentPath 父级目录
     * @return [type]             [description]
     */
    public function getTraceFile($fileName = '', $fileAlias = '', $parentPath = "") {
        #读取扩展名
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        #判断是否是../目录
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
        #判断是否为路由
        if ($fileAlias == $fileName && $fileAlias == "phpRouter" && $this->is_router) {
            $fileName = $this->phpRouter;
        }
        if ($fileAlias == $fileName && $fileAlias == "sonRouter" && $this->is_router) {
            $fileName = $this->sonRouter;
        }
        #如果文件有扩展名
        if ($extension) {
            #检查依赖文件是否存在
            if (!is_file($fileName)) {
                return false;
            }
            #如果是CSS样式转样式模块
            if (strtolower($extension) == "css") {
                return [$fileName, $extension];
            }
            #如果是JS插件转插件模块
            if (strtolower($extension) == "js") {
                return [$fileName, $extension];
            }
        } else {
            #判断处理为插件依赖模块
            $dependencies = array_keys($this->config['dependencies']);
            if (in_array($fileAlias, $dependencies)) {
                $fileName = "./plugin/" . $fileAlias . ".js";
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
     * 获取模板内容信息
     * @param  string $document [description]
     * @return [type]           [description]
     */
    private function intTemplateinfo($tpl = '', $scoped = false) {
        #获取模板文件 - 废弃
        //$document = $tpl->find('template', 0);
        if ($tpl) {
            #处理template子集
            foreach ($tpl->children() as $key => $val) {
                $this->tagDispose($val, $scoped);
            }
            return $tpl->innertext;
        } else {
            return '';
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
        #CSS语法问题
        switch ($style->less) {
        case 'less':
            require_once dirname(dirname(__FILE__)) . "/bin/lessc.inc.php";
            if (!$this->less) {
                $this->less = new \lessc();
            }
            $content = $this->less->compile($content);
            break;
        case 'scss':
        case 'sass':
            require_once dirname(dirname(__FILE__)) . "/bin/scss.inc.php";
            if (!$this->scss) {
                $this->scss = new \scssc();
            }
            $content = $this->scss->compile($content);
            break;
        }
        $style = $scoped ? $this->cssScoped($content, $scoped) : $content;
        return [$style, $scoped];
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
     * 检查JS代码错误
     * @param  string $value [description]
     * @return [type]        [description]
     */
    private function checkScript($body = '', $script = '', $file = '') {
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
     * 设置数据存储
     * @param string $fileName  [description]
     * @param string $extension [description]
     */
    public function setDatasTore($fileName = '', $extension = '') {
        #获取样式信息
        $body = file_get_contents($fileName);
        $code = md5_file($fileName);
        switch ($extension) {
        case 'css':
            $this->styleModules[$code] = array(
                "file" => $fileName,
                "code" => $code,
                "body" => $body,
            );
            break;
        case 'js':
            #存储样式模块
            $this->styleModules[$code] = array(
                "file" => $fileName,
                "code" => $code,
                "body" => $body,
            );
            break;
        }
    }
}