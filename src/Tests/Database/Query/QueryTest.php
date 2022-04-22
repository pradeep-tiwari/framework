<?php

use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Pagination\Pagination as BasePagination;
use Lightpack\Database\Lucid\Pagination as LucidPagination;
use PHPUnit\Framework\TestCase;

// Initalize container
$container = new Container();

final class QueryTest extends TestCase
{
    private $db;

    /** @var \Lightpack\Database\Query\Query */
    private $query;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->query = new \Lightpack\Database\Query\Query('products', $this->db);

        // Configure container
        global $container;
        $container->register('db', function() { return $this->db; });
        $container->register('request', function() { return new Request(); });

        // Set Request URI
        $_SERVER['REQUEST_URI'] = '/lightpack';
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE `products`, `options`, `owners`;";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testSelectFetchAll()
    {
        // Test 1
        $products = $this->query->select('id', 'name')->fetchAll();
        $this->assertGreaterThan(0, count($products));

        // Test 2
        $products = $this->query->select('id', 'name')->where('color', '=', 'maroon')->fetchAll();
        $this->assertEquals(0, count($products));
        $this->assertIsArray($products);
        $this->assertEmpty($products);
        $this->assertNotNull($products);
    }

    public function testSelectFetchOne()
    {
        // Test 1
        $product = $this->query->fetchOne();
        $this->assertTrue(isset($product->id));

        // Test 2
        $product = $this->query->where('color', '=', 'maroon')->fetchOne();
        $this->assertFalse($product);
    }

    public function testCompiledSelectQuery()
    {
        // Test 1
        $this->assertEquals(
            'SELECT * FROM products',
            $this->query->getCompiledSelect()
        );
        $this->query->resetQuery();

        // Test 2
        $this->query->select('id', 'name');

        $this->assertEquals(
            'SELECT id, name FROM products',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 3
        $this->query->select('id', 'name')->orderBy('id');

        $this->assertEquals(
            'SELECT id, name FROM products ORDER BY id ASC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 4
        $this->query->select('id', 'name')->orderBy('id', 'DESC');

        $this->assertEquals(
            'SELECT id, name FROM products ORDER BY id DESC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 5
        $this->query->select('id', 'name')->orderBy('name', 'DESC')->orderBy('id', 'DESC');

        $this->assertEquals(
            'SELECT id, name FROM products ORDER BY name DESC, id DESC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 6
        $this->query->select('name')->distinct();

        $this->assertEquals(
            'SELECT DISTINCT name FROM products',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 7
        $this->query->where('id', '>', 2);

        $this->assertEquals(
            'SELECT * FROM products WHERE id > ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 8
        $this->query->where('id', '>', 2)->where('color', '=', '#000');

        $this->assertEquals(
            'SELECT * FROM products WHERE id > ? AND color = ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 9
        $this->query->where('id', '>', 2)->where('color', '=', '#000')->orWhere('color', '=', '#FFF');

        $this->assertEquals(
            'SELECT * FROM products WHERE id > ? AND color = ? OR color = ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 10
        $this->query->whereIn('id', [23, 24, 25]);

        $this->assertEquals(
            'SELECT * FROM products WHERE id IN (?, ?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 11
        $this->query->whereIn('id', [23, 24, 25])->orWhereIn('color', ['#000', '#FFF']);

        $this->assertEquals(
            'SELECT * FROM products WHERE id IN (?, ?, ?) OR color IN (?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 12
        $this->query->whereNotIn('id', [23, 24, 25]);

        $this->assertEquals(
            'SELECT * FROM products WHERE id NOT IN (?, ?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 13
        $this->query->whereNotIn('id', [23, 24, 25])->orWhereNotIn('color', ['#000', '#FFF']);

        $this->assertEquals(
            'SELECT * FROM products WHERE id NOT IN (?, ?, ?) OR color NOT IN (?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 14
        $this->query->join('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT * FROM products INNER JOIN options ON products.id = options.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 15
        $this->query->leftJoin('options', 'options.product_id', 'products.id');

        $this->assertEquals(
            'SELECT * FROM products LEFT JOIN options ON options.product_id = products.id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 16
        $this->query->rightJoin('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT * FROM products RIGHT JOIN options ON products.id = options.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 17
        $this->query->select('products.*', 'options.name AS oname')->join('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT products.*, options.name AS oname FROM products INNER JOIN options ON products.id = options.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 18: alias
        $this->query->alias('p')->join('options AS o', 'p.id', 'o.product_id')->select('p.*', 'o.name AS oname');

        $this->assertEquals(
            'SELECT p.*, o.name AS oname FROM products AS p INNER JOIN options AS o ON p.id = o.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();
    }

    public function testGetMagicMethod()
    {
        $this->assertEquals('products', $this->query->table);
        $this->assertEquals([], $this->query->bindings);
    }

    public function testInsertMethod()
    {
        $products = $this->query->fetchAll();
        $productsCountBeforeInsert = count($products);

        $this->query->insert([
            'name' => 'Product 4',
            'color' => '#CCC',
        ]);

        $products = $this->query->fetchAll();
        $productsCountAfterInsert = count($products);

        $this->assertEquals($productsCountBeforeInsert + 1, $productsCountAfterInsert);
    }

    public function testUpdateMethod()
    {
        $product = $this->query->select('id')->fetchOne();

        $this->query->where('id', '=', $product->id)->update(
            ['color' => '#09F']
        );

        $updatedProduct = $this->query->select('color')->where('id', '=', $product->id)->fetchOne();

        $this->assertEquals('#09F', $updatedProduct->color);
    }

    public function testDeleteMethod()
    {
        $product = $this->query->orderBy('id', 'DESC')->fetchOne();
        $products = $this->query->fetchAll();
        $productsCountBeforeDelete = count($products);

        $this->query->where('id', '=', $product->id)->delete();

        $products = $this->query->fetchAll();
        $productsCountAfterDelete = count($products);

        $this->assertEquals($productsCountBeforeDelete - 1, $productsCountAfterDelete);
    }

    public function testWhereLogicalGroupingOfParameters()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE (color = ? OR color = ?)';
        $this->query->where(function($q) {
            $q->where('color', '=', '#000')->orWhere('color', '=', '#FFF');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE id = ? AND (color = ? OR color = ?)';
        $this->query->where('id', '=', 1)->where(function($q) {
            $q->where('color', '=', '#000')->orWhere('color', '=', '#FFF');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereInLogicalGroupingOfParameters()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE (color IN (?, ?) OR color IN (?, ?))';
        $this->query->where(function($q) {
            $q->whereIn('color', ['#000', '#FFF'])->orWhereIn('color', ['#000', '#FFF']);
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE size IN (SELECT id FROM sizes)';
        $this->query->whereIn('size', function($q) {
                $q->select('id')->from('sizes');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE color IN (?, ?, ?) AND size IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->whereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM products WHERE color IN (?, ?, ?) OR size IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->orWhereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 5
        $sql = 'SELECT * FROM products WHERE color IN (?, ?, ?) AND size IN (SELECT id FROM sizes WHERE is_active = ?) OR size IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->whereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            })
            ->orWhereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 6
        $sql = 'SELECT * FROM products WHERE color NOT IN (?, ?, ?) AND size NOT IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereNotIn('color', ['#000', '#FFF', '#CCC'])
            ->whereNotIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 7
        $sql = 'SELECT * FROM products WHERE color NOT IN (?, ?, ?) OR size NOT IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereNotIn('color', ['#000', '#FFF', '#CCC'])
            ->orWhereNotIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereColumnMatchesSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE size IN (SELECT id FROM sizes WHERE size = ?)';
        $this->query->where('size', 'IN', function($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereExistsSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE EXISTS (SELECT id FROM sizes WHERE size = ?)';
        $this->query->whereExists(function($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereNotExistsSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE NOT EXISTS (SELECT id FROM sizes WHERE size = ?)';
        $this->query->whereNotExists(function($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereRaw()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE color = ? AND size = ?';
        $this->query->whereRaw('color = ? AND size = ?', ['#000', 'XL']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE color = ? AND size = ? AND is_active = 1';
        $this->query->whereRaw('color = ? AND size = ?', ['#000', 'XL'])->whereRaw('is_active = 1');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE (color = ? AND size = ?) OR is_active = 1';
        $this->query->whereRaw('(color = ? AND size = ?)', ['#000', 'XL'])->orWhereRaw('is_active = 1');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = "SELECT * FROM products WHERE color = ? OR status = 'active'";
        $this->query->where('color', '=', '#000')->orWhereRaw("status = 'active'");
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereNullConditions()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE color IS NULL';
        $this->query->whereNull('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE color IS NOT NULL';
        $this->query->whereNotNull('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE color IS NULL OR size IS NULL';
        $this->query->whereNull('color')->orWhereNull('size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
        
        // Test 4
        $sql = 'SELECT * FROM products WHERE color IS NULL OR size IS NOT NULL';
        $this->query->whereNull('color')->orWhereNotNull('size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testLimitAndOffsetQueries()
    {
        // Test 1
        $sql = 'SELECT * FROM products LIMIT 10';
        $this->query->limit(10);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products LIMIT 10 OFFSET 20';
        $this->query->limit(10)->offset(20);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testGroupByQueries()
    {
        // Test 1
        $sql = 'SELECT * FROM products GROUP BY color';
        $this->query->groupBy('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products GROUP BY color, size';
        $this->query->groupBy('color', 'size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereBetweenConditions()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE price BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE price NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE price BETWEEN ? AND ? OR size BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20])->orWhereBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM products WHERE price NOT BETWEEN ? AND ? OR size NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20])->orWhereNotBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 5
        $sql = 'SELECT * FROM products WHERE price BETWEEN ? AND ? AND size BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20])->whereBetween('size', ['M', 'L']); 
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 6
        $sql = 'SELECT * FROM products WHERE price NOT BETWEEN ? AND ? AND size NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20])->whereNotBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 7: Expect exception when passing in an array with less than 2 values
        $this->expectException(Exception::class);
        $this->query->whereBetween('price', [10]);
        $this->query->resetQuery();
    }

    public function testPaginateMethod()
    {
        // Test 1
        $this->query->paginate(10, 20);
        $this->assertInstanceOf(BasePagination::class, $this->query->paginate(10, 20));
        $this->query->resetQuery();

        // Test 2
        require __DIR__ . '/../Lucid/Product.php';
        $products = Product::query()->paginate(10, 20);
        $this->assertInstanceOf(LucidPagination::class, $products);
    }
}
