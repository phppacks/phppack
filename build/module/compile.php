<?php
namespace phppacks\phppack\module;
/**
 * @Author: qinuoyun
 * @Date:   2018-11-15 11:07:08
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-11-30 10:48:07
 */

class compile extends basics {

    public function bootstrap($router = '') {
        if ($router == 'single') {
            $this->single();
        }
        #获取配置文件
        $this->intConfig();
        #解析入口文件-返回所有数据
        $data = $this->getEntry();
        extract($data);
        #分解后的数据部署
        $js     = to_json($js);
        $css    = to_json($css);
        $style  = $this->styleModules;
        $config = to_json($this->config);
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
     * 页面存储-API调用
     * @param  string $value [description]
     * @return [type]        [description]
     */
    protected function single() {
        $data = array(
            'compontent' => base64_decode($_POST['compontent']),
            'style'      => base64_decode($_POST['style']),
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

}