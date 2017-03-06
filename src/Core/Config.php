<?php
/**
 * yuan1994/ZCrawler
 *
 * @author        yuan1994 <tianpian0805@gmail.com>
 * @link          https://github.com/yuan1994/ZCrawler
 * @documentation http://zcrawler.yuan1994.com
 * @copyright     2017 yuan1994 all rights reserved.
 * @license       http://www.apache.org/licenses/LICENSE-2.0
 */

namespace ZCrawler\Core;

use ZCrawler\Support\Arr;

class Config
{
    /**
     * @var array 配置项
     */
    private static $config = [];

    /**
     * 初始化配置项
     *
     * @param array $config
     */
    public static function init($config = [])
    {
        if (!self::$config) {
            self::$config = include __DIR__ ."/default-config.php";
        }
        self::$config = Arr::array_merge_multi(self::$config, $config);
    }

    /**
     * 判断配置是否为空
     *
     * @return bool
     */
    public static function isEmpty()
    {
        return empty(self::$config);
    }

    /**
     * 获取配置参数
     * 为空则获取所有配置，支持点语法
     *
     * @param string $name    配置参数名
     * @param mixed  $default 默认值
     *
     * @return mixed
     */
    public static function get($name = null, $default = null)
    {
        if (!$name) {
            return self::$config;
        } else {
            $keys = explode('.', $name);
            $ret = self::$config;
            while ($key = array_shift($keys)) {
                if (isset($ret[$key])) {
                    $ret = $ret[$key];
                } else {
                    return $default;
                }
            }

            return $ret;
        }
    }

    /**
     * 设置配置参数
     * 支持点语法，最多三级
     *
     * @param string|array $name  配置参数名
     * @param mixed        $value 配置值
     */
    public static function set($name, $value)
    {
        $keys = explode('.', $name);
        switch (count($keys)) {
            case 1:
                self::$config[$keys[0]] = $value;
                break;
            case 2:
                self::$config[$keys[0]][$keys[1]] = $value;
                break;
            case 3:
                self::$config[$keys[0]][$keys[1]][$keys[2]] = $value;
                break;
        }
    }

    /**
     * Delete config
     *
     * @param string $name
     */
    public static function delete($name)
    {
        $keys = explode('.', $name);
        switch (count($keys)) {
            case 1:
                unset(self::$config[$keys[0]]);
                break;
            case 2:
                unset(self::$config[$keys[0]][$keys[1]]);
                break;
            case 3:
                unset(self::$config[$keys[0]][$keys[1]][$keys[2]]);
                break;
        }
    }
}
