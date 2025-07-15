<?php
// tests/Feature/RolePermissionTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function super_admin_can_access_all_routes()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user, 'sanctum');

        // Test admin routes
        $this->getJson('/api/admin/users')->assertStatus(200);
        $this->getJson('/api/admin/roles')->assertStatus(200);
        $this->getJson('/api/admin/permissions')->assertStatus(200);

        // Test module routes
        $this->getJson('/api/items')->assertStatus(200);
        $this->postJson('/api/items', [
            'name' => 'Test Item',
            'item_code' => 'TEST001',
            'category_id' => 1,
            'uom_id' => 1,
        ])->assertStatus(201);
    }

    /** @test */
    public function inventory_manager_can_access_inventory_routes()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $this->actingAs($user, 'sanctum');

        // Should have access to inventory
        $this->getJson('/api/items')->assertStatus(200);
        $this->postJson('/api/items', [
            'name' => 'Test Item',
            'item_code' => 'TEST001',
            'category_id' => 1,
            'uom_id' => 1,
        ])->assertStatus(201);

        // Should not have access to admin routes
        $this->getJson('/api/admin/users')->assertStatus(403);
        $this->getJson('/api/admin/roles')->assertStatus(403);
    }

    /** @test */
    public function inventory_staff_has_limited_inventory_access()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        $this->actingAs($user, 'sanctum');

        // Should have read/create/update access
        $this->getJson('/api/items')->assertStatus(200);
        $this->postJson('/api/items', [
            'name' => 'Test Item',
            'item_code' => 'TEST001',
            'category_id' => 1,
            'uom_id' => 1,
        ])->assertStatus(201);

        // Should not have delete access (403 or custom deletion check)
        // This would depend on your implementation
        $this->deleteJson('/api/items/1')->assertStatus(403);
    }

    /** @test */
    public function viewer_can_only_read_data()
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->actingAs($user, 'sanctum');

        // Should have read access
        $this->getJson('/api/items')->assertStatus(200);

        // Should not have create access
        $this->postJson('/api/items', [
            'name' => 'Test Item',
            'item_code' => 'TEST001',
            'category_id' => 1,
            'uom_id' => 1,
        ])->assertStatus(403);

        // Should not have update access
        $this->putJson('/api/items/1', [
            'name' => 'Updated Item',
        ])->assertStatus(403);

        // Should not have delete access
        $this->deleteJson('/api/items/1')->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $this->getJson('/api/items')->assertStatus(401);
        $this->getJson('/api/admin/users')->assertStatus(401);
        $this->postJson('/api/items')->assertStatus(401);
    }

    /** @test */
    public function production_manager_can_access_manufacturing_routes()
    {
        $user = User::factory()->create();
        $user->assignRole('production_manager');

        $this->actingAs($user, 'sanctum');

        // Should have access to manufacturing
        $this->getJson('/api/production-orders')->assertStatus(200);
        $this->getJson('/api/work-orders')->assertStatus(200);
        $this->getJson('/api/boms')->assertStatus(200);

        // Should not have access to admin routes
        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    /** @test */
    public function user_can_have_multiple_roles()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        $user->assignRole('sales_manager');

        $this->actingAs($user, 'sanctum');

        // Should have access to both inventory and sales
        $this->getJson('/api/items')->assertStatus(200);
        // Assuming sales routes exist:
        // $this->getJson('/api/sales-orders')->assertStatus(200);

        $this->assertTrue($user->hasRole(['inventory_manager', 'sales_manager']));
        $this->assertTrue($user->hasAllRoles(['inventory_manager', 'sales_manager']));
    }

    /** @test */
    public function role_permission_assignment_works()
    {
        $role = Role::where('name', 'inventory_manager')->first();
        $permission = Permission::where('name', 'inventory.delete')->first();

        // Initially inventory manager shouldn't have delete permission
        $this->assertFalse($role->hasPermission('inventory.delete'));

        // Assign delete permission
        $role->givePermissionTo('inventory.delete');

        $this->assertTrue($role->fresh()->hasPermission('inventory.delete'));
    }

    /** @test */
    public function permission_middleware_blocks_unauthorized_access()
    {
        $user = User::factory()->create();
        $user->assignRole('viewer'); // Only read permissions

        $this->actingAs($user, 'sanctum');

        // Test with explicit permission middleware
        $response = $this->postJson('/api/items', [
            'name' => 'Test Item',
            'item_code' => 'TEST001',
            'category_id' => 1,
            'uom_id' => 1,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to create in inventory module',
            ]);
    }
}

// tests/Unit/UserRoleTest.php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function user_can_be_assigned_a_role()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'inventory_manager')->first();

        $user->assignRole('inventory_manager');

        $this->assertTrue($user->hasRole('inventory_manager'));
        $this->assertCount(1, $user->roles);
    }

    /** @test */
    public function user_can_have_multiple_roles()
    {
        $user = User::factory()->create();

        $user->assignRole('inventory_manager');
        $user->assignRole('sales_manager');

        $this->assertTrue($user->hasRole(['inventory_manager', 'sales_manager']));
        $this->assertCount(2, $user->roles);
    }

    /** @test */
    public function user_inherits_permissions_from_roles()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $this->assertTrue($user->hasPermission('inventory.create'));
        $this->assertTrue($user->hasPermission('inventory.read'));
        $this->assertTrue($user->hasPermission('inventory.update'));
        $this->assertTrue($user->hasPermission('inventory.delete'));
    }

    /** @test */
    public function user_can_check_module_access()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $this->assertTrue($user->canAccess('inventory', 'create'));
        $this->assertTrue($user->canAccess('inventory', 'read'));
        $this->assertTrue($user->canAccess('inventory', 'update'));
        $this->assertTrue($user->canAccess('inventory', 'delete'));

        $this->assertFalse($user->canAccess('manufacturing', 'create'));
    }

    /** @test */
    public function super_admin_has_all_permissions()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->isAdmin());

        // Should have access to all modules
        $this->assertTrue($user->canAccess('inventory', 'create'));
        $this->assertTrue($user->canAccess('manufacturing', 'create'));
        $this->assertTrue($user->canAccess('sales', 'create'));
        $this->assertTrue($user->canAccess('admin', 'user_management'));
    }

    /** @test */
    public function viewer_role_has_only_read_permissions()
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->assertTrue($user->canAccess('inventory', 'read'));
        $this->assertTrue($user->canAccess('manufacturing', 'read'));

        $this->assertFalse($user->canAccess('inventory', 'create'));
        $this->assertFalse($user->canAccess('manufacturing', 'create'));
        $this->assertFalse($user->canAccess('admin', 'user_management'));
    }

    /** @test */
    public function role_can_be_removed_from_user()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        $this->assertTrue($user->hasRole('inventory_manager'));

        $user->removeRole('inventory_manager');

        $this->assertFalse($user->fresh()->hasRole('inventory_manager'));
    }

    /** @test */
    public function user_roles_can_be_synced()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        $user->assignRole('sales_manager');

        $this->assertCount(2, $user->roles);

        $user->syncRoles(['production_manager']);

        $user = $user->fresh();
        $this->assertCount(1, $user->roles);
        $this->assertTrue($user->hasRole('production_manager'));
        $this->assertFalse($user->hasRole('inventory_manager'));
        $this->assertFalse($user->hasRole('sales_manager'));
    }
}

// tests/Unit/AuthServiceTest.php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->authService = new AuthService();
    }

    /** @test */
    public function auth_service_returns_correct_user()
    {
        $user = User::factory()->create();
        Auth::login($user);

        $this->assertEquals($user->id, $this->authService->user()->id);
        $this->assertTrue($this->authService->check());
    }

    /** @test */
    public function auth_service_checks_roles_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        Auth::login($user);

        $this->assertTrue($this->authService->hasRole('inventory_manager'));
        $this->assertFalse($this->authService->hasRole('admin'));
    }

    /** @test */
    public function auth_service_checks_permissions_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        Auth::login($user);

        $this->assertTrue($this->authService->hasPermission('inventory.create'));
        $this->assertFalse($this->authService->hasPermission('admin.user_management'));
    }

    /** @test */
    public function auth_service_returns_user_permissions_grouped_by_module()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        Auth::login($user);

        $permissions = $this->authService->getUserPermissions();

        $this->assertArrayHasKey('inventory', $permissions);
        $this->assertIsArray($permissions['inventory']);
        $this->assertNotEmpty($permissions['inventory']);
    }

    /** @test */
    public function auth_service_returns_accessible_modules()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        Auth::login($user);

        $modules = $this->authService->getAccessibleModules();

        $this->assertArrayHasKey('inventory', $modules);
        $this->assertArrayNotHasKey('admin', $modules);
    }

    /** @test */
    public function auth_service_generates_navigation_menu()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');
        Auth::login($user);

        $navigation = $this->authService->getNavigationMenu();

        $this->assertIsArray($navigation);
        $this->assertNotEmpty($navigation);

        // Should have dashboard
        $dashboardItem = collect($navigation)->firstWhere('name', 'Dashboard');
        $this->assertNotNull($dashboardItem);

        // Should have inventory module
        $inventoryItem = collect($navigation)->firstWhere('route', 'inventory');
        $this->assertNotNull($inventoryItem);
    }
}

// tests/Feature/MiddlewareTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function role_middleware_allows_access_with_correct_role()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertStatus(200);
    }

    /** @test */
    public function role_middleware_denies_access_without_role()
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertStatus(403)
            ->assertJson([
                'message' => 'Forbidden',
                'error' => 'You do not have the required role to access this resource'
            ]);
    }

    /** @test */
    public function permission_middleware_allows_access_with_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        // Assuming you have a route protected by permission middleware
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/items')
            ->assertStatus(200);
    }

    /** @test */
    public function module_access_middleware_works_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        $this->actingAs($user, 'sanctum');

        // Should allow read access
        $this->getJson('/api/items')->assertStatus(200);

        // Should allow create access
        $this->postJson('/api/items', [
            'name' => 'Test Item',
            'item_code' => 'TEST001',
            'category_id' => 1,
            'uom_id' => 1,
        ])->assertStatus(201);

        // Should deny delete access (depending on role permissions)
        $this->deleteJson('/api/items/1')->assertStatus(403);
    }
}
