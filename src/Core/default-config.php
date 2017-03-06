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

return [
    /**
     * Debug 模式，bool 值：true/false
     *
     * 当值为 false 时，所有的日志都不会记录
     */
    'debug'        => false,

    /**
     * 日志配置
     *
     * level: 日志级别, 可选为：
     *         debug/info/notice/warning/error/critical/alert/emergency
     * permission：日志文件权限(可选)，默认为null（若为null值,monolog会取0644）
     * file：日志文件位置(绝对路径!!!)，要求可写权限
     */
    'log'          => [
        'level'      => 'debug',
        'permission' => 0777,
        'file'       => 'ZCrawler' . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log',
    ],

    /**
     * Guzzle Http 请求选项
     *
     * base_uri: 教务网地址
     * allow_redirects: 是否自动重定向，需禁用自动重定向
     *
     * 其他配置请参考 http://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html
     */
    'http'         => [
        'base_uri'        => 'http://jwgl.buct.edu.cn/',
        'allow_redirects' => false,
    ],

    /**
     * 请求头信息
     *
     * User-Agent: 用户代理，可以模拟浏览器
     */
    'http_headers' => [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36',
    ],

    /**
     * 缓存配置
     *
     * expire: Cookie 和 ViewState 缓存有效期，单位秒 (s)
     */
    'cache'        => [
        'expire' => 150,
    ],

    /**
     * 保留配置
     * 用于数据临时全局缓存，请不要动
     *
     * url_prefix: 请求地址前缀，即链接式 SessionId，例如 (fclbz3454utzkn45qx5yr0j1)/
     * view_state: ViewState 防 xss 参量
     */
    'param'        => [
        'url_prefix' => '',
        'view_state' => [],
    ],

    /**
     * 请求页面地址
     */
    'url'          => [
        // 学生登录后的主页
        'main_page'                   => 'xs_main.aspx',
        // 免验证码登录，自己网上找地址
        'login_no_code'               => 'default4.aspx',
        // 需验证码登录
        'login_with_code'             => 'default2.aspx',
        // 验证码
        'login_captcha'               => 'CheckCode.aspx',
        // 学生个人课表
        'schedule_basic'              => 'xskbcx.aspx',
        // 学生专业选课、已选课
        'schedule_selected'           => 'xsxk.aspx',
        // 全校课表
        'schedule_all_school'         => 'jxrwcx.aspx',
        // 全校性选修课（通识课）
        'schedule_select_school_wide' => 'xf_xsqxxxk.aspx',
        // 体育选课
        'schedule_select_physical'    => 'xf_xstyxk.aspx',
        // 密码修改
        'password_modify'             => 'mmxg.aspx',
        // 等级考试成绩查询
        'grade_class_exam'            => 'xsdjkscx.aspx',
        // 培养计划查询
        'grade_training_plan'         => 'pyjh.aspx',
        // 学生个人成绩、历年成绩、成绩统计
        'grade_basic'                 => 'xscjcx.aspx',
        // 考试查询
        'exam_basic'                  => 'xskscx.aspx',
        // 补考查询
        'exam_make_up'                => 'xsbkkscx.aspx',
        // 新闻列表
        'content_list'                => 'content.aspx',
        // 附件下载
        'attach_download'             => 'wbwj/',
        // 四六级报名
        'cet_apply'                   => 'bmxmb.aspx',
        // 四六级报名协议
        'cet_protocol'                => 'sm_bmxmb.aspx',
        // 学生照片，用于四六级报名
        'student_photo'               => 'readimagexs.aspx',
    ],

    /**
     * 网络代理
     *
     * proxy_status: 代理状态，false/true，true 为开启代理
     * proxy: 代理地址，具体参考 http://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html#proxy
     */
    'proxy_status' => false,
    'proxy'        => 'socks5://xxx.xxx.xxx.xxx:xxxx',
];
