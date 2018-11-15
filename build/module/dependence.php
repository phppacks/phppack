<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-15 17:39:37
 */
use \phppacks\phppack\label\template;

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
     * 安装模块
     * @var array
     */
    public $installedModules = array();

    /**
     * 样式模块
     */
    public $styleModules = array();

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
        P("-------getEntry-------");

        #改变目录
        chdir($this->path);

        #循环读取文件-目前只支持一体化
        foreach ($this->config['entry'] as $key => $value) {
            $this->getFileInfo($key, $value);
        }
        #模板文件分离模式
        $this->getDomDocument();
    }

    /**
     * 获取DOM信息
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function getDomDocument($value = '') {
        #加载DOM插件
        require dirname(__DIR__) . '/bin/simple_html_dom.php';
        #执行模块编译
        $obj = new template();
        #循环编译文件
        foreach ($this->installedModules as $key => $value) {
            if ($value['type'] == "vue") {

                $html     = str_get_html($value['body']);
                $template = $html->find('template', 0)->innertext;
                $script   = $html->find('script', 0)->innertext;
                $style    = $html->find('style', 0)->innertext;
                echo $script;

                $html->clear();
                // $obj->parse($value['body'], $this);
                exit();
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
                "name"    => $name,
                "type"    => $type,
                "file"    => $file,
                "code"    => $code,
                "require" => $alias ? [$alias] : [],
                "body"    => $body,
            );
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
}