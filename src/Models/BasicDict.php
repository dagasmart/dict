<?php

namespace DagaSmart\Dict\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use DagaSmart\BizAdmin\Models\BaseModel;

class BasicDict extends BaseModel
{
    use SoftDeletes;

    protected $table = 'basic_dict';

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderByDesc('sort');
    }

    public function dict_type(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }
}
