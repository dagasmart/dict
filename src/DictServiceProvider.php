<?php
declare(strict_types=1);
namespace DagaSmart\Dict;

use Exception;
use DagaSmart\BizAdmin\Extend\ServiceProvider;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\Dict\Traits\DictTrait;


class DictServiceProvider extends ServiceProvider
{
    use DictTrait;

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

    /**
     * @return void
     * @throws Exception
     */
    public function register(): void
    {
        parent::register();

        /**加载路由**/
        parent::registerRoutes(__DIR__.'/Http/routes.php');
        /**加载语言包**/
        if ($lang = parent::getLangPath()) {
            $this->loadTranslationsFrom($lang, $this->getCode());
        }

        $this->app->singleton('admin.dict', Common::class);
    }


    public function settingForm(): Form
    {
        return $this->baseSettingForm()->body([
            amis()->SwitchControl('disabled_dict_type', '屏蔽数据字典类型管理'),
            amis()->SwitchControl('disabled_dict_create', '屏蔽数据字典创建'),
            amis()->SwitchControl('disabled_dict_delete', '屏蔽数据字典删除'),
            amis()->SwitchControl('muggle_mode', '麻瓜模式')->labelRemark('开启后, 字典值将由系统随机生成'),
        ]);
    }
}
