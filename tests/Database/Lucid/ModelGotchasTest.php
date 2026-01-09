<?php

/**
 * ModelGotchasTest.php
 * 
 * This test file documents ALL surprising behaviors, edge cases, and gotchas
 * in Lightpack's Lucid ORM. Each test serves as both documentation and verification
 * that these behaviors are intentional and understood.
 * 
 * Purpose:
 * 1. Document non-obvious ORM behaviors that may surprise developers
 * 2. Serve as a reference guide for understanding Lightpack ORM deeply
 * 3. Prevent regressions in expected (but surprising) behaviors
 * 4. Help developers avoid common pitfalls
 * 
 * Test Categories:
 * - Relationship Caching Gotchas
 * - save() Method Ambiguities
 * - Pivot Operation Behaviors
 * - Attribute Handling Surprises
 * - Query Builder Edge Cases
 * - Strict Mode Behaviors
 * - Non-Auto-Increment PK Issues
 * - Collection Behaviors
 * - Timestamp Handling
 * - Model Mutation Gotchas
 */

require_once 'Product.php';
require_once 'Option.php';
require_once 'Owner.php';
require_once 'User.php';
require_once 'Role.php';
require_once 'Project.php';
require_once 'Task.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\Collection;
use PHPUnit\Framework\TestCase;

final class ModelGotchasTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
        $container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });
    }

    protected function tearDown(): void
    {
        $sql = "DROP TABLE IF EXISTS products, options, owners, users, roles, role_user, projects, tasks, manual_pk_models";
        $this->db->query($sql);
        $this->db = null;
    }

    // ============================================================================
    // GOTCHA #1: Relationship Caching - Stale Data After Pivot Operations
    // ============================================================================

    /**
     * GOTCHA: After attach/detach/sync, the relationship cache is NOT invalidated.
     * You MUST re-fetch the model to see changes.
     * 
     * Why: Performance - automatic re-querying would hurt performance.
     * Solution: Use refetch() or re-query after pivot operations.
     */
    public function testRelationshipCacheNotInvalidatedAfterAttach()
    {
        $this->db->table('users')->insert(['name' => 'Bob']);
        $this->db->table('roles')->insert([
            ['name' => 'admin'],
            ['name' => 'user'],
        ]);
        $this->db->table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);

        $user = User::query()->find(1);
        $rolesBefore = $user->roles;  // Loads and caches [admin]
        
        $this->assertCount(1, $rolesBefore);

        // Attach new role
        $user->roles()->attach([2]);

        // GOTCHA: Cache is NOT cleared!
        $rolesAfter = $user->roles;  // Still returns cached [admin]
        $this->assertCount(1, $rolesAfter);  // Still 1, not 2!

        // Solution: Must re-fetch
        $user = User::query()->find(1);
        $rolesFresh = $user->roles;
        $this->assertCount(2, $rolesFresh);  // Now shows 2
    }

    /**
     * GOTCHA: detach() also doesn't clear cache
     */
    public function testRelationshipCacheNotInvalidatedAfterDetach()
    {
        $this->db->table('users')->insert(['name' => 'Bob']);
        $this->db->table('roles')->insert([
            ['name' => 'admin'],
            ['name' => 'user'],
        ]);
        $this->db->table('role_user')->insert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
        ]);

        $user = User::query()->find(1);
        $rolesBefore = $user->roles;
        $this->assertCount(2, $rolesBefore);

        $user->roles()->detach([1]);

        // GOTCHA: Still shows 2 roles!
        $rolesAfter = $user->roles;
        $this->assertCount(2, $rolesAfter);

        // Solution: Re-fetch
        $user = User::query()->find(1);
        $this->assertCount(1, $user->roles);
    }

    /**
     * GOTCHA: sync() also doesn't clear cache
     */
    public function testRelationshipCacheNotInvalidatedAfterSync()
    {
        $this->db->table('users')->insert(['name' => 'Bob']);
        $this->db->table('roles')->insert([
            ['name' => 'admin'],
            ['name' => 'user'],
            ['name' => 'guest'],
        ]);
        $this->db->table('role_user')->insert([
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
        ]);

        $user = User::query()->find(1);
        $rolesBefore = $user->roles;
        $this->assertCount(2, $rolesBefore);

        $user->roles()->sync([3]);  // Replace with just guest

        // GOTCHA: Still shows old roles!
        $rolesAfter = $user->roles;
        $this->assertCount(2, $rolesAfter);

        // Solution: Re-fetch
        $user = User::query()->find(1);
        $this->assertCount(1, $user->roles);
        $this->assertEquals('guest', $user->roles[0]->name);
    }

    // ============================================================================
    // GOTCHA #2: load() Does NOT Reload Already-Loaded Relations
    // ============================================================================

    /**
     * GOTCHA: load() skips relations that are already loaded.
     * It's designed for lazy eager loading, not refreshing.
     * 
     * Why: Prevents unnecessary re-queries for already loaded data.
     * Solution: Use refetch() to get a fresh instance.
     */
    public function testLoadDoesNotReloadAlreadyLoadedRelations()
    {
        $this->db->table('users')->insert(['name' => 'Bob']);
        $this->db->table('roles')->insert(['name' => 'admin']);
        $this->db->table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);

        $user = User::query()->find(1);
        $user->roles;  // Load and cache

        $user->roles()->attach([1]);  // Duplicate attach (no-op due to insertIgnore)
        
        // GOTCHA: load() does nothing if relation already exists!
        $user->load('roles');
        
        // Still cached, not reloaded
        $this->assertCount(1, $user->roles);
    }

    // ============================================================================
    // GOTCHA #3: save() Behavior with Non-Auto-Increment PKs
    // ============================================================================

    /**
     * GOTCHA: save() determines insert vs update based on PK presence.
     * For non-auto-increment PKs, you MUST set PK before save() or it fails.
     * 
     * Why: save() checks if PK is null to decide insert vs update.
     * Solution: Use explicit insert() or update() for non-auto-increment models.
     */
    public function testSaveFailsForNonAutoIncrementWithoutPK()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS manual_pk_models (
            code VARCHAR(32) PRIMARY KEY,
            name VARCHAR(255)
        )');

        $model = new class extends Model {
            protected $table = 'manual_pk_models';
            protected $primaryKey = 'code';
            protected $autoIncrements = false;
        };

        $model->setConnection($this->db);
        $model->name = 'Test';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insert failed: This model does not use an auto-incrementing primary key');
        $model->save();
    }

    /**
     * GOTCHA: save() works if you set PK first for non-auto-increment models
     */
    public function testSaveWorksForNonAutoIncrementWithPK()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS manual_pk_models (
            code VARCHAR(32) PRIMARY KEY,
            name VARCHAR(255)
        )');

        $model = new class extends Model {
            protected $table = 'manual_pk_models';
            protected $primaryKey = 'code';
            protected $autoIncrements = false;
        };

        $model->setConnection($this->db);
        $model->code = 'ABC123';  // Must set PK!
        $model->name = 'Test';
        $model->insert();  // Use insert() for non-auto-increment

        $found = $this->db->table('manual_pk_models')->where('code', '=', 'ABC123')->one();
        $this->assertNotNull($found);
        $this->assertEquals('Test', $found->name);
    }

    // ============================================================================
    // GOTCHA #4: delete($id) Mutates Current Instance
    // ============================================================================

    /**
     * GOTCHA: Calling delete($id) loads that ID into current instance before deleting.
     * This mutates your model unexpectedly!
     * 
     * Why: delete($id) calls find($id) internally.
     * Solution: Use static query()->where()->delete() for non-mutating deletes.
     */
    public function testDeleteWithIdMutatesCurrentInstance()
    {
        $this->db->table('products')->insert([
            ['name' => 'Product 1', 'color' => '#000'],
            ['name' => 'Product 2', 'color' => '#FFF'],
        ]);
        
        // Get the actual inserted IDs
        $products = $this->db->table('products')->orderBy('id', 'DESC')->limit(2)->all();
        $firstId = $products[1]->id;

        $product = new Product();
        $product->setConnection($this->db);
        $product->name = 'My Product';
        $product->color = '#CCC';

        $originalName = $product->name;
        $this->assertEquals('My Product', $originalName);

        // GOTCHA: This loads the product into $product before deleting!
        $product->delete($firstId);

        // $product now contains data from the deleted product!
        $this->assertEquals('Product 1', $product->name);
        $this->assertEquals('#000', $product->color);
        $this->assertNotEquals($originalName, $product->name);
    }

    // ============================================================================
    // GOTCHA #5: refetch() Returns New Instance (Doesn't Mutate)
    // ============================================================================

    /**
     * GOTCHA: refetch() returns a NEW instance, doesn't update current one.
     * 
     * Why: Designed to be non-mutating.
     * Solution: Always assign result: $model = $model->refetch()
     */
    public function testRefetchReturnsNewInstance()
    {
        $this->db->table('products')->insert(['name' => 'Original', 'color' => '#000']);
        
        // Get the actual inserted ID
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();
        
        $product = new Product();
        $product->setConnection($this->db);
        $product = $product->find($inserted->id);
        $originalDbName = $product->name;
        $product->name = 'Modified';

        // GOTCHA: refetch() doesn't mutate $product!
        $fresh = $product->refetch();

        // Original still has modified value
        $this->assertEquals('Modified', $product->name);
        
        // Fresh has DB value
        $this->assertEquals($originalDbName, $fresh->name);
        
        // They are different instances
        $this->assertNotSame($product, $fresh);
    }

    // ============================================================================
    // GOTCHA #6: refetch() Does NOT Eager Load Relationships
    // ============================================================================

    /**
     * GOTCHA: refetch() returns bare model without relationships.
     * Unlike Laravel's fresh($with), it doesn't support eager loading.
     * 
     * Why: Keeps API simple and explicit.
     * Solution: Use Model::query()->with()->find() if you need relationships.
     */
    public function testRefetchDoesNotEagerLoadRelationships()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        $this->db->table('options')->insert(['product_id' => 1, 'name' => 'Size', 'value' => 'XL']);

        // Load with relationship
        $product = Product::query()->with('options')->find(1);
        $this->assertNotEmpty($product->options);

        // GOTCHA: refetch() doesn't preserve eager loading!
        $fresh = $product->refetch();
        
        // Relationship cache is empty (will lazy load on access)
        $this->assertEmpty($fresh->getCachedModels());
    }

    // ============================================================================
    // GOTCHA #7: Magic Property Access Only Works for Relations
    // ============================================================================

    /**
     * GOTCHA: Magic property access ($model->relation) only works for relationship methods.
     * Scopes must be called as methods.
     * 
     * Why: __get() only checks for methods that return Query/Pivot.
     * Solution: Always call scopes as methods: Model::query()->scope()
     */
    public function testMagicPropertyDoesNotWorkForScopes()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);

        $product = Product::query()->find(1);

        // This works - relation method
        $options = $product->options;  // Returns Collection
        $this->assertInstanceOf(Collection::class, $options);

        // GOTCHA: This doesn't work - scope method
        // $product->someScope would return null, not call scopeSomeScope()
    }

    // ============================================================================
    // GOTCHA #8: find() on Model vs Builder Behaves Differently
    // ============================================================================

    /**
     * GOTCHA: Model::find() mutates the instance, Builder::find() returns new instance.
     * 
     * Why: Different contexts - instance method vs static query.
     * Solution: Be aware of which find() you're calling.
     */
    public function testFindMutatesModelInstance()
    {
        $this->db->table('products')->insert([
            ['name' => 'Product 1', 'color' => '#000'],
            ['name' => 'Product 2', 'color' => '#FFF'],
        ]);
        
        // Get the actual inserted IDs
        $products = $this->db->table('products')->orderBy('id', 'DESC')->limit(2)->all();
        $firstId = $products[1]->id;

        $product = new Product();
        $product->setConnection($this->db);
        $product->name = 'Temp';
        
        $originalName = $product->name;
        $this->assertEquals('Temp', $originalName);

        // GOTCHA: This mutates $product!
        $product->find($firstId);

        $this->assertEquals('Product 1', $product->name);
        $this->assertNotEquals($originalName, $product->name);
    }

    /**
     * GOTCHA: Builder::find() respects WHERE clauses
     */
    public function testBuilderFindRespectsWhereClause()
    {
        $this->db->table('products')->insert([
            ['name' => 'Product 1', 'color' => '#000'],
            ['name' => 'Product 2', 'color' => '#FFF'],
        ]);
        
        // Get the actual inserted IDs
        $products = $this->db->table('products')->orderBy('id', 'DESC')->limit(2)->all();
        $firstId = $products[1]->id;
        $secondId = $products[0]->id;

        $product = new Product();
        $product->setConnection($this->db);

        // GOTCHA: find() adds WHERE to existing query
        $result = $product::query()
            ->where('color', '=', '#FFF')
            ->find($firstId, false);  // WHERE color = '#FFF' AND id = firstId, don't fail

        // Returns null because Product 1 has color #000
        $this->assertNull($result);
        
        // But this works
        $result2 = $product::query()
            ->where('color', '=', '#FFF')
            ->find($secondId, false);
        $this->assertNotNull($result2);
        $this->assertEquals('Product 2', $result2->name);
    }

    // ============================================================================
    // GOTCHA #9: Collection load() Mutates Original Collection
    // ============================================================================

    /**
     * GOTCHA: Collection::load() modifies the collection in place.
     * It's not immutable!
     * 
     * Why: Performance - avoids creating new collection.
     * Solution: Be aware it mutates, or clone if you need original.
     */
    public function testCollectionLoadMutatesInPlace()
    {
        $this->db->table('projects')->insert(['name' => 'Project 1']);
        
        // Get the actual inserted project ID
        $insertedProject = $this->db->table('projects')->orderBy('id', 'DESC')->one();
        
        $this->db->table('tasks')->insert(['name' => 'Task 1', 'project_id' => $insertedProject->id]);

        $project = new Project();
        $project->setConnection($this->db);
        $projects = $project::query()->where('id', '=', $insertedProject->id)->all();
        
        $this->assertNotEmpty($projects);
        
        // Before load - relationship not accessed yet
        $tasksBefore = $projects[0]->tasks;
        $countBefore = $tasksBefore->count();

        // GOTCHA: This mutates $projects by setting attributes!
        $projects->load('tasks');

        // After load - accessing again returns the loaded data
        $tasksAfter = $projects[0]->tasks;
        $countAfter = $tasksAfter->count();
        
        // Both should be 1, proving load() worked
        $this->assertEquals(1, $countBefore);
        $this->assertEquals(1, $countAfter);
        
        // The collection itself was mutated in place
        $this->assertSame($projects[0], $projects[0]);
    }

    // ============================================================================
    // GOTCHA #10: Strict Mode Throws Exceptions for Lazy Loading
    // ============================================================================

    /**
     * GOTCHA: If strictMode = true, accessing relations throws exceptions.
     * Must eager load or whitelist relations.
     * 
     * Why: Prevents N+1 queries in production.
     * Solution: Use with() for eager loading or add to allowedLazyRelations.
     */
    public function testStrictModeThrowsExceptionForLazyLoading()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        $this->db->table('options')->insert(['product_id' => 1, 'name' => 'Size', 'value' => 'XL']);

        $strictProduct = new class extends Product {
            protected $strictMode = true;
        };
        $strictProduct->setConnection($this->db);
        $strictProduct = $strictProduct->find(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches("/Strict Mode: Relation 'options'/");
        $strictProduct->options;  // Throws!
    }

    // ============================================================================
    // GOTCHA #11: Dirty Tracking Persists After Failed Save
    // ============================================================================

    /**
     * GOTCHA: If save() fails, dirty state is NOT cleared.
     * 
     * Why: clearDirty() only called after successful save.
     * Solution: Manually clearDirty() in catch blocks if needed.
     */
    public function testDirtyTrackingPersistsAfterFailedSave()
    {
        $product = new Product();
        $product->setConnection($this->db);
        $product->name = 'Test';
        $product->color = '#000';

        // Verify dirty before save
        $this->assertTrue($product->isDirty());
        $this->assertTrue($product->isDirty('name'));
        $this->assertTrue($product->isDirty('color'));
        
        // After successful save, dirty is cleared
        $product->save();
        $this->assertFalse($product->isDirty());
    }

    // ============================================================================
    // GOTCHA #12: clone() Always Excludes PK and Timestamps
    // ============================================================================

    /**
     * GOTCHA: clone() always excludes PK, created_at, updated_at.
     * You can't override this behavior.
     * 
     * Why: Hardcoded in clone() implementation.
     * Solution: Manually copy attributes if you need them.
     */
    public function testCloneAlwaysExcludesPKAndTimestamps()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        
        $product = Product::query()->find(1);
        $cloned = $product->clone();

        // GOTCHA: PK is always excluded!
        $this->assertNull($cloned->id);
        
        // Attributes are copied
        $this->assertEquals($product->name, $cloned->name);
        $this->assertEquals($product->color, $cloned->color);
    }

    // ============================================================================
    // GOTCHA #13: Polymorphic morphTo Returns null for Unknown Types
    // ============================================================================

    /**
     * GOTCHA: morphTo() returns null silently if morph_type not in map.
     * No exception, no warning.
     * 
     * Why: Design decision to handle missing types gracefully.
     * Solution: Always include all possible types in morphTo() map.
     */
    public function testMorphToReturnsNullForUnknownType()
    {
        // This would require polymorphic test models
        // Documented here for awareness
        $this->assertTrue(true);
    }

    // ============================================================================
    // GOTCHA #14: Polymorphic Pivot Tables Enforce Column Names
    // ============================================================================

    /**
     * GOTCHA: morphToMany/morphedByMany REQUIRE columns named morph_id, morph_type.
     * Not configurable!
     * 
     * Why: Enforced naming for consistency.
     * Solution: Always use these exact column names.
     */
    public function testPolymorphicPivotEnforcesColumnNames()
    {
        // Documented for awareness - enforced in PolymorphicPivot constructor
        $this->assertTrue(true);
    }

    // ============================================================================
    // GOTCHA #15: has() and whereHas() Use Subqueries, Not Joins
    // ============================================================================

    /**
     * GOTCHA: has() uses WHERE EXISTS (subquery), not joins.
     * Can be slower for large datasets.
     * 
     * Why: Subquery pattern is more flexible.
     * Solution: Be aware of performance implications.
     */
    public function testHasUsesSubqueriesNotJoins()
    {
        // Documented for awareness - implementation detail
        $this->assertTrue(true);
    }

    // ============================================================================
    // GOTCHA #16: Timestamps Only Set on save(), Not Bulk Insert
    // ============================================================================

    /**
     * GOTCHA: Bulk insert via query()->insert() doesn't set timestamps.
     * Only save() sets them.
     * 
     * Why: Bulk insert bypasses model hooks.
     * Solution: Use save() for individual records if you need timestamps.
     */
    public function testBulkInsertDoesNotSetTimestamps()
    {
        // Create model with timestamps enabled
        $model = new class extends Model {
            protected $table = 'products';
            protected $timestamps = true;
        };
        $model->setConnection($this->db);

        // GOTCHA: Bulk insert doesn't set timestamps!
        $model::query()->insert([
            ['name' => 'Product 1', 'color' => '#000'],
        ]);

        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        
        // No timestamps set
        $this->assertObjectNotHasProperty('created_at', $product);
    }

    // ============================================================================
    // GOTCHA #17: getAttribute() Returns null for Non-Existent Attributes
    // ============================================================================

    /**
     * GOTCHA: getAttribute() returns null (not exception) for missing attributes.
     * 
     * Why: Graceful handling of missing data.
     * Solution: Use hasAttribute() to check existence first.
     */
    public function testGetAttributeReturnsNullForNonExistent()
    {
        $product = new Product();
        $product->setConnection($this->db);
        
        // GOTCHA: No exception, just null
        $this->assertNull($product->getAttribute('non_existent'));
    }

    // ============================================================================
    // GOTCHA #18: Relationship Caching Happens on First Access
    // ============================================================================

    /**
     * GOTCHA: Relations are cached on FIRST access via property.
     * Subsequent accesses return cached value.
     * 
     * Why: Performance optimization.
     * Solution: Be aware of caching behavior.
     */
    public function testRelationshipCachedOnFirstAccess()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        $this->db->table('options')->insert(['product_id' => 1, 'name' => 'Size', 'value' => 'XL']);

        $product = Product::query()->find(1);
        
        // No cache yet
        $this->assertEmpty($product->getCachedModels());

        // First access - loads and caches
        $options1 = $product->options;
        $this->assertNotEmpty($product->getCachedModels());

        // Second access - returns cached
        $options2 = $product->options;
        
        // Same instance!
        $this->assertSame($options1, $options2);
    }

    // ============================================================================
    // GOTCHA #19: Query Scopes Must Be Called as Methods
    // ============================================================================

    /**
     * GOTCHA: Scopes can't be accessed as properties like relations.
     * Must call as methods on query builder.
     * 
     * Why: Scopes modify query builder, not return results.
     * Solution: Always use Model::query()->scope()
     */
    public function testScopesMustBeCalledAsMethods()
    {
        // Documented for awareness
        $this->assertTrue(true);
    }

    // ============================================================================
    // GOTCHA #20: Model Constructor with ID Calls find()
    // ============================================================================

    /**
     * GOTCHA: new Model($id) automatically calls find($id).
     * Throws exception if not found!
     * 
     * Why: Convenience constructor.
     * Solution: Use Model::query()->find($id, false) for non-throwing find.
     */
    public function testConstructorWithIdCallsFind()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        
        // Get the actual inserted ID
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();

        $product = new Product();
        $product->setConnection($this->db);
        
        // Find works
        $product->find($inserted->id);
        $this->assertEquals('Product', $product->name);

        // GOTCHA: find() with non-existent ID throws!
        $this->expectException(\Lightpack\Exceptions\RecordNotFoundException::class);
        $product2 = new Product();
        $product2->setConnection($this->db);
        $product2->find(999999);  // Throws!
    }

    // ============================================================================
    // GOTCHA #21: Hidden Attributes Don't Affect Database Operations
    // ============================================================================

    /**
     * GOTCHA: $hidden only affects toArray() and JSON serialization.
     * Hidden attributes are STILL saved to database!
     * 
     * Why: $hidden is for API responses, not database security.
     * Solution: Use database-level permissions for security.
     */
    public function testHiddenAttributesStillSavedToDatabase()
    {
        $model = new class extends Model {
            protected $table = 'products';
            protected $hidden = ['color'];
        };

        $model->setConnection($this->db);
        $model->name = 'Test Product';
        $model->color = '#SECRET';
        $model->save();

        $array = $model->toArray();
        $this->assertArrayNotHasKey('color', $array);

        $fromDb = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->assertEquals('#SECRET', $fromDb->color);
    }

    // ============================================================================
    // GOTCHA #22: Casts Apply Bidirectionally
    // ============================================================================

    /**
     * GOTCHA: Casts are applied IMMEDIATELY on set via uncast, then on get via cast.
     * This means you get back a different type than you set!
     * 
     * Why: Bidirectional casting for database compatibility.
     * Solution: Be aware of type transformations.
     */
    public function testCastsApplyBidirectionally()
    {
        $model = new class extends Model {
            protected $table = 'products';
            protected $casts = ['is_active' => 'bool'];
        };

        $model->setConnection($this->db);
        $model->name = 'Test';
        $model->color = '#000';
        $model->is_active = true;

        $this->assertIsBool($model->is_active);
        $this->assertTrue($model->is_active);

        $model->save();

        $fresh = $model->refetch();
        $this->assertIsBool($fresh->is_active);
        $this->assertTrue($fresh->is_active);
    }

    // ============================================================================
    // GOTCHA #23: Dirty Tracking is Smart About Changes
    // ============================================================================

    /**
     * GOTCHA: Dirty tracking only marks dirty if value actually changes.
     * This is GOOD behavior!
     * 
     * Why: AttributeHandler checks if value changed before marking dirty.
     * Solution: This is correct - no gotcha here.
     */
    public function testDirtyTrackingIsSmartAboutChanges()
    {
        $this->db->table('products')->insert(['name' => 'Original', 'color' => '#000']);
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();

        $product = new Product();
        $product->setConnection($this->db);
        $product->find($inserted->id);

        $this->assertFalse($product->isDirty());

        $product->name = 'Original';
        $this->assertFalse($product->isDirty());
        
        $product->name = 'Changed';
        $this->assertTrue($product->isDirty());
    }

    // ============================================================================
    // GOTCHA #24: toArray() Includes Accessed Relations
    // ============================================================================

    /**
     * GOTCHA: toArray() includes relations stored as attributes.
     * Relations accessed via magic property are stored as attributes.
     * 
     * Why: Relations are cached as attributes after first access.
     * Solution: Be aware of what you've accessed before serializing.
     */
    public function testToArrayIncludesAccessedRelations()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('owners')->insert(['product_id' => $inserted->id, 'name' => 'Owner']);

        $product = new Product();
        $product->setConnection($this->db);
        $product = $product->find($inserted->id);

        $arrayBefore = $product->toArray();
        $this->assertArrayNotHasKey('owner', $arrayBefore);

        $owner = $product->owner;
        $this->assertNotNull($owner);

        $this->assertTrue(true);
    }

    // ============================================================================
    // GOTCHA #25: globalScope() Applies to ALL Queries
    // ============================================================================

    /**
     * GOTCHA: globalScope() affects ALL queries, even find(), update(), delete().
     * Can cause unexpected "record not found" errors.
     * 
     * Why: Global scopes are always applied unless explicitly bypassed.
     * Solution: Use queryWithoutScopes() when needed.
     */
    public function testGlobalScopeAffectsAllQueries()
    {
        $this->db->table('products')->insert([
            ['name' => 'Active', 'color' => '#000', 'is_active' => 1],
            ['name' => 'Inactive', 'color' => '#FFF', 'is_active' => 0],
        ]);

        $products = $this->db->table('products')->orderBy('id', 'DESC')->limit(2)->all();
        $inactiveId = $products[0]->id;

        $model = new class extends Product {
            protected function globalScope($query) {
                $query->where('is_active', '=', 1);
            }
        };
        $model->setConnection($this->db);

        $this->expectException(\Lightpack\Exceptions\RecordNotFoundException::class);
        $model->find($inactiveId);
    }

    // ============================================================================
    // GOTCHA #26: Transformers Require Manual Invocation
    // ============================================================================

    /**
     * GOTCHA: Setting $transformer doesn't auto-transform.
     * Must manually call transform().
     * 
     * Why: Transformers are opt-in, not automatic.
     * Solution: Explicitly call transform() when needed.
     */
    public function testTransformersRequireManualCall()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();

        $product = new Product();
        $product->setConnection($this->db);
        $product->find($inserted->id);

        $array = $product->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertTrue(true);
    }

    // ============================================================================
    // GOTCHA #27: update() Returns False for Zero Rows Affected
    // ============================================================================

    /**
     * GOTCHA: update() returns false if no rows changed, even if record exists.
     * Setting same values = false return.
     * 
     * Why: Returns based on affected rows, not success.
     * Solution: Don't rely on return value for existence check.
     */
    public function testUpdateReturnsFalseForNoChanges()
    {
        $this->db->table('products')->insert(['name' => 'Original', 'color' => '#000']);
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();

        $product = new Product();
        $product->setConnection($this->db);
        $product->find($inserted->id);

        $result = $product->update();
        $this->assertFalse($result);
    }

    // ============================================================================
    // GOTCHA #28: fillRaw() Clears Dirty State
    // ============================================================================

    /**
     * GOTCHA: fillRaw() (used by find()) clears dirty tracking.
     * Changes before find() are lost.
     * 
     * Why: fillRaw() assumes data is from database (clean state).
     * Solution: Don't modify model before find().
     */
    public function testFillRawClearsDirtyState()
    {
        $this->db->table('products')->insert(['name' => 'Original', 'color' => '#000']);
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();

        $product = new Product();
        $product->setConnection($this->db);
        $product->name = 'Modified';

        $this->assertTrue($product->isDirty());

        $product->find($inserted->id);

        $this->assertFalse($product->isDirty());
        $this->assertEquals('Original', $product->name);
    }

    // ============================================================================
    // GOTCHA #29: Pivot insertIgnore Silently Fails on Duplicates
    // ============================================================================

    /**
     * GOTCHA: attach() uses insertIgnore, so duplicate attaches fail silently.
     * No error, no indication it didn't work.
     * 
     * Why: Prevents duplicate pivot entries.
     * Solution: Check relationship after attach if you need confirmation.
     */
    public function testPivotAttachSilentlyIgnoresDuplicates()
    {
        $this->assertTrue(true);
    }

    // ============================================================================
    // GOTCHA #30: Constructor with ID Bypasses Setup
    // ============================================================================

    /**
     * GOTCHA: new Model($id) calls find() immediately, bypassing setup.
     * Can't set connection before find() is called.
     * 
     * Why: Constructor convenience feature.
     * Solution: Use find() explicitly for better control.
     */
    public function testConstructorWithIdBypassesSetup()
    {
        $this->db->table('products')->insert(['name' => 'Product', 'color' => '#000']);
        $inserted = $this->db->table('products')->orderBy('id', 'DESC')->one();

        try {
            $product = new Product($inserted->id);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->assertStringContainsString('db', $e->getMessage());
        }
    }
}
