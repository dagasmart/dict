<?php

namespace DagaSmart\Dict\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use DagaSmart\Dict\DictServiceProvider;
use DagaSmart\Dict\Models\BasicDict as Model;
use DagaSmart\BizAdmin\Services\AdminService;

/**
 * @method Model|Builder query()
 */
class BasicDictService extends AdminService
{
    protected string $modelName = Model::class;

    const string All_DICT_CACHE_KEY   = 'admin_dict_cache_key';
    const string VALID_DICT_CACHE_KEY = 'admin_dict_valid_cache_key';

    public function getListByParentId($parentId)
    {
        return $this->query()->where('parent_id', $parentId)->get()->toArray();
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

    public function list(): array
    {
        $isType      = request()->has('_type') ? '=' : '<>';
        $key         = request()->input('key');
        $value       = request()->input('value');
        $parentId    = request()->input('parent_id');
        $enabled     = request()->input('enabled');
        $typeKey     = request()->input('type_key');
        $typeValue   = request()->input('type_value');
        $typeEnabled = request()->input('type_enabled');

        $query = $this->query()
            ->with('dict_type')
            ->where('parent_id', $isType, 0)
            ->when($parentId, fn($query) => $query->where('parent_id', $parentId))
            ->when($key, fn($query) => $query->where('key', 'like', "%{$key}%"))
            ->when($value, fn($query) => $query->where('value', 'like', "%{$value}%"))
            ->when(is_numeric($enabled), fn($query) => $query->where('enabled', $enabled))
            ->when($typeKey, fn($query) => $query->where('key', 'like', "%{$typeKey}%"))
            ->when($typeValue, fn($query) => $query->where('value', 'like', "%{$typeValue}%"))
            ->when(is_numeric($typeEnabled), fn($query) => $query->where('enabled', $typeEnabled));

        $items = (clone $query)->paginate(request()->input('perPage', 20))->items();
        $total = (clone $query)->count();

        return compact('items', 'total');
    }

    public function store($data): bool
    {
        $key      = Arr::get($data, 'key');
        $parentId = Arr::get($data, 'parent_id', 0);

        $exists = $this->query()->where('parent_id', $parentId)->where('key', $key)->exists();

        if ($exists) {
            return $this->setError(
                $this->trans(
                    'repeat',
                    [
                        'field' => $this->trans('field.' . ($parentId != 0 ? 'key' : 'type_key')),
                    ]
                )
            );
        }

        $this->clearCache();

        $data['id'] = bcadd($this->query()->max('id') ?? 0, 1); //id最大值 +1

        return parent::store($data);
    }

    public function update($primaryKey, $data): bool
    {
        $key      = Arr::get($data, 'key');
        $parentId = Arr::get($data, 'parent_id', 0);

        $exists =
            $this->query()->where('parent_id', $parentId)->where('key', $key)->where('id', '<>', $primaryKey)->exists();

        if ($exists) {
            return $this->setError(
                $this->trans(
                    'repeat',
                    [
                        'field' => $this->trans('field.' . ($parentId != 0 ? 'key' : 'type_key')),
                    ]
                )
            );
        }

        $this->clearCache();

        return parent::update($primaryKey, $data);
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
        return $this->query()
            ->where('parent_id', 0)
            ->orderByDesc('sort')
            ->get()
            ->toArray();
//        return Cache::rememberForever(self::All_DICT_CACHE_KEY, function () {
//            $this->clearCache();//清除指定缓存
//            return Cache::lock(self::All_DICT_CACHE_KEY . '_lock', 10)->block(5, function () {
//                $data = $this->query()
//                    ->with('children')
//                    ->where('parent_id', 0)
//                    ->orderByDesc('sort')
//                    ->get();
//
//                return $data->toArray();
//            });
//        });
    }

    public function getValidData()
    {
        return Cache::rememberForever(self::VALID_DICT_CACHE_KEY, function () {
            return Cache::lock(self::VALID_DICT_CACHE_KEY . '_lock', 10)->block(5, function () {
                $data = $this->query()
                    ->with('children')
                    ->where('parent_id', 0)
                    ->where('enabled', 1)
                    ->orderByDesc('sort')
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
