<?php

namespace DagaSmart\Dict;

use DagaSmart\BizAdmin\Extend\ServiceProvider;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\SwitchControl;

class DictServiceProvider extends ServiceProvider
{
    protected $menu = [

//        [
//            'parent' => NULL,
//            'title' => '基础维护',
//            'url' => '/basic',
//            'url_type' => 1,
//            'icon' => 'basil:settings-solid',
//        ],
        [
            'parent'    => 'admin_basic',
            'title'     => '数据字典',
            'url'       => '/basic/dict',
            'url_type'  => 1,
            'icon'      => 'streamline:dictionary-language-book',
        ],
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton('admin.dict', AdminDict::class);
    }

    public function settingForm(): ?Form
    {
        return $this->baseSettingForm()->body([
            SwitchControl::make()->name('disabled_dict_type')->label('屏蔽数据字典类型管理'),
            SwitchControl::make()->name('disabled_dict_create')->label('屏蔽数据字典创建'),
            SwitchControl::make()->name('disabled_dict_delete')->label('屏蔽数据字典删除'),
        ]);
    }
}
