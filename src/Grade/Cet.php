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

namespace ZCrawler\Grade;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use ZCrawler\Core\AbstractAPI;
use ZCrawler\Core\Exceptions\HttpException;
use ZCrawler\Core\Config;
use ZCrawler\Support\Parser;

class Cet extends AbstractAPI
{
    /**
     * Query student CET4 and CET6 grade
     *
     * @return array
     */
    public function grade()
    {
        $url = Config::get('url.grade_class_exam');

        $response = $this->request('get', [$url]);

        $body = $this->checkAndThrow($response);

        return Parser::table($body, '#DataGrid1', [
            'year', 'term', 'name', 'number', 'date', 'grade_all', 'grade_listening',
            'grade_reading', 'grade_writing', 'grade_synthesizing',
        ], 10);
    }


    /**
     * 四六级报名展示页
     *
     * @param string $photoSavePath
     * @param string $saveName
     *
     * @return array
     */
    public function page($photoSavePath = '', $saveName = '')
    {
        $this->protocol();

        $url = Config::get('url.cet_apply');

        $response = $this->request('get', [$url]);

        return $this->parseCet($response, $photoSavePath, $saveName);
    }

    /**
     * 四六级报名提交页
     *
     * @param string $itemName
     * @param string $idCard
     * @param string $photoSavePath
     * @param string $saveName
     *
     * @return array
     */
    public function submit($itemName, $idCard, $photoSavePath = '', $saveName = '')
    {
        $this->protocol();

        $url = Config::get('url.cet_apply');

        $param['__EVENTTARGET'] = "";
        $param['__EVENTARGUMENT'] = "";
        $param['__VIEWSTATE'] = true;
        $param['txtxxmc'] = "";
        $param[$itemName] = "on";
        $param['txtSFZH'] = $idCard;
        $param['btnSubmit'] = iconv('utf-8', 'gb2312', ' 确 定 ');
        $param['TextBox1'] = "";

        $files['File1'] = '';

        $response = $this->request('upload', [$url, $param, [], $files]);

        return $this->parseCet($response, $photoSavePath, $saveName);
    }

    /**
     * 四六级退选
     *
     * @param string $itemName 删除id
     * @param string $photoSavePath
     * @param string $saveName
     *
     * @return array
     */
    public function delete($itemName, $photoSavePath = '', $saveName = '')
    {
        $this->protocol();

        $url = Config::get('url.cet_apply');

        $param['__EVENTTARGET'] = $itemName;
        $param['__EVENTARGUMENT'] = "";
        $param['__VIEWSTATE'] = true;
        $param['txtxxmc'] = "";
        $param["txtSFZH"] = "";
        $param["TextBox1"] = "";

        $response = $this->request('upload', [$url, $param]);

        return $this->parseCet($response, $photoSavePath, $saveName);
    }

    /**
     * @param ResponseInterface $response
     * @param string            $photoSavePath
     * @param string            $saveName
     *
     * @return array
     */
    private function parseCet(ResponseInterface $response, $photoSavePath = '', $saveName = '')
    {
        $body = $this->checkAndThrow($response);

        $crawler = new Crawler($body);
        // 教务信息
        $ret['info'] = [
            'year'   => $crawler->filter('#Label1')->text(),
            'term'   => $crawler->filter('#Label2')->text(),
            'remark' => $crawler->filter('#Label3')->text(),
        ];
        // 可报名列表
        $ret['list'] = Parser::table($crawler, '#DBGrid', [
            ['chk', function (Crawler $crawler, $getValueMethod) {
                return $crawler->filter('input')->attr('name');
            }],
            'name', 'type', '', 'can_apply', 'require', 'fit', 'limit', 'remark', 'markup',
        ], 0, 1);
        // 已报名列表
        $ret['applied'] = Parser::table($crawler, '#DBGridInfo', [
            'no', 'name', '', '', 'id_card', 'is_pay', 'original_no', 'reserve_type', 'reserve_grade', ['delete_id', function (Crawler $crawler, $getValueMethod) {
                if (preg_match('/\'(.*?)\'/', $crawler->filter('a')->attr('href'), $matches)) {
                    return str_replace('$', ":", $matches[1]);
                }

                return '';
            }],
        ], 8);
        // 学生信息
        $ret['student'] = [
            'photo'   => $crawler->filter('#xszp')->attr('src'),
            'id_card' => $crawler->filter('#Labsfzh')->text(),
        ];
        // 是否下载学生照片
        if ($photoSavePath) {
            if (!$saveName) {
                $saveName = 'student-photo-' . $this->getUsername() . '.png';
            }
            // 头像下载地址
            $downloadUrl = Config::get('url.student_photo');
            // 下载文件
            $response = $this->request('get', [$downloadUrl, [], [
                'sink' => $photoSavePath . $saveName,
            ]]);

            $ret['student']['photo_save'] = $saveName;
        }

        return $ret;
    }

    /**
     * 四六级协议
     *
     * @throws HttpException
     */
    private function protocol()
    {
        $cacheKey = 'cet_apply-' . $this->getUserName();

        if (!$this->getCache()->fetch($cacheKey)) {
            $url = Config::get('url.cet_protocol');

            $param['__VIEWSTATE'] = true;
            $param['__VIEWSTATEGENERATOR'] = "C510EDC3";
            $param['TextBox1'] = "C510EDC3";
            $param['Button1'] = iconv('utf-8', 'gb2312', '我已认真阅读，并同意以上相关规定');

            $response = $this->request('post', [$url, $param]);

            $body = $this->checkAndThrow($response);

            if (!preg_match('/bmxmb/', $body)) {
                throw new HttpException('四六级协议同意失败', 10008);
            }

            $this->getCache()->save($cacheKey, true, Config::get('cache.expire', 100));
        }
    }
}
