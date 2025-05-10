<?php

namespace DagaSmart\Dict;

use DagaSmart\BizAdmin\Extend\ServiceProvider;

class DictServiceProvider extends ServiceProvider
{
    protected $menu = [

//        [
//            'parent' => NULL,
//            'title' => '基本设置',
//            'url' => '/basic',
//            'url_type' => 1,
//            'icon' => 'basil:settings-solid',
//        ],
        [
            'parent'    => '基本设置',
            'title'     => '数据字典',
            'url'       => '/basic/dict',
            'url_type'  => 1,
            'icon'      => 'streamline:dictionary-language-book',
        ],
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton('admin.dict', Common::class);
    }

    public function settingForm()
    {
        return $this->baseSettingForm()->body([
            amis()->SwitchControl('disabled_dict_type', '屏蔽数据字典类型管理'),
            amis()->SwitchControl('disabled_dict_create', '屏蔽数据字典创建'),
            amis()->SwitchControl('disabled_dict_delete', '屏蔽数据字典删除'),
            amis()->SwitchControl('muggle_mode', '麻瓜模式')->labelRemark('开启后, 字典值将由系统随机生成'),
        ]);
    }
}
