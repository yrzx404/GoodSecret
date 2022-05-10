<?php

/**
 * 加密插件
 *
 * @package GoodSecret
 * @author yrzx404
 * @version 1.0.0
 * @link http://www.javatiku.cn
 */
class GoodSecret_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('GoodSecret_Plugin', 'addSecretBtnToolbar');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('GoodSecret_Plugin', 'addSecretBtnToolbar');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('GoodSecret_Plugin', 'contentEx');
        return _t('插件已经激活，请先配置参数！');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        return _t('插件已被禁用');
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $wechat_name = new Typecho_Widget_Helper_Form_Element_Text('wechat_name', array("value"), 'Java面试那些事儿', _t('微信公众号名称'), _t('微信公众号平台→公众号设置→名称，例如：Java面试那些事儿'));
        $form->addInput($wechat_name->addRule('required', _t('请填写微信公众号名称')));
        $wechat_account = new Typecho_Widget_Helper_Form_Element_Text('wechat_account', array("value"), 'javatiku', _t('微信公众号ID'), _t(' 微信公众号平台→公众号设置→微信号，例如：javatiku'));
        $form->addInput($wechat_account->addRule('required', _t('请填写微信公众号ID')));
        $wechat_keyword = new Typecho_Widget_Helper_Form_Element_Text('wechat_keyword', array("value"), '2021', _t('回复以下关键词获取验证码'), _t('例如：微信验证码，访客回复这个关键词就可以获取到验证码'));
        $form->addInput($wechat_keyword->addRule('required', _t('请填写获取验证码的关键字')));
        $wechat_code = new Typecho_Widget_Helper_Form_Element_Text('wechat_code', array("value"), '12406', _t('自动回复的验证码'), _t('该验证码要和微信公众号平台自动回复的内容一致，最好定期两边都修改下'));
        $form->addInput($wechat_code->addRule('required', _t('请填写自动回复的验证码')));
        $wechat_qrimg = new Typecho_Widget_Helper_Form_Element_Text('wechat_qrimg', array("value"), 'https://wx.javatiku.cn/j/wx.jpg', _t('微信公众号二维码地址'), _t('填写您的微信公众号的二维码图片地址，建议150X150像素'));
        $form->addInput($wechat_qrimg->addRule('required', _t('请填写微信公众号二维码地址')));
        $wechat_day = new Typecho_Widget_Helper_Form_Element_Text('wechat_day', array("value"), '1', _t('Cookie有效期天数'), _t('在有效期内，访客无需再获取验证码可直接访问隐藏内容'));
        $form->addInput($wechat_day);
        $wechat_key = new Typecho_Widget_Helper_Form_Element_Text('wechat_key', array("value"), md5('javatiku.cn' . time() . rand(10000, 99999)), _t('加密密钥'), _t('用于加密Cookie，默认是自动生成，一般无需修改，如果修改，所有访客需要重新输入验证码才能查看隐藏内容'));
        $form->addInput($wechat_key);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }


    /**
     * 后台编辑器添加隐藏按钮
     */
    public static function addSecretBtnToolbar()
    {
        echo <<<TEXT
<script type="text/javascript">
$(function(){
    if($('#wmd-button-row').length>0){
        $('#wmd-button-row').append('<li class="wmd-button" id="wmd-button-secret" style="font-size:20px;float:left;color:#AAA;width:20px;" title="内容加密"><b>MP</b></li>');
    }else{
        $('#text').before('<a href="#" id="wmd-button-secret" title="内容加密"><b>MP</b></a>');
    }
    $(document).on('click', '#wmd-button-secret', function(){
        $('#text').val($('#text').val()+"\\r\\n<!--GoodSecret start-->\\r\\n\\r\\n<!--GoodSecret end-->");
    });
    if(($('.wmd-prompt-dialog').length != 0) && e.keyCode == '27') {
        $('.wmd-prompt-dialog').remove();
    }
});
</script>
TEXT;
    }


    /**
     * 自动输出内容
     * @access public
     * @return void
     */
    public static function contentEx($html, $widget, $lastResult)
    {
        $wechatfansRule = '/<!--GoodSecret start-->([\s\S]*?)<!--GoodSecret end-->/i';
        preg_match_all($wechatfansRule, $html, $hide_words);
        if (!$hide_words[0]) {
            $wechatfansRule = '/&lt;!--GoodSecret start--&gt;([\s\S]*?)&lt;!--GoodSecret end--&gt;/i';
        }
        $html = empty($lastResult) ? $html : $lastResult;
        $option = Typecho_Widget::widget('Widget_Options')->plugin('GoodSecret');
        $cookie_name = 'javatiku_good_secret';
        $html = trim($html);
        if (preg_match_all($wechatfansRule, $html, $hide_words)) {
            $cv = md5($option->wechat_key . $cookie_name . 'javatiku.cn');
            $vtips = '';
            if (isset($_POST['javatiku_verifycode'])) {
                if ($_POST['javatiku_verifycode'] == $option->wechat_code) {
                    setcookie($cookie_name, $cv, time() + (int)$option->wechat_day * 86400, "/");
                    $_COOKIE[$cookie_name] = $cv;
                } else {
                    $vtips = '<script>alert("请关注公众号，获取正确验证码！");</script>';
                }
            }
            $cookievalue = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';

            if ($cookievalue == $cv) {
                $html = str_replace($hide_words[0], '<div style="color: red;font-weight: bolder">隐藏内容如下：</div><div style="border:1px dashed gray; padding:10px; margin:10px 0; background-color:#fff; overflow:hidden; clear:both;"><div>' . $hide_words[0][0] . '</div></div>', $html);
                $html = str_replace("&lt;", "<", $html);
                $html = str_replace("&gt;", ">", $html);
            } else {
                $default = <<<TEXT
<div style="border:1px dashed gray; padding:10px; margin:10px 0; line-height:200%; color:black; background-color: #FFFFFF; overflow:hidden; clear:both;">
    <img class="wxpic" align="right" src="{wechat_qrimg}" style="width:150px;height:150px;margin-left:20px;display:inline;border:none" width="150" height="150"  alt="{wechat_name}" />
    反爬虫抓取，人机验证，请输入验证码查看内容：
    <form name="wechatFansForm" method="post" style="margin:10px 0;">
        <span style="float:left;">验证码：</span>
        <input name="javatiku_verifycode" type="text" value="" style="border:none;float:left;width:120px; height:28px; line-height:27px; padding:0 5px; border:1px solid gray;-moz-border-radius: 0px;  -webkit-border-radius: 0px;  border-radius:0px;" />
        <input style="border:none;float:left;width:80px; height:28px; line-height:28px; padding:0 5px; background-color:gray; text-align:center; border:none; cursor:pointer; color:#FFF;-moz-border-radius: 0px; font-size:14px;  -webkit-border-radius: 0px;  border-radius:0px;" name="" type="submit" value="提交查看" />
    </form>
    <div style="clear:left;"></div>
    <div style="color:#003D79;margin-top: 5px;">请关注本站公众号回复关键字:“<span style="color:red;font-weight: bolder;font-size: 18px;">{wechat_keyword}</span>”，获取验证码。</div>
    <div style="color:red">【注】微信搜索公众号:“<span style="color:blue">{wechat_name}</span>”或者“<span style="color:blue">{wechat_account}</span>”
    或微信扫描右侧二维码关注微信公众号</div>
</div>
TEXT;
                $default = str_replace(
                    array('{wechat_qrimg}', '{wechat_name}', '{wechat_keyword}', '{wechat_account}'),
                    array($option->wechat_qrimg, $option->wechat_name, $option->wechat_keyword, $option->wechat_account),
                    $default
                );

                $hide_notice = $default . $vtips;
                $html = str_replace($hide_words[0], $hide_notice, $html);
            }
        }
        return $html;
    }
}
