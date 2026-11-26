<?php

namespace Tests;

use App\Models\ItemOption;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Str;

trait CatalogFixtures
{
    protected function category(array $attrs = []): ServiceCategory
    {
        $slug = $attrs['slug'] ?? 'cat-'.Str::lower(Str::random(6));

        // Some categories already exist from the migrations.
        return ServiceCategory::updateOrCreate(['slug' => $slug], $attrs + ['name' => Str::headline($slug), 'is_published' => true]);
    }

    protected function service(?ServiceCategory $category = null, array $attrs = []): Service
    {
        $slug = $attrs['slug'] ?? 'svc-'.Str::lower(Str::random(6));

        return Service::create($attrs + [
            'category_id' => ($category ?? $this->category())->id,
            'slug' => $slug,
            'title' => Str::headline($slug),
            'desc' => 'Description',
            'image' => 'x.jpg',
            'features' => ['One', 'Two'],
        ]);
    }

    protected function option(Service $service, array $attrs = []): ItemOption
    {
        return ItemOption::create($attrs + [
            'item_type' => 'service',
            'item_id' => $service->id,
            'name' => 'Plan A',
            'price_amount' => 1000,
            'currency' => 'ETB',
            'billing_term' => 'one-time',
            'show_price' => true,
            'is_ready' => true,
            'is_active' => true,
            'is_payable_online' => true,
        ]);
    }

    protected function staff(string $role): User
    {
        $user = User::create([
            'name' => $role, 'email' => $role.Str::random(4).'@example.test',
            'password' => bcrypt('password'), 'is_admin' => true, 'is_active' => true,
        ]);
        $user->roles()->sync([Role::where('name', $role)->value('id')]);

        return $user;
    }

    /** Turn Chapa fully on with test-only fake keys. */
    protected function enableChapa(): void
    {
        config([
            'services.chapa.enabled' => true,
            'services.chapa.secret_key' => 'CHASECK_TEST-fake',
            'services.chapa.webhook_secret' => 'fake-webhook-secret',
        ]);
        SiteSetting::set('chapa_enabled', '1');
    }
}
