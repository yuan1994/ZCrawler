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

namespace ZCrawler\User;

use Symfony\Component\DomCrawler\Crawler;
use ZCrawler\Core\AbstractAPI;
use ZCrawler\Core\Config;

class Notice extends AbstractAPI
{
    /**
     * 获取内容列表
     *
     * @return array
     */
    public function contentList()
    {
        $url = Config::get('url.content_list');
        // 下载网页
        $response = $this->request('get', [$url]);
        $body = $this->checkAndThrow($response);
        // 分析网页
        $crawler = new Crawler($body);
        $list = [];
        $crawler->filter('.datelist')->children()
            ->each(function (Crawler $node, $i) use (&$list) {
                $nodeTd = $node->children();
                if ($i > 0 && $nodeTd->count() == 4) {
                    $nodeTitle = $nodeTd->eq(0)->filter('a');
                    if (preg_match("/'([^']*)/", $nodeTitle->attr('onclick'), $matches)) {
                        $item['url'] = $matches[1];
                        $item['title'] = $nodeTitle->html();
                        $item['unit'] = $nodeTd->eq(1)->html();
                        $item['publish_time'] = $nodeTd->eq(2)->html();
                        $item['expire_time'] = $nodeTd->eq(3)->html();
                        $list[] = $item;
                    }
                }

            });

        return $list;
    }

    /**
     * 获取内容详情
     *
     * @param string $url
     * @param string $attachSavePath
     *
     * @return mixed
     */
    public function contentDetail($url, $attachSavePath = '')
    {
        // 抓取网页
        $urlParsed = parse_url($url);
        $response = $this->request('get', [$urlParsed['path'], [], [
            'cookies' => false,
            'query'   => isset($urlParsed['query']) ? $urlParsed['query'] : []],
        ]);
        $body = $this->checkAndThrow($response);

        // 分析网页内容
        $crawler = new Crawler($body);
        // 标题
        $detail['title'] = $crawler->filter('#Label2')->html();
        // 主体内容
        $detail['body'] = $crawler->filter('#txtGGSM')->html();
        // 附件
        $attaches = [];
        $attachNode = $crawler->filterXPath('//table[@id="DataGrid1"]//a');
        if ($attachSavePath && $attachNode->count() > 0) {
            $attachNode->each(function (Crawler $crawler, $i) use (&$attaches, $attachSavePath) {
                if ($fileName = $crawler->html()) {
                    $attaches[] = $this->downloadAttach($fileName, $attachSavePath);
                }
            });
        }
        $detail['attaches'] = $attaches;

        return $detail;
    }

    /**
     * 下载附件
     *
     * @param string $fileName       附件名称
     * @param string $attachSavePath 保存路径
     * @param string $saveName       附件保存名称
     *
     * @return array ['file_name' => <FILE_NAME>, 'save_name' => <SAVE_NAME>]
     */
    public function downloadAttach($fileName, $attachSavePath, $saveName = null)
    {
        if (!$saveName) {
            $fileNameArr = explode(".", $fileName);
            $saveName = sha1(uniqid() . $fileNameArr[0]) . '.' . end($fileNameArr);
        }
        // 附件下载地址
        $downloadUrl = Config::get('url.attach_download') . urlencode($fileName);
        // 下载文件
        $response = $this->request('get', [$downloadUrl, [], [
            'cookies' => false,
            'sink'    => $attachSavePath . $saveName,
        ]]);

        return [
            'file_name' => $fileName,
            'save_name' => $saveName,
        ];
    }

    /**
     * 一次性获取所有内容详情
     *
     * @param string $attachSavePath
     *
     * @return array
     */
    public function contentDetailList($attachSavePath = '')
    {
        $list = $this->contentList();

        foreach ($list as &$item) {
            $item['detail'] = $this->contentDetail($item['url'], $attachSavePath);
        }

        return $list;
    }
}
