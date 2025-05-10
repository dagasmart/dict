<?php

use DagaSmart\Dict\Common;

if (!function_exists('admin_dict')) {
    /**
     * 数据字典
     *
     * @return Common
     */
    function admin_dict(): Common
    {
        return app('admin.dict');
    }
}
