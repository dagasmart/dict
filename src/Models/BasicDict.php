<?php

namespace DagaSmart\Dict\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use DagaSmart\BizAdmin\Models\BaseModel as Model;

class BasicDict extends Model
{
    use SoftDeletes;

    protected $table = 'admin_dict';

    public function children(): HasMany
    {
        return $this->hasMany(BasicDict::class, 'parent_id')->orderByDesc('sort');
    }

    public function dict_type(): BelongsTo
    {
        return $this->belongsTo(BasicDict::class, 'parent_id');
    }
}
