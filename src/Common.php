<?php

namespace DagaSmart\Dict;

use Illuminate\Support\Arr;
use DagaSmart\Dict\Services\DictService as Service;

class Common
{
    private ?array $data = [];

    private function key($value = null): mixed
    {
        if(!$this->data) return null;
        $array = array_column(array_values($this->data), 'value', 'key');
        if ($value) {
            return array_search($value, $array) ?? null;
        } else {
            return array_keys($array) ?? null;
        }
    }

    private function value($key = null): mixed
    {
        if(!$this->data) return null;
        $array = array_column(array_values($this->data), 'value', 'key');
        if ($key) {
            return $array[$key] ?? null;
        } else {
            return array_values($array) ?? null;
        }
    }

    private function options(): array
    {
        return collect($this->data)->values()->map(fn($item) => [
            'label' => $item['value'],
            'value' => $item['key'],
        ])->toArray();
    }

    private function mapValues(): object
    {
        return (object) collect($this->data)->values()->pluck('value', 'key')->toArray();
    }

    private function all($default = [])
    {
        return $this->data ?? $default;
    }

    public function get($path, $needAllData = false): static
    {
        $originData = $needAllData ? Service::make()->getAllData() : Service::make()->getValidData();
        $this->data = Arr::get($originData, $path);
        return $this;
    }

    public function getValue($path, $key = '', $needAllData = true): array|string|null
    {
        return $this->get($path, $needAllData)->value($key);
    }

    public function getKey($path, $value = '', $needAllData = true)
    {
        return $this->get($path, $needAllData)->key($value);
    }

    /**
     * 获取字典类型标识的数全部数据
     * 二维数组
     *  key
     * @param $path
     * @param array $default
     * @param bool $needAllData
     * @return array|object ['local' => ['key' => 'local', 'value' => '本地存储'], ...]
     */
    public function getAll($path, array $default = [], bool $needAllData = true): object|array
    {
        return $this->get($path, $needAllData)->all($default);
    }

    /**
     * 获取字典类型标识的结构数据
     * 一维数组
     * @param $path
     * @param bool $needAllData
     * @return array|object ['local' => '本地存储', ...]
     */
    public function getMapValues($path, bool $needAllData = true): object|array
    {
        return $this->get($path, $needAllData)->mapValues();
    }

    /**
     * 获取字典类型标识的结构映射数据
     * 二维数组
     *  label
     * @param $path
     * @param bool $needAllData
     * @return array [['label' => '本地存储', 'value' => 'local'], ...]
     */
    public function getOptions($path, bool $needAllData = true): array
    {
        return $this->get($path, $needAllData)->options();
    }
}
