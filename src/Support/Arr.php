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

namespace ZCrawler\Support;

class Arr
{
    /**
     * 多维数组合并（支持多数组）
     *
     * @return array
     */
    public static function array_merge_multi()
    {
        $args = func_get_args();
        $array = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $k => $v) {
                    if (is_array($v)) {
                        $array[$k] = isset($array[$k]) ? $array[$k] : [];
                        $array[$k] = static::array_merge_multi($array[$k], $v);
                    } else {
                        $array[$k] = $v;
                    }
                }
            }
        }

        return $array;
    }
}
