<?php

namespace Modules\Shopping\App\Actions;

use Modules\Shopping\App\Models\Product;

final class SaveProduct
{
    public function execute(array $data, ?Product $product = null): Product
    {
        $product ??= new Product();
        $product->fill($data)->save();

        return $product;
    }
}
