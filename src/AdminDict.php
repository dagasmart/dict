<?php

namespace DagaSmart\Dict;

use Illuminate\Support\Arr;
use DagaSmart\Dict\Services\BasicDictService as Service;

class AdminDict
{
    private ?array $data;

    public function key($default = '')
    {
        return Arr::get($this->data, 'key', $default);
    }

    public function value($default = '')
    {
        return Arr::get($this->data, 'value', $default);
    }

    public function all($default = [])
    {
        return $this->data ?: $default;
    }

    public function get($path, $needAllData = false): static
    {
        $originData = $needAllData ? Service::make()->getAllData() : Service::make()->getValidData();

        $this->data = Arr::get($originData, $path);

        return $this;
    }

    public function getValue($path, $default = '', $needAllData = true)
    {
        return $this->get($path, $needAllData)->value($default);
    }

    public function getKey($path, $default = '', $needAllData = true)
    {
        return $this->get($path, $needAllData)->key($default);
    }

    public function getAll($path, $default = [], $needAllData = true)
    {
        return $this->get($path, $needAllData)->all($default);
    }

    public function getOptions($path, $default = [], $needAllData = true): array
    {
        $data = [];
        $Arr = $this->get($path, $needAllData)->all($default);
        asort($Arr);
        array_walk($Arr, function ($v,$k) use(&$data){
            $data[$k]['label'] = $v['value'];
            $data[$k]['value'] = $v['key'];
        });
        return array_values($data);
    }
}
