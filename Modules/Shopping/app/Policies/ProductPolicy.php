<?php

namespace Modules\Shopping\App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;
use Modules\Shopping\App\Models\Product;

class ProductPolicy
{
    use ChecksModulePermissions;

    protected string $module = 'products';

    public function viewAny(User $actor): bool { return $this->can($actor, 'read'); }
    public function view(User $actor, Product $product): bool { return $this->can($actor, 'read'); }
    public function create(User $actor): bool { return $this->can($actor, 'create'); }
    public function update(User $actor, Product $product): bool { return $this->can($actor, 'update'); }
    public function delete(User $actor, Product $product): bool { return $this->can($actor, 'delete'); }

    public function toggle(User $actor, Product $product): bool
    {
        return $this->can($actor, 'toggle');
    }

    public function restore(User $actor, Product $product): bool
    {
        return $this->can($actor, 'reverse');
    }
}
