<?php

namespace DagaSmart\Dict\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use DagaSmart\BizAdmin\Renderers\Alert;
use DagaSmart\BizAdmin\Renderers\Dialog;
use DagaSmart\BizAdmin\Renderers\CRUDTable;
use DagaSmart\BizAdmin\Renderers\Form;
use DagaSmart\BizAdmin\Renderers\Operation;
use DagaSmart\BizAdmin\Renderers\Page;
use DagaSmart\BizAdmin\Renderers\TableColumn;
use DagaSmart\BizAdmin\Renderers\TextControl;
use DagaSmart\BizAdmin\Services\AdminService;
use DagaSmart\Dict\DictServiceProvider;
use DagaSmart\BizAdmin\Renderers\DialogAction;
use DagaSmart\BizAdmin\Renderers\SwitchControl;
use DagaSmart\BizAdmin\Renderers\NumberControl;
use DagaSmart\BizAdmin\Renderers\SelectControl;
use DagaSmart\BizAdmin\Renderers\VanillaAction;
use DagaSmart\Dict\Services\BasicDictService;
use DagaSmart\BizAdmin\Controllers\AdminController;

/**
 * @property AdminService|BasicDictService $service
 */
class DictController extends AdminController
{
    protected string $serviceName = BasicDictService::class;

    /**
     * @return Page
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function list(): Page
    {
        $createButton = $this->createButton(true);

        if (DictServiceProvider::setting('disabled_dict_create')) {
            $createButton = '';
        }

        $rowAction = Operation::make()->label(__('admin.actions'))->buttons([
            $this->rowEditButton(true),
            $this->rowDeleteButton(),
        ])->set('width', 240);

        if (DictServiceProvider::setting('disabled_dict_delete')) {
            $rowAction = Operation::make()->label(__('admin.actions'))->buttons([
                $this->rowEditButton(true),
            ])->set('width', 120);
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
                amis('reload')->align('right'),
                amis('filter-toggler')->align('right'),
            ])
            ->filter(
                $this->baseFilter()->body([
                    SelectControl::make()
                        ->name('parent_id')
                        ->label($this->trans('type'))
                        ->source(admin_url('/basic/dict/dict_type_options'))
                        ->valueField('id')
                        ->size('md')
                        ->clearable(true)
                        ->labelField('value'),
                    TextControl::make()->name('key')->label($this->trans('field.key'))->size('md'),
                    TextControl::make()->name('value')->label($this->trans('field.value'))->size('md'),
                    SelectControl::make()
                        ->name('enabled')
                        ->label($this->trans('field.enabled'))
                        ->size('md')
                        ->clearable(true)
                        ->options([
                            ['label' => $this->trans('yes'), 'value' => 1],
                            ['label' => $this->trans('no'), 'value' => 0],
                        ]),
                ])
            )
            ->autoFillHeight(true)
            ->columns([
                TableColumn::make()
                    ->name('dict_type.value')
                    ->label($this->trans('type'))
                    ->type('tag')
                    ->set('color', 'active'),
                TableColumn::make()->name('dict_type.key')->label($this->trans('dict_type.key')),
                TableColumn::make()->name('key')->label($this->trans('field.key')),
                TableColumn::make()->name('value')->label($this->trans('field.value')),
                TableColumn::make()->name('enabled')->label($this->trans('field.enabled'))->type('switch')->width(120),
                TableColumn::make()->name('sort')->label($this->trans('field.sort'))->quickEdit(true)->width(120),
                TableColumn::make()->name('created_at')->label(__('admin.created_at'))->width(120),
                TableColumn::make()->name('updated_at')->label(__('admin.updated_at'))->width(120),
                $rowAction,
            ])->combineFromIndex(0)->combineNum(2);

        return $this->basePage()->body([
            Alert::make()->showIcon(true)->body("调用方法：admin_dict()->getOptions('daga.filesystem.driver')"),
            $this->baseList($crud)
        ]);
    }


    public function form(): Form
    {
        return $this->baseForm()->id('dict_item_form')->data([
            'enabled' => true,
            'sort'    => 0,
        ])->body([
            SelectControl::make()
                ->name('parent_id')
                ->label($this->trans('type'))
                ->source(admin_url('/basic/dict/dict_type_options'))
                ->clearable(true)
                ->required(true)
                ->valueField('id')
                ->labelField('value'),
            TextControl::make()->name('value')->label($this->trans('field.value'))->required(true)->maxLength(255),
            TextControl::make()->name('key')->label($this->trans('field.key'))->required(true)->maxLength(255)->addOn(
                VanillaAction::make()->label($this->trans('random'))->icon('fa-solid fa-shuffle')->onEvent([
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
            NumberControl::make()
                ->name('sort')
                ->label($this->trans('field.sort'))
                ->displayMode('enhance')
                ->min(0)
                ->max(9999)
                ->description($this->trans('sort_description')),
            SwitchControl::make()->name('enabled')->label($this->trans('field.enabled')),
        ]);
    }

    public function dictTypeOptions(): JsonResponse|JsonResource
    {
        return $this->response()->success($this->service->getDictTypeOptions());
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
            TextControl::make()->name('value')->label($this->trans('field.value'))->required(true)->maxLength(255),
            TextControl::make()->name('key')->label($this->trans('field.key'))->required(true)->maxLength(255),
            SwitchControl::make()->name('enabled')->label($this->trans('field.enabled')),
        ]);

        $createButton = DialogAction::make()
            ->dialog(Dialog::make()->title(__('admin.create'))->body($form))
            ->label(__('admin.create'))
            ->icon('fa fa-add')
            ->level('primary');

        $editForm = (clone $form)->api($this->getUpdatePath('$id'))->initApi($this->getEditGetDataPath('$id'));

        $editButton = DialogAction::make()
            ->dialog(Dialog::make()->title(__('admin.edit'))->body($editForm))
            ->label(__('admin.edit'))
            ->icon('fa-regular fa-pen-to-square')
            ->level('link');

        return DialogAction::make()->label($this->trans('dict_type.label'))->dialog(
            Dialog::make()->title($this->trans('dict_type.label'))->size('lg')->actions([])->body(
                CRUDTable::make()
                    ->perPage(20)
                    ->affixHeader(false)
                    ->filterTogglable(true)
                    ->filterDefaultVisible(false)
                    ->bulkActions([$this->bulkDeleteButton()])
                    ->perPageAvailable([10, 20, 30, 50, 100, 200])
                    ->footerToolbar(['switch-per-page', 'statistics', 'pagination'])
                    ->api($this->getListGetDataPath() . '&_type=1')
                    ->headerToolbar([
                        $createButton,
                        'bulkActions',
                        amis('reload')->align('right'),
                        amis('filter-toggler')->align('right'),
                    ])
                    ->filter(
                        $this->baseFilter()->data(['_type' => 1])->body([
                            TextControl::make()->name('type_key')->label($this->trans('field.type_key'))->size('md'),
                            TextControl::make()->name('type_value')->label($this->trans('field.value'))->size('md'),
                            SelectControl::make()
                                ->name('type_enabled')
                                ->label($this->trans('field.enabled'))
                                ->size('md')
                                ->clearable(true)
                                ->options([
                                    '1' => $this->trans('yes'),
                                    '0' => $this->trans('no'),
                                ]),
                        ])
                    )
                    ->columns([
                        TableColumn::make()->name('value')->label($this->trans('field.value')),
                        TableColumn::make()->name('key')->label($this->trans('field.type_key')),
                        TableColumn::make()
                            ->name('enabled')
                            ->label($this->trans('field.enabled'))
                            ->type('status')
                            ->width(120),
                        TableColumn::make()->name('created_at')->label(__('admin.created_at'))->width(120),
                        TableColumn::make()->name('updated_at')->label(__('admin.updated_at'))->width(120),
                        Operation::make()->label(__('admin.actions'))->buttons([
                            $editButton,
                            $this->rowDeleteButton(),
                        ])->set('width', 240),
                    ])
            )
        );
    }

    private function trans($key): array|string|null
    {
        return DictServiceProvider::trans('basic-dict.' . $key);
    }
}
