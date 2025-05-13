<?php

namespace DagaSmart\Dict\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use DagaSmart\Dict\DictServiceProvider;
use DagaSmart\BizAdmin\Services\AdminService;
use DagaSmart\Dict\Models\BasicDict;

/**
 * @method BasicDict|Builder query()
 */
class DictService extends AdminService
{
    protected string $modelName = BasicDict::class;

    const string All_DICT_CACHE_KEY   = 'admin_dict_cache_key';
    const string VALID_DICT_CACHE_KEY = 'admin_dict_valid_cache_key';

    public function getListByParentId($parentId)
    {
        return $this->query()->where('parent_id', $parentId)->get();
    }

    public function getFirstId()
    {
        return $this->query()->where('parent_id', 0)->value('id') ?? 0;
    }

    public function getDictType()
    {
        return $this->getListByParentId(0);
    }

    public function getDictTypeOptions()
    {
        return $this->getDictType()
            ->map(fn($item) => $item->only(['id', 'value', 'key', 'enabled']))
            ->map(function ($item) {
                $item['creatable'] = false;
                return $item;
            })
            ->toArray();
    }

    public function listQuery()
    {
        $isType   = request()->has('_type') ? '=' : '<>';
        $key      = request()->input('key', request()->input('type_key'));
        $value    = request()->input('value', request()->input('type_value'));
        $parentId = request()->input('parent_id');
        $enabled  = request()->input('enabled', request()->input('type_enabled'));

        $query = $this->query()
            ->with('dict_type')
            ->where('parent_id', $isType, 0)
            ->when($parentId, fn($query) => $query->where('parent_id', $parentId))
            ->when($key, fn($query) => $query->where('key', 'like', "%{$key}%"))
            ->when($value, fn($query) => $query->where('value', 'like', "%{$value}%"))
            ->when(is_numeric($enabled), fn($query) => $query->where('enabled', $enabled));

        $this->sortable($query);

        return $query;
    }

    /**
     * 排序
     *
     * @param $query
     *
     * @return void
     */
    public function sortable($query): void
    {
        if (request()->orderBy && request()->orderDir) {
            $query->orderBy(request()->orderBy, request()->orderDir ?? 'asc');
        } else {
            $query->orderBy('parent_id')->orderBy('sort');
        }
    }

    public function store($data): bool
    {
        $key        = Arr::get($data, 'key');
        $muggleMode = DictServiceProvider::setting('muggle_mode');
        // 麻瓜模式
        regenerate:
        if ($muggleMode) {
            $data['key'] = $key = uniqid(mt_rand(1000, 9999));
        }
        $parentId = Arr::get($data, 'parent_id', 0);

        $exists = $this->query()->where('parent_id', $parentId)->where('key', $key)->exists();

        if ($exists) {
            if ($muggleMode) {
                goto regenerate;
            }
            return $this->repeatError($parentId);
        }

        $this->clearCache();

        return parent::store($data);
    }

    public function update($primaryKey, $data): bool
    {
        $key      = Arr::get($data, 'key');
        $parentId = Arr::get($data, 'parent_id', 0);

        $exists =
            $this->query()->where('parent_id', $parentId)->where('key', $key)->where('id', '<>', $primaryKey)->exists();

        if ($exists) {
            return $this->repeatError($parentId);
        }

        $this->clearCache();

        return parent::update($primaryKey, $data);
    }

    public function repeatError($parentId): bool
    {
        return $this->setError(
            $this->trans(
                'repeat',
                [
                    'field' => $this->trans('field.' . ($parentId != 0 ? 'key' : 'type_key')),
                ]
            )
        );
    }

    public function delete(string $ids): mixed
    {
        $this->clearCache();

        return parent::delete($ids);
    }

    private function handleData($data): array
    {
        $result = [];

        if (!$data) {
            return $result;
        }

        foreach ($data as $item) {
            $result[$item['key']] = [];
            if (Arr::get($item, 'children')) {
                foreach ($item['children'] as $child) {
                    $result[$item['key']][$child['key']] = [
                        'key'   => $child['key'],
                        'value' => $child['value'],
                    ];
                }
            }
        }

        return $result;
    }

    public function getAllData()
    {
        return Cache::rememberForever(self::All_DICT_CACHE_KEY, function () {
            return Cache::lock(self::All_DICT_CACHE_KEY . '_lock', 10)->block(5, function () {
                $data = $this->query()
                    //->with(['children' => fn($query) => $query->withTrashed()])
                    //->withTrashed()
                    ->with('children')
                    ->where('parent_id', 0)
                    ->orderBy('sort')
                    ->get();

                return $this->handleData($data ? $data->toArray() : []);
            });
        });
    }

    public function getValidData()
    {
        return Cache::rememberForever(self::VALID_DICT_CACHE_KEY, function () {
            return Cache::lock(self::VALID_DICT_CACHE_KEY . '_lock', 10)->block(5, function () {
                $data = $this->query()
                    ->with(['children' => fn($query) => $query->where('enabled', 1)])
                    ->where('parent_id', 0)
                    ->where('enabled', 1)
                    ->orderBy('sort')
                    ->get();

                return $this->handleData($data ? $data->toArray() : []);
            });
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::All_DICT_CACHE_KEY);
        Cache::forget(self::VALID_DICT_CACHE_KEY);
    }

    private function trans($key, $replace = []): array|string|null
    {
        return DictServiceProvider::trans('basic-dict.' . $key, $replace);
    }
}
