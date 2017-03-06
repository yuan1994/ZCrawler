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

use Symfony\Component\DomCrawler\Crawler;
use ZCrawler\Core\AbstractAPI;
use ZCrawler\Core\Config;
use ZCrawler\Support\Parser;

class Grade extends AbstractAPI
{
    /**
     * 查询培养计划
     *
     * @return array|bool
     */
    public function trainingPlan()
    {
        $url = Config::get('url.grade_training_plan');

        $all=iconv('utf-8', 'gb2312', '全部');
        $param['__VIEWSTATE'] = true;
        $param['__EVENTTARGET'] = "";
        $param['__EVENTARGUMENT'] = "";
        $param['xq'] = $all;
        $param['kcxz'] = $all;
        $param['dpDBGrid:txtChoosePage'] = 1;
        $param['dpDBGrid:txtPageSize'] = 200;

        $response = $this->request('post', [$url, $param]);

        $body = $this->checkAndThrow($response);

        return Parser::table($body, '#DBGrid', [
            'code', 'name', 'credit', 'hour', 'examination', 'property', 'kind', 'term', '',
            'minor', 'direction', 'group_code', 'module_code', 'status', 'week'
        ], 16, 1, 'text');
    }

    /**
     * 成绩统计
     *
     * @return array
     */
    public function state()
    {
        $url = Config::get('url.grade_basic');

        $button=iconv('utf-8', 'gb2312', '成绩统计');

        $param['__VIEWSTATE'] = true;
        $param['__EVENTTARGET'] = "";
        $param['__EVENTARGUMENT'] = "";
        $param['hidLanguage'] = "";
        $param['ddlXN'] = "";
        $param['ddlXQ'] = "";
        $param['ddl_kcxz'] = "";
        $param['Button1'] = $button;

        $response = $this->request('post', [$url, $param]);

        $body = $this->checkAndThrow($response);

        $crawler = new Crawler($body);

        // 概览
        if (preg_match('/([\d\.]+).*?([\d\.]+).*?([\d\.]+).*?([\d\.]+)/', $crawler->filter('#xftj')->text(), $matches)) {
            $ret['overview']['selected'] = $matches[1];
            $ret['overview']['get'] = $matches[2];
            $ret['overview']['restudy'] = $matches[3];
            $ret['overview']['fail'] = $matches[4];
        }
        // 性质
        $ret['property'] = Parser::table($crawler, '#Datagrid2', [
            'item_name', 'require', 'get', 'fail', 'need'
        ], 5);
        // 归属
        $ret['affiliation'] = Parser::table($crawler, '#DataGrid6', [
            'item_name', 'require', 'get', 'fail', 'need'
        ]);
        // 统计信息
        $ret['info']['people'] = preg_match('/(\d+)/', $crawler->filter('#zyzrs')->text(), $matches) ? $matches[1] : 0;
        $ret['info']['gpa'] = preg_match('/([\.\d]+)/', $crawler->filter('#pjxfjd')->text(), $matches) ? $matches[1] : 0;
        if (preg_match("/([\d\.]+).*?([\d\-]+)\s?(\d+:\d+:\d+)/",$crawler->filter('#xfjdzh')->text(), $matches)){
            $ret['info']['gp_sum'] = $matches[1];
            $ret['info']['update_time'] = $matches[2]." ".$matches[3];
        }

        return $ret;
    }

    /**
     * 历年成绩
     *
     * @return array
     */
    public function history()
    {
        $url = Config::get('url.grade_basic');

        $button=iconv('utf-8', 'gb2312', '历年成绩');

        $param['__VIEWSTATE'] = true;
        $param['__EVENTTARGET'] = "";
        $param['__EVENTARGUMENT'] = "";
        $param['hidLanguage'] = "";
        $param['ddlXN'] = "";
        $param['ddlXQ'] = "";
        $param['ddl_kcxz'] = "";
        $param['btn_zcj'] = $button;

        $response = $this->request('post', [$url, $param]);

        $body = $this->checkAndThrow($response);

        return Parser::table($body, '#Datagrid1', [
            'year', 'term', 'code', 'name', 'property', 'affiliation', 'credit', 'gp', 'grade',
            'minor_flag', 'make_up', 'restudy', 'campus', 'remark', 'restudy_flag'
        ], 15);
    }
}
