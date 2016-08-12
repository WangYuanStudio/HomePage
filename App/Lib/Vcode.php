<?php
namespace App\Lib;

/**
 * Created by PhpStorm.
 * User: zeffee
 * Date: 2016/8/12
 * Time: 18:59
 */

class Vcode
{
    /**图片高度
     *
     * @var int
     */
    private $img_height;


    /**图片宽度
     *
     * @var float
     */
    private $img_width;


    /**文本长度
     *
     * @var int
     */
    private $text_num;


    /**图片显示的验证码
     *
     * @var string
     */
    private $img_text;


    /**最终验证的文本
     *
     * @var string
     */
    private $verify_text;


    /**字体颜色
     *
     * @var string
     */
    private $font_color;


    /**字体大小
     *
     * @var int
     */
    private $font_size;


    /**字体
     *
     * @var string
     */
    private $font_family;


    /**背景颜色
     *
     * @var string
     */
    private $bg_color;


    /**噪点
     *
     * @var int
     */
    private $noise_point;


    /**干扰线
     *
     * @var int
     */
    private $noise_line;


    /**扭曲文字
     *
     * @var bool
     */
    private $text_distortion;

    private $img_distortion;


    /**图片边框
     *
     * @var bool
     */
    private $img_border;


    /**
     * 实例
     */
    private $image;


    public function __construct($num = 5, $size = 16, $height = NULL, $width = NULL, $distortion = false, $border = false, $noise_point_num = 30, $noise_line_num = 3, $font_family = "")
    {
        $this->text_num = $num;
        $this->font_size = $size;
        $this->img_height = $height ?: $this->getDefaultImgHeight();
        $this->img_width = $width ?: $this->getDefaultImgWidth();
        $this->noise_point = $noise_point_num;
        $this->noise_line = $noise_line_num;
        $this->text_distortion = $distortion;
        $this->img_border = $border;
        //字体自定义
        $this->font_family = $font_family ?: "c:\\windows\\fonts\SIMYOU.ttf";

        $this->run();
    }


    /**获取图片宽度默认值
     *
     * @return float
     */
    private function getDefaultImgWidth()
    {
        return floor($this->font_size * 1.3) * $this->text_num + 10;
    }


    /**获取图片高度默认值
     *
     * @return int
     */
    private function getDefaultImgHeight()
    {
        return $this->font_size * 2;
    }


    protected function run()
    {
        $this->initImg()->textMergeImg()->distortText()->createNoisePoint()->createNoiseLine()->createBorder();
    }


    /**初始化图片
     *
     * @return $this
     */
    protected function initImg()
    {
        $this->image = imagecreatetruecolor($this->img_width, $this->img_height);

        $this->bg_color = imagecolorallocate($this->image, mt_rand(100, 255), mt_rand(100, 255), mt_rand(100, 255));
        $this->font_color = imagecolorallocate($this->image, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));

        imagefill($this->image, 0, 0, $this->bg_color);

        return $this;
    }


    /**文字写进图片
     *
     * @return $this
     */
    protected function textMergeImg()
    {
        $text_arr = $this->getText();

        for ($i = 0; $i < $this->text_num; $i++) {
            imagettftext($this->image, $this->font_size, mt_rand(-1, 1) * mt_rand(1, 20), 5 + $i * floor($this->font_size * 1.3), floor($this->img_height * 0.75), $this->font_color, $this->font_family, $text_arr[ $i ]);
        }

        return $this;
    }


    /**获取随机文字
     *
     * @return array
     */
    private function getText()
    {
        $img_text = [];
        $final_verify_text = [];

        for ($i = 0; $i < $this->text_num; $i++) {
            $rand_num = mt_rand(0, 3);
            $final_verify_text[ $rand_num ][] = $img_text[] = $this->getRandText($rand_num);
        }

        $this->img_text = join("", $img_text);
        $this->verify_text = $this->getVerifyText($final_verify_text);

        return $img_text;
    }


    /**获取指定类型的随机字符
     *
     * @param int $mode
     *
     * @return string
     */
    private function getRandText($mode)
    {
        switch ($mode) {
            //no break at all
            case 0:
                return "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"[ mt_rand(0, 49) ];
            case 1:
                return "0123456789"[ mt_rand(0, 9) ];
            case 2:
                return iconv('GB2312', 'UTF-8', chr(rand(0xB0, 0xCC)) . chr(rand(0xA1, 0xBB)));
            case 3:
                return "!@#$%&*+-="[ mt_rand(0, 9) ];
        }
    }


    /**获取最终验证的文字
     *
     * @param $text_arr
     *
     * @return array
     */
    private function getVerifyText($text_arr)
    {
        $verify_text = [];

        $rand_num = array_keys($text_arr)[ mt_rand(0, count($text_arr) - 1) ];
        switch ($rand_num) {
            case 0:
                $verify_text["type"] = "英文";
                break;
            case 1:
                $verify_text["type"] = "数字";
                break;
            case 2:
                $verify_text["type"] = "中文";
                break;
            case 3:
                $verify_text["type"] = "符号";
                break;
        }
        $verify_text["text"] = join("", $text_arr[ $rand_num ]);

        return $verify_text;
    }


    /**干扰文字
     *
     * @return $this
     */
    protected function distortText()
    {
        if ($this->text_distortion) {
            $this->img_distortion = imagecreatetruecolor($this->img_width, $this->img_height);
            imagefill($this->img_distortion, 0, 0, $this->bg_color);
            for ($x = 0; $x < $this->img_width; $x++) {
                for ($y = 0; $y < $this->img_height; $y++) {
                    imagesetpixel($this->img_distortion, (int)($x + sin($y / $this->img_height * 2 * M_PI - M_PI * 0.5) * 3), $y, imagecolorat($this->image, $x, $y));
                }
            }
            $this->image = $this->img_distortion;
        }

        return $this;
    }


    /**生成干扰点
     *
     * @return $this
     */
    protected function createNoisePoint()
    {
        for ($i = 0; $i < $this->noise_point; $i++) {
            $pointColor = imagecolorallocate($this->image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($this->image, mt_rand(0, $this->img_width), mt_rand(0, $this->img_height), $pointColor);
        }

        return $this;
    }


    /**生成干扰线
     *
     * @return $this
     */
    protected function createNoiseLine()
    {
        for ($i = 0; $i < $this->noise_line; $i++) {
            $lineColor = imagecolorallocate($this->image, mt_rand(0, 255), mt_rand(0, 255), 20);
            imageline($this->image, 0, mt_rand(0, $this->img_width), $this->img_width, mt_rand(0, $this->img_height), $lineColor);
        }

        return $this;
    }


    /**
     * 生成边框
     */
    protected function createBorder()
    {
        if ($this->img_border) {
            imagerectangle($this->image, 0, 0, $this->img_width - 1, $this->img_height - 1, $this->font_color);
        }
    }


    /**获取验证文本
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->verify_text;
    }


    /**
     * 生成png
     */
    public function show()
    {
        Header("Content-type: image/PNG; charset=utf-8");
        imagepng($this->image);
    }


    /**
     * 销毁
     */
    public function __destruct()
    {
        imagedestroy($this->image);

        if ($this->text_distortion) {
            imagedestroy($this->img_distortion);
        }
    }
}