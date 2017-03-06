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

namespace ZCrawler\Schedule;

use Symfony\Component\DomCrawler\Crawler;
use ZCrawler\Core\AbstractAPI;
use ZCrawler\Core\Config;
use ZCrawler\Support\Parser;

class Select extends AbstractAPI
{
    /**
     * 全校性通识课列表
     *
     * @param string $district 校区
     *
     * @return array
     */
    public function schoolWide($district = '')
    {
        $url = Config::get('url.schedule_select_school_wide');

        $param['__EVENTTARGET'] = '';
        $param['__EVENTARGUMENT'] = '';
        $param['__VIEWSTATE'] = true;
        $param['ddl_kcxz'] = true;
        $param['ddl_ywyl'] = '';
        $param['ddl_kcgs'] = '';
        $param['ddl_xqbs'] = $district;
        $param['ddl_sksj'] = '';
        $param['TextBox1'] = '';
        $param['Button2'] = '';
        $param['dpkcmcGrid:txtChoosePage'] = 1;
        $param['dpkcmcGrid:txtPageSize'] = 1000;

        $response = $this->request('post', [$url, $param]);

        $body = $this->checkAndThrow($response);

        // 通识课列表
        $crawler = new Crawler($body);
        $dataList = Parser::table($crawler, '#kcmcGrid', [
            '', '', 'name','code', 'teacher',
            ['time', function (Crawler $crawler, $getValueMethod) {
                return $crawler->attr('title') ?: $crawler->text();
            }],
            'location', 'credit' ,'hour', 'week', 'volume', 'volume_surplus',
            'affiliation', 'property', 'district', 'campus', 'exam_time'
        ], 17, 1, 'text');
        // 校区
        $districtList = $crawler->filterXPath('//select[@id="ddl_xqbs"]//option')->each(function (Crawler $node, $i) {
            return [
                'text'  => $node->text(),
                'value' => $node->attr('value'),
            ];
        });

        // 课程名称,教师姓名,学分,周学时,起始结束周,校区,上课时间,上课地点,课程归属,课程性质,校区代码
        $selectedList = Parser::table($crawler, '#DataGrid2', [
            'name', 'teacher', 'credit', 'week', 'week_start_to_end', 'district',
            'hour', 'location', '', 'affiliation', 'property', 'code'
        ], 10, 1, 'text');

        return ['list' => $dataList, 'selected' => $selectedList, 'district' => $districtList];
    }

    /**
     * 已选择课表
     *
     * @return array
     */
    public function selected()
    {
        // 请求参数
        $param['__EVENTTARGET'] = '';
        $param['__EVENTARGUMENT'] = '';
        $param['__VIEWSTATE'] = true;
        $param['zymc'] = "";
        $param['xx'] = "";
        $param['Button8'] = iconv('utf-8', 'gb2312', '已选课程');

        $header = [
            'code', 'name', 'property', 'direction', 'credit', 'hour', 'exam_time', '', 'status'
        ];

        return $this->getSelect($param, $param, $header, 10);
    }

    /**
     * 专业可选课列表
     *
     * @return array
     */
    public function major()
    {
        // 请求参数
        $param['__EVENTTARGET'] = '';
        $param['__EVENTARGUMENT'] = '';
        $param['__VIEWSTATE'] = true;
        $param['DrDl_Nj'] = "2014";
        $param['zymc'] = "";
        $param['xx'] = "";

        $header = [
            'code', 'name', 'property', 'direction', 'credit', 'hour', 'exam_time', '', 'status'
        ];

        return $this->getSelect([], $param, $header, 9);
    }

    /**
     * 获取选课
     *
     * @param array $paramFirst 首次请求参数
     * @param array $paramLoop 循环时的参数
     * @param array $header
     * @param int   $columnCount
     *
     * @return array
     */
    private function getSelect($paramFirst = [], $paramLoop = [], $header = [], $columnCount = 0)
    {
        $url = Config::get('url.schedule_selected');

        $dataList = [];
        $param = $paramFirst;
        while (true) {
            $response = $this->request('post', [$url, $param]);

            $body = $this->checkAndThrow($response);

            if ($body) {
                $crawler = new Crawler($body);
                // 数据列表
                $dataList = array_merge($dataList, Parser::table($crawler, '#kcmcgrid', $header, $columnCount, 1, 'text'));

                // 翻页菜单
                $spanNode = $crawler->filterXPath('//table[@id="kcmcgrid"]//td[@colspan="15"]//span');
                // 下一页
                $nextNode = $spanNode->nextAll();
                if ($nextNode->count() == 0) {
                    break;
                }
                if (!preg_match('/\'(.*?)\'/', $nextNode->attr('href'), $matches)) {
                    break;
                }
                // 参数重新赋值
                $param = $paramLoop;
                $param['__EVENTTARGET'] = str_replace('$', ":", $matches[1]);
                $param['__VIEWSTATE'] = $crawler->filterXPath('//input[@name="__VIEWSTATE"]')->attr('value');
                $param['zymc'] = $crawler->filterXPath('//input[@name="zymc"]')->attr('value');
            }
        }

        return $dataList;
    }

    /**
     * 体育选课列表
     *
     * @return mixed
     */
    public function physical()
    {
        $url = Config::get('url.schedule_select_physical');

        $response = $this->request('get', [$url]);

        $body = $this->checkAndThrow($response);

        $crawlerEnter = new Crawler($body);
        $param['__EVENTTARGET'] = 'kj';
        $param['__EVENTARGUMENT']= '';
        $param['__VIEWSTATE'] = $crawlerEnter->filterXPath('//input[@name="__VIEWSTATE"]')->attr('value');
        $param['kj'] = iconv('utf-8', 'gb2312', $crawlerEnter->filterXPath('//select[@name="kj"]//option')->last()->attr('value'));

        $response = $this->request('post', [$url, $param]);

        $body = $this->checkAndThrow($response);

        $crawler = new Crawler($body);
        // 可选列表
        $ret['list'] =  Parser::table($crawler, '#kcmcGrid', [
            'name', 'teacher', 'hour', 'location', 'credit', 'week', 'volume', 'volume_surplus'
        ], 11, 1, 'text');
        // 选课课号,课程名称,教师姓名,学分,周学时,上课时间,上课地点,年级、专业限制
        $ret['selected'] = Parser::table($crawler, '#DataGrid2', [
            'code', 'name', 'teacher', 'credit', 'week', 'hour', 'location', 'limit'
        ], 8, 1, 'text');

        return $ret;
    }
}
