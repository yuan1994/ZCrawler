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

class School extends AbstractAPI
{
    /**
     * Parse html to array
     *
     * @param string $html
     *
     * @return array
     */
    public function parseHtml($html)
    {
        return Parser::table($html, '#DBGrid', [
            'status', 'name', 'credit', 'examination', 'property', 'teacher', 'code', 'week',
            ['hour', function (Crawler $crawler) {
                return $crawler->attr("title") ?: $crawler->text();
            }],
            ['place', function (Crawler $crawler) {
                return $crawler->text() == ";" ? '' : ($crawler->attr("title") ?: $crawler->text());
            }],
            'campus',
            ['class', function (Crawler $crawler) {
                return $crawler->attr("title") ?: $crawler->text();
            }],
        ], 12, 1, 'text');
    }

    /**
     * Save data to cache
     *
     * @param int    $pageCount
     * @param string $cachePath
     *
     * @return int
     */
    public function saveDataToCache($cachePath)
    {
        $url = Config::get('url.schedule_all_school');

        // 请求参数
        $param['__EVENTTARGET'] = "";
        $param['__EVENTARGUMENT'] = "";
        $param['__VIEWSTATE'] = true;
        $param['ddlXY'] = '';
        $param['ddlJS'] = '';
        $param['kcmc'] = '';
        $param['ddlXN'] = '2016-2017';
        $param['ddlXQ'] = '2';
        $param['DropDownList1'] = 'kcmc';
        $param['TextBox1'] = '';
        $param['Button1'] = iconv('utf-8', 'gb2312', " 查 询 ");
        $pageCount = 0;
        while (true) {
            $response = $this->request('post', [$url, $param]);
            $body = $this->checkAndThrow($response);

            // 写入数据到缓存
            if ($body) {
                $crawler = new Crawler($body);
                // 翻页菜单
                $spanNode = $crawler->filterXPath('//table[@id="DBGrid"]//td[@colspan="19"]//span');
                // 写入数据到缓存
                $pageCount = $spanNode->text();
                file_put_contents($cachePath . $pageCount . '.html', $body);
                // 下一页
                $nextNode = $spanNode->nextAll();
                if ($nextNode->count() == 0) {
                    break;
                }
                if (!preg_match('/\'(.*?)\'/', $nextNode->attr('href'), $matches)) {
                    break;
                }
                // 参数重新赋值
                $param['__EVENTTARGET'] = str_replace('$', ":", $matches[1]);
                $param['__VIEWSTATE'] = $crawler->filterXPath('//input[@name="__VIEWSTATE"]')->attr('value');
                unset($param['Button1']);
            }
        }

        return $pageCount;
    }
}
