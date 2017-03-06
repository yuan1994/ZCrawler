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

class Main extends AbstractAPI
{
    /**
     * 获取姓名与菜单
     *
     * @return array
     */
    public function menu()
    {
        $url = Config::get('url.main_page');
        // 下载网页
        $response = $this->request('get', [$url]);
        $body = $this->checkAndThrow($response);

        // 分析网页
        $menu = [];
        $crawler = new Crawler($body);
        $crawler->filter('.sub')->each(function (Crawler $nodeUl, $i) use (&$menu) {
            $nodeMenu = $nodeUl->filter('li > a');
            if ($nodeMenu->count() > 0) {
                $nodeMenu->each(function (Crawler $nodeA, $i) use (&$menu) {
                    $urlRaw = $nodeA->attr('href');
                    if ($urlRaw != '#') {
                        $urlParsed = parse_url($urlRaw);
                        if (isset($urlParsed['path'])) {
                            $menu[] = [
                                'url_raw' => $urlRaw,
                                'url_base' => $urlParsed['path'],
                                'title' => $nodeA->html(),
                            ];
                        }
                    }
                });
            }
        });
        $info['student_number'] = $this->getUsername();
        $info['student_name'] = mb_substr($crawler->filter('#xhxm')->html(), 0, -2);

        return ['menu' => $menu, 'info' => $info];
    }
}
