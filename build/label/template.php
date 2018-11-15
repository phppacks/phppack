<?php
/**
 * this7 PHP Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2016-2018 Yan TianZeng<qinuoyun@qq.com>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://www.ub-7.com
 */
namespace phppacks\phppack\label;

#模板解析
class template extends basics {
    /**
     * blade模板(父级)
     * @var array
     */
    private $blade = [];

    /**
     * less对象
     * @var [type]
     */
    private $less;

    /**
     * scss对象
     * @var [type]
     */
    private $scss;

    /**
     * blockshow模板(父级)
     * @var array
     */
    private static $widget = [];

    /**
     * 是否设置作用域
     * @var boolean
     */
    private $scoped = false;

    /**
     * block 块标签
     * level 嵌套层次
     */
    public $tags
    = [
        'style'    => ['block' => TRUE, 'level' => 1],
        'template' => ['block' => TRUE, 'level' => 5],
        'script'   => ['block' => TRUE, 'level' => 1],
    ];

    /**
     * 获取模板信息
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function _template($attr, $content, &$ubdata) {

        P($content);
    }

    /**
     * 获取JS脚本
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     * $c="/\{([^{}]+|(?R))*\}/"; 原始语句
     */
    public function _script($attr, $content, &$ubdata) {

    }

    /**
     * 返回代码所在行
     * @return [type] [description]
     */
    public function getRowsread() {

        $array = explode(PHP_EOL, $this->content);
        $line  = 1;
        foreach ($array as $key => $value) {
            $preg = "#\<script(.+?)\>#is";
            if (preg_match($preg, $value, $matches)) {
                $s = trim($matches[1]);
                if (md5($s) !== md5('type="text/json"') && $s !== md5("type='text/json'")) {
                    $line = $key;
                }
            }
        }
        // P([$this->info['name'], $line]);

        $this->info['line'] = $line;
    }

    /**
     * 获取样式信息
     * @Author   Sean       Yan
     * @DateTime 2018-06-28
     * @param    [type]     $attr    [description]
     * @param    [type]     $content [description]
     * @param    [type]     &$ubdata [description]
     * @return   [type]              [description]
     */
    public function _style($attr, $content, &$ubdata) {

    }

}