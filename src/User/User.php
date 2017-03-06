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

use ZCrawler\Core\AbstractAPI;
use ZCrawler\Core\Exceptions\HttpException;
use ZCrawler\Core\Config;

class User extends AbstractAPI
{
    /**
     * Modify student password
     *
     * @param string $newPassword
     *
     * @return bool
     * @throws HttpException
     */
    public function modifyPassword($newPassword)
    {
        $url = Config::get('url.password_modify');

        $param['__VIEWSTATE'] = true;
        $param['TextBox2'] = $this->login->getPassword();
        $param['TextBox3'] = $newPassword;
        $param['Textbox4'] = $newPassword;
        $param['Button1'] = '修  改';

        $response = $this->request('post', [$url, $param]);

        $body = $this->checkAndThrow($response);

        // Convert the data charset from gb2312 to utf-8
        $content = mb_convert_encoding($body, "utf-8", "gb2312");

        // Check whether success
        if (preg_match('/<script.*>alert\(\'(.*)\'\).*<\/script>/', $content, $matches)) {
            if ($matches[1] == "修改成功！") {
                return true;
            }
            throw new HttpException($matches[1], 10006);
        }

        throw new HttpException('修改密码失败', 10007);
    }
}
