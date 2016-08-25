<?php
/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/9
 * Time: 1:52
 */

namespace App\Lib;

use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;

class Mail
{
    /**邮件实例
     *
     * @var Message
     */
    private $mail;


    /**邮箱配置
     *
     * @var array
     */
    private $config = [
        'name'     => '网园资讯工作室',
        'host'     => 'smtp.exmail.qq.com',
        'port'     => 465,
        'username' => 'Admin@WangYuan.Info',
        'password' => 'ZgB8IqmKh6ZHM2LD',
        'secure'   => 'ssl'
    ];


    public function __construct()
    {
        $this->mail = new Message();
        $this->mail->setFrom($this->config['username'], $this->config['name']);
    }


    public static function to($to)
    {
        return (new Mail())->addTo($to);
    }


    /**设置收件人
     *
     * @param $email
     *
     * @return $this
     */
    public function addTo($email)
    {
        if (is_array($email)) {
            foreach ($email as $e_email) {
                $this->mail->addTo($e_email);
            }
        } else {
            $this->mail->addTo($email);
        }

        return $this;
    }


    /**设置标题
     *
     * @param $title
     *
     * @return $this
     */
    public function title($title)
    {
        $this->mail->setSubject($title);

        return $this;
    }


    /**设置内容
     *
     * @param $content
     */
    public function content($content)
    {
        $this->mail->setHtmlBody($content);
    }


    /**
     * 发送邮件
     */
    public function __destruct()
    {
        (new SmtpMailer($this->config))->send($this->mail);
    }
}