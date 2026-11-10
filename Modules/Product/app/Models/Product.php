<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Models\Category;
use Modules\Product\Database\Factories\ProductFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ENABLED = 'enabled';

    public const STATUS_DISABLED = 'disabled';

    protected $fillable = ['name', 'category_id', 'description', 'price', 'stock', 'enabled'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Shared filtering used by the API, the dashboard and the Excel export.
     *
     * @param  array{category_id?: mixed, status?: mixed, search?: mixed}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['category_id']), fn ($q) => $q->where('category_id', $filters['category_id']))
            ->when(($filters['status'] ?? null) === self::STATUS_ENABLED, fn ($q) => $q->where('enabled', true))
            ->when(($filters['status'] ?? null) === self::STATUS_DISABLED, fn ($q) => $q->where('enabled', false))
            ->when(! empty($filters['search']), fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%'));
    }
}
