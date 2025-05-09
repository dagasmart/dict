<?php

use DagaSmart\Dict\AdminDict;

if (!function_exists('admin_dict')) {
    /**
     * 数据字典
     * @return AdminDict
     */
    function admin_dict(): AdminDict
    {
        return app('admin.dict');
    }
}
