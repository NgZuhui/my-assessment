<?php

namespace Modules\Product\Http\Requests;

/**
 * Same rules as creation, but every field is optional so PATCH works for partial updates.
 */
class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => in_array('sometimes', $rules, true) ? $rules : ['sometimes', ...$rules])
            ->all();
    }
}
