<?php

use DagaSmart\Dict\DictService;

if (!function_exists('admin_dict')) {
    /**
     * 数据字典
     * @return DictService
     */
    function admin_dict(): DictService
    {
        return app('admin.dict');
    }
}
