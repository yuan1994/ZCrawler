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

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use ZCrawler\Core\AbstractAPI;
use ZCrawler\Core\Config;
use ZCrawler\Login\Login;
use ZCrawler\Support\Parser;

class Schedule extends AbstractAPI
{
    /**
     * Request url
     *
     * @var string
     */
    private $requestUrl = '';

    /**
     * Schedule constructor.
     *
     * @param Login $login
     */
    public function __construct(Login $login)
    {
        parent::__construct($login);

        $this->requestUrl = Config::get('url.schedule_basic');
    }

    /**
     * Get the current term`s schedule
     *
     * @return array
     */
    public function current()
    {
        $url = $this->requestUrl;

        $response = $this->request('get', [$url]);

        return $this->parseSchedule($response);
    }

    /**
     * Get schedule by the year and term
     *
     * @param string     $year
     * @param string|int $term
     *
     * @return array
     */
    public function getByTerm($year, $term)
    {
        $url = $this->requestUrl;

        $param['__VIEWSTATE'] = true;
        $param['__EVENTTARGET'] = "xnd";
        $param['__EVENTARGUMENT'] = "";
        $param['xnd'] = $year;
        $param['xqd'] = $term;

        $response = $this->request('post', [$url, $param]);

        return $this->parseSchedule($response);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    private function parseSchedule(ResponseInterface $response)
    {
        $body = $this->checkAndThrow($response);

        $crawler = new Crawler($body);

        // 正常课表
        $ret['normal'] = Parser::schedule($crawler, '#Table1');
        // 未安排上课时间的课表
        $ret['no_time'] = Parser::table($crawler, '#Datagrid2', [
            'year', 'term', 'name', 'teacher', 'credit'
        ], 5);
        // 学生信息
        $ret['student']['number'] = mb_substr($crawler->filter('#Label5')->text(), 3);
        $ret['student']['name'] = mb_substr($crawler->filter('#Label6')->text(), 3);
        $ret['student']['campus'] = mb_substr($crawler->filter('#Label7')->text(), 3);
        $ret['student']['major'] = mb_substr($crawler->filter('#Label8')->text(), 3);
        $ret['student']['class'] = mb_substr($crawler->filter('#Label9')->text(), 4);

        return $ret;
    }
}
