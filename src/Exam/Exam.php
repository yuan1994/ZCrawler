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

namespace ZCrawler\Exam;

use Psr\Http\Message\ResponseInterface;
use ZCrawler\Core\AbstractAPI;
use ZCrawler\Core\Config;
use ZCrawler\Support\Parser;

class Exam extends AbstractAPI
{
    /**
     * Query current exam
     *
     * @return array
     */
    public function current()
    {
       $url = Config::get('url.exam_basic');

        $response = $this->request('get', [$url]);

        return $this->parseExam($response);
    }

    /**
     * Get exam by the year and term
     *
     * @param string $year
     * @param string|int $term
     *
     * @return array
     */
    public function getByTerm($year, $term)
    {
        $url = Config::get('url.exam_basic');

        $param['__VIEWSTATE'] = true;
        $param['__EVENTTARGET'] = "xnd";
        $param['__EVENTARGUMENT'] = "";
        $param['xnd'] = $year;
        $param['xqd'] = $term;

        $response = $this->request('post', [$url, $param]);

        return $this->parseExam($response);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    private function parseExam(ResponseInterface $response)
    {
        $body = $this->checkAndThrow($response);

        // form => 考试形式, district => 校区
        return Parser::table($body, '#DataGrid1', [
            'code', 'name', 'student_name', 'time', 'location', 'form', 'seat', 'district'
        ], 8);
    }

    /**
     * Query current make-up exam
     *
     * @return array
     */
    public function currentMakeUp()
    {
        $url = Config::get('url.exam_make_up');

        $response = $this->request('get', [$url]);

        return $this->parseMakeUpExam($response);
    }

    /**
     * Get make-up exam by the year and term
     *
     * @param string $year
     * @param string|int $term
     *
     * @return array
     */
    public function getMakeUpByTerm($year, $term)
    {
        $url = Config::get('url.exam_make_up');

        $param['__VIEWSTATE'] = true;
        $param['__EVENTTARGET'] = "xqd";
        $param['__EVENTARGUMENT'] = "";
        $param['__VIEWSTATEGENERATOR'] = "3F4872EB";
        $param['xnd'] = $year;
        $param['xqd'] = $term;

        $response = $this->request('post', [$url, $param]);

        return $this->parseMakeUpExam($response);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    private function parseMakeUpExam(ResponseInterface $response)
    {
        $body = $this->checkAndThrow($response);

        // form => 考试形式, district => 校区
        return Parser::table($body, '#DataGrid1', [
            'code', 'name', 'student_name', 'time', 'location', 'seat', 'form'
        ], 7);
    }
}
