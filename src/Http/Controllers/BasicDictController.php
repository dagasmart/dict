<?php

namespace DagaSmart\Dict\Http\Controllers;

use DagaSmart\BizAdmin\Renderers\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Services\AdminService;
use DagaSmart\Dict\DictServiceProvider;
use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\Dict\Services\BasicDictService;
use DagaSmart\BizAdmin\Controllers\AdminController;

/**
 * @property AdminService|BasicDictService $service
 */
class BasicDictController extends AdminController
{
    protected string $serviceName = BasicDictService::class;

    public function index(): JsonResponse|JsonResource
    {
//        admin_abort($this->service->getAllData());

        if ($this->actionOfGetData()) {
            return $this->response()->success($this->service->list());
        }

        $css = [
            '.cxd-Tree-itemArrowPlaceholder' => ['display' => 'none'],
            '.cxd-Tree-itemLabel'            => ['padding-left' => '0 !important'],
        ];

        $page = amis()->Page()->body([
            amis()->Flex()->items([
                $this->navBar(),
                $this->list()
            ]),
        ])->css($css);

        return $this->response()->success($page);
    }


    public function navBar(): Card
    {
        $formItems = [
            amis()->TextControl('value', $this->trans('type_label'))->required()->maxLength(255),
            amis()->TextControl('key', $this->trans('type_value'))->required()->maxLength(255),
            amis()->SwitchControl('enabled', $this->trans('field.enabled'))->value(1),
        ];

        return amis()->Card()->className('w-1/4 mr-5 mb-0 min-w-xs')->body([
            amis()->Flex()->className('mb-4')->justify('space-between')->items([
                amis()->Wrapper()
                    ->size('none')
                    ->body($this->trans('dict_type.label'))
                    ->className('flex items-center text-md'),
            ]),
            amis()->Form()
                ->wrapWithPanel(false)
                ->data([
                    'items' => $this->service->getDictType(),
                ])
                ->body(
                    amis()->TreeControl('dict_type')
                        ->id('dict_type_list')
                        //->source('/basic/dict/dict_type_options')
                        ->source('${items}')
                        ->set('valueField', 'id')
                        ->set('labelField', 'value')
                        ->showIcon(false)
                        ->searchable()
                        ->set('rootCreateTip', admin_trans('admin.create') . $this->trans('dict_type.label'))
                        ->selectFirst()
                        ->creatable($this->dictTypeEnabled())
                        ->addControls($formItems)
                        ->editable($this->dictTypeEnabled())
                        ->editControls(array_merge($formItems, [amis()->HiddenControl()->name('id')]))
                        ->removable($this->dictTypeEnabled())
                        ->addApi($this->getStorePath())
                        ->editApi($this->getUpdatePath())
                        ->deleteApi($this->getDeletePath())
                        ->menuTpl('<span class="w-2/5">${value}</div>')
                        ->onEvent([
                            'change' => [
                                'actions' => [
                                    [
                                        'actionType' => 'url',
                                        'args'       => ['url' => '/basic/dict?parent_id=${dict_type}'],
                                    ],
                                ],
                            ],
                        ])
                ),
        ]);
    }


    /**
     * @return Page
     */
    public function list(): Page
    {
        $createButton = $this->createButton(true);

        if (DictServiceProvider::setting('disabled_dict_create')) {
            $createButton = '';
        }

        $rowAction = amis()->Operation()->label(__('admin.actions'))->buttons([
            $this->rowEditButton(true),
            $this->rowDeleteButton(),
        ]);

        if (DictServiceProvider::setting('disabled_dict_delete')) {
            $rowAction = amis()->Operation()->label(__('admin.actions'))->buttons([
                $this->rowEditButton(true),
            ]);
        }

        $dictTypeButton = $this->dictForm();

        if (DictServiceProvider::setting('disabled_dict_type')) {
            $dictTypeButton = '';
        }

        $crud = $this->baseCRUD()
            ->headerToolbar([
                $createButton,
                'bulkActions',
                $dictTypeButton,
                amis('reload')->set('align','right'),
                amis('filter-toggler')->set('align','right'),
            ])
            ->filter(
                $this->baseFilter()
                    ->data([
                        'items' => $this->service->getDictType(),
                    ])
                    ->body([
                    amis()->SelectControl()
                        ->name('parent_id')
                        ->label($this->trans('type'))
                        //->source(admin_url('/basic/dict/dict_type_options'))
                        ->source('${items}')
                        ->set('valueField', 'id')
                        ->set('labelField', 'value')
                        ->clearable()
                        ->size('md'),
                    amis()->TextControl()->name('key')->label($this->trans('field.key'))->size('md'),
                    amis()->TextControl()->name('value')->label($this->trans('field.value'))->size('md'),
                    amis()->SelectControl()
                        ->name('enabled')
                        ->label($this->trans('field.enabled'))
                        ->size('md')
                        ->clearable()
                        ->options([
                            ['label' => $this->trans('yes'), 'value' => 1],
                            ['label' => $this->trans('no'), 'value' => 0],
                        ]),
                ])
            )
            ->autoFillHeight(true)
            ->columns([
                amis()->TableColumn('id', 'ID'),
                amis()->TableColumn('dict_type.value', $this->trans('type'))
                    ->type('tag')
                    ->set('color', 'active'),
                amis()->TableColumn('dict_type.key', $this->trans('dict_type.key')),
                amis()->TableColumn('key', $this->trans('field.key')),
                amis()->TableColumn('value', $this->trans('field.value')),
                amis()->TableColumn('enabled', $this->trans('field.enabled'))->type('switch'),
                amis()->TableColumn('sort', $this->trans('field.sort'))->quickEdit(true),
                $rowAction,
            ])->combineFromIndex(1)->combineNum(2);

        return $this->basePage()->body([
            amis()->Alert()
                ->showIcon()
                ->style([
                    'padding' => '1rem',
                    'color' => 'var(--colors-brand-6)',
                    'borderStyle' => 'dashed',
                    'borderColor' => 'var(--colors-brand-6)',
                    'backgroundColor' => 'var(--Tree-item-onChekced-bg)',
                ])
                ->body("调用方法：admin_dict()->getOptions('data.filesystem.driver')"),
            $this->baseList($crud)
        ]);
    }


    public function form(): Form
    {
        return $this->baseForm()->id('dict_item_form')->data([
            'enabled' => true,
            'sort'    => 0,
            'items'   => $this->service->getDictType()
        ])->body([
            amis()->SelectControl()
                ->name('parent_id')
                ->label($this->trans('type'))
                //->source(admin_url('/basic/dict/dict_type_options'))
                ->source('${items}')
                ->clearable()
                ->required()
                ->valueField('id')
                ->labelField('value'),
            amis()->TextControl()->name('value')->label($this->trans('field.value'))->required()->maxLength(255),
            amis()->TextControl()->name('key')->label($this->trans('field.key'))->required()->maxLength(255)->addOn(
                amis()->VanillaAction()->label($this->trans('random'))->icon('fa-solid fa-shuffle')->onEvent([
                    'click' => [
                        'actions' => [
                            [
                                'actionType'  => 'setValue',
                                'componentId' => 'dict_item_form',
                                'args'        => [
                                    'value' => [
                                        'key' => '${PADSTART(INT(NOW()), 9, "0") | base64Encode | lowerCase}',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
            ),
            amis()->NumberControl()
                ->name('sort')
                ->label($this->trans('field.sort'))
                ->displayMode('enhance')
                ->min(0)
                ->max(9999)
                ->description($this->trans('sort_description')),
            amis()->SwitchControl()->name('enabled')->label($this->trans('field.enabled')),
        ]);
    }

    public function dictTypeOptions(): JsonResponse|JsonResource
    {
        return $this->response()->success($this->service->getDictTypeOptions());
    }

    public function dictOptions(): JsonResponse|JsonResource
    {
        $path = request('path');
        $list = [];
        if ($path) {
            $list = admin_dict()->getOptions($path);
        }

        return $this->response()->success($list);
    }

    public function detail($id): Form
    {
        return $this->baseDetail($id);
    }

    public function dictForm(): DialogAction
    {
        $form = $this->baseForm()->api($this->getStorePath())->data([
            'enabled' => true,
            'sort'    => 0,
        ])->body([
            amis()->TextControl()->name('value')->label($this->trans('field.value'))->required()->maxLength(255),
            amis()->TextControl()->name('key')->label($this->trans('field.key'))->required()->maxLength(255),
            amis()->SwitchControl()->name('enabled')->label($this->trans('field.enabled')),
        ]);

        $createButton = amis()->DialogAction()
            ->dialog(amis()->Dialog()->title(__('admin.create'))->body($form))
            ->label(__('admin.create'))
            ->icon('fa fa-add')
            ->level('primary');

        $editForm = (clone $form)->api($this->getUpdatePath('$id'))->initApi($this->getEditGetDataPath('$id'));

        $editButton = amis()->DialogAction()
            ->dialog(amis()->Dialog()->title(__('admin.edit'))->body($editForm))
            ->label(__('admin.edit'))
            ->icon('fa-regular fa-pen-to-square')
            ->level('link');

        return amis()->DialogAction()->label($this->trans('dict_type.label'))->dialog(
            amis()->Dialog()->title($this->trans('dict_type.label'))->size('lg')->actions([])->body(
                amis()->CRUDTable()
                    ->perPage(10)
                    ->affixHeader(false)
                    ->filterTogglable()
                    ->filterDefaultVisible(false)
                    ->bulkActions([$this->bulkDeleteButton()])
                    ->perPageAvailable([10, 20, 30, 50, 100, 200])
                    ->footerToolbar(['switch-per-page', 'statistics', 'pagination'])
                    ->api($this->getListGetDataPath() . '&_type=1')
                    ->headerToolbar([
                        $createButton,
                        'bulkActions',
                        amis('reload')->set('align','right'),
                        amis('filter-toggler')->set('align','right'),
                    ])
                    ->filter(
                        $this->baseFilter()->data(['_type' => 1])->body([
                            amis()->TextControl()->name('type_key')->label($this->trans('field.type_key'))->size('md'),
                            amis()->TextControl()->name('type_value')->label($this->trans('field.value'))->size('md'),
                            amis()->SelectControl()
                                ->name('type_enabled')
                                ->label($this->trans('field.enabled'))
                                ->size('md')
                                ->clearable()
                                ->options([
                                    '1' => $this->trans('yes'),
                                    '0' => $this->trans('no'),
                                ]),
                        ])
                    )
                    ->columns([
                        amis()->TableColumn()->name('value')->label($this->trans('type_label')),
                        amis()->TableColumn()->name('key')->label($this->trans('type_value')),
                        amis()->TableColumn()
                            ->name('enabled')
                            ->label($this->trans('field.enabled'))
                            ->type('status'),
                        amis()->Operation()->label(__('admin.actions'))->buttons([
                            $editButton,
                            $this->rowDeleteButton(),
                        ])->set('width', 150),
                    ])
            )
        );
    }

    private function trans($key): array|string|null
    {
        return DictServiceProvider::trans('basic-dict.' . $key);
    }

    private function dictTypeEnabled(): bool
    {
        return !DictServiceProvider::setting('disabled_dict_type');
    }

    private function muggleMode()
    {
        return DictServiceProvider::setting('muggle_mode');
    }


}
