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

use Symfony\Component\DomCrawler\Crawler;

class Parser
{
    /**
     * Parser the schedule data.
     *
     * @param string|Crawler $body
     * @param string         $selector
     *
     * @return array
     */
    public static function schedule($body, $selector = '#Table1')
    {
        $array = [];

        $crawler = $body instanceof Crawler ? $body : new Crawler((string)$body);
        // to array
        $crawler->filter($selector)->children()->each(function (Crawler $node, $i) use (&$array) {
            // 删除前两行
            if ($i < 2) {
                return false;
            }
            $array[] = $node->children()->each(function (Crawler $node, $j) {
                $span = (int)$node->attr('rowspan') ?: 0;

                return [$node->html(), $span];
            });
        });

        // If there are some classes in the table is in two or more lines,
        // insert it into the next lines in $array.
        $lineCount = count($array);
        $schedule = [];
        for ($i = 0; $i < $lineCount; $i++) {  // lines
            for ($j = 0; $j < 9; $j++) {    // rows
                if (isset($array[$i][$j])) {
                    $k = $array[$i][$j][1];
                    while (--$k > 0) { // insert element to next line
                        // Set the span 0
                        $array[$i][$j][1] = 0;
                        $array[$i + $k] = array_merge(
                            array_slice($array[$i + $k], 0, $j),
                            [$array[$i][$j]],
                            array_splice($array[$i + $k], $j)
                        );
                    }
                }
                $schedule[$i][$j] = isset($array[$i][$j][0]) ? $array[$i][$j][0] : '';
            }
        }

        return $schedule;
    }

    /**
     * Parser the common table, like cet, chooseClass, etc.
     *
     * @param string|object|Crawler $body
     * @param string                $selector
     * @param array                 $header
     * @param int                   $columnCount
     * @param int                   $startLine
     * @param string                $getValueMethod
     *
     * @return array
     */
    public static function table($body, $selector = '#DataGrid1', $header = [], $columnCount = 0, $startLine = 1, $getValueMethod = 'html')
    {
        $dataList = [];

        $crawler = $body instanceof Crawler ? $body : new Crawler((string)$body);

        $crawler->filter($selector)->children()->each(function (Crawler $node, $i) use ($startLine, $columnCount, $header, $getValueMethod, &$dataList) {
            $nodeTd = $node->children();
            if ($i < $startLine || ($columnCount && $nodeTd->count() < $columnCount)) {
                return false;
            }

            $itemList = [];
            if ($header) {
                foreach ($header as $index => $key) {
                    if (is_array($key) && isset($key[1]) && is_callable($key[1])) {
                        $func = $key[1];
                        $itemList[$key[0]] = call_user_func_array($func, [$nodeTd->eq($index), $getValueMethod]);
                    } elseif ($key) {
                        $itemList[$key] = $nodeTd->eq($index)->$getValueMethod();
                    }
                }
            } else {
                $itemList = $nodeTd->each(function (Crawler $node, $j) use ($getValueMethod) {
                    return $node->$getValueMethod();
                });
            }
            $dataList[] = $itemList;
        });

        return $dataList;
    }

    /**
     * Parser the form control name`s attribute
     *
     * @param string|object|Crawler $body
     * @param string                $fieldName
     * @param string                $attr
     *
     * @return string
     */
    public static function fieldName($body, $fieldName, $attr = 'value')
    {
        $crawler = $body instanceof Crawler ? $body : new Crawler((string)$body);

        return $crawler->filterXPath('//input[@name="' . $fieldName . '"]')->attr($attr);
    }

    /**
     * 解析课表上课时间
     * 有一种特殊格式的上课时间信息，请注意特殊处理：经济学原理|{第3-18周|3节/周}|高歌|电110
     *
     * @param string $str
     *
     * @return array
     */
    public static function scheduleClassHour($str)
    {
        $strReplaced = preg_replace('/(<br.*?>)/i', '|', $str);

        $pattern = "/([^\|]*)\|(周(.*?)第([,\d]+)节)?\{第([-\d]+)周(\|(.*?)周)?\}\|(.*?)\|([^\|]*)(\|(\d{4}).*?(\d+).*?(\d+).*?\((.*?)\))?(\|([^\|]*))?(\|\|)?/";
        $list = [];
        if (preg_match_all($pattern, $strReplaced, $matches, PREG_SET_ORDER)) {
            $mapField = [
                1  => 'name',
                3  => 'week',
                4  => 'time_section',
                5  => 'week_section',
                7  => 'week_remark',
                8  => 'teacher',
                9  => 'location',
                11 => 'exam_y',
                12 => 'exam_m',
                13 => 'exam_d',
                14 => 'exam_h',
            ];
            foreach ($matches as $match) {
                $item = [];
                foreach ($mapField as $index => $field) {
                    $item[$field] = isset($match[$index]) ? $match[$index] : '';
                }
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * 解析考试时间
     *
     * @param string $str
     *
     * @return array
     */
    public static function examTime($str)
    {
        $pattern = "/(\d+).*?(\d+).*?(\d+).*?([\d:-]+)/";
        $ret = [];
        if (preg_match($pattern, $str, $matches)) {
            $ret['date'] = $matches[1] . "-" . $matches[2] . "-" . $matches[3];
            $ret['time'] = $matches[4];
        }

        return $ret;
    }

    /**
     * 选课时间解析
     * 例如：周六第3,4,5节{第3-10周}
     *
     * @param string $str
     *
     * @return array
     */
    public static function scheduleSelectTime($str)
    {
        $pattern = '/周(.*?)第([,\d]+).*?([-\d]+)周(\|(.*?)周)?/';

        $ret = [];
        if (preg_match($pattern, $str, $matches)) {
            $ret = [
                'week'         => $matches[1],
                'time_section' => $matches[2],
                'week_section' => $matches[3],
                'week_remark'  => isset($matches[5]) ? $matches[5] : '',
            ];
        }

        return $ret;
    }

    /**
     * 解析有多个选课时间组合的时间
     * 例如：周六第3,4,5节{第3-10周};周六第8,9,10节{第3-10周}
     *
     * @param string $str
     *
     * @return array
     */
    public static function multiScheduleSelectTime($str)
    {
        $arr = explode(';', $str);

        $ret = [];
        foreach ($arr as $item) {
            if ($result = static::scheduleSelectTime($item)) {
                $ret[] = $result;
            }
        }

        return $ret;
    }

    /**
     * 解析长课程代码为详细的课程代码
     *
     * @param string $str
     *
     * @return array
     */
    public static function courseCode($str)
    {
        $pattern = '/(\d+-\d+)-(\d)\)-([^-]+)-([^-]+)-(\d+)/';

        $ret = [];
        if (preg_match($pattern, $str, $matches)) {
            $ret = [
                'year'               => $matches[1],
                'term'               => $matches[2],
                'code'               => $matches[3],
                'teacher_job_number' => $matches[4],
                'no'                 => $matches[5],
            ];
        }

        return $ret;
    }
}
