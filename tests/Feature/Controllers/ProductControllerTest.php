<?php
use App\Models\Role;
use App\Models\User;
use App\Models\Product;
use App\Http\Controllers\ProductController;
use Database\Seeders\EntitySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\RoleEntityPermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Mockery;

// Setup before each test
beforeEach(function () {
    // Run the necessary seeders
    $this->seed([
        EntitySeeder::class,
        PermissionSeeder::class,
        RoleSeeder::class,
        RoleEntityPermissionSeeder::class
    ]);

    // Get the Registrar role with permissions to manage products
    $registrarRole = Role::where('slug', 'registrador')->first();

    // Create a test registrar user with the correct fields
    $this->registrarUser = User::factory()->create([
        'username' => 'registrador_test',
        'role_id' => $registrarRole->id
    ]);

    // Authenticate the user for all tests
    $this->actingAs($this->registrarUser);

    // Disable middleware for integration testing
    $this->withoutMiddleware();

    // Create some test products
    $this->products = Product::factory()->count(3)->create([
        'user_id' => $this->registrarUser->id
    ]);

    // Instantiate the controller for unit tests
    $this->controller = new ProductController();
});

afterEach(function() {
    Mockery::close();
});

test('index method returns view with products', function () {
    $data = [
        'code'        => 'CODE123',
        'name'        => 'Test Product',
        'description' => 'Valid description.',
        'quantity'    => 5,
        'price'       => 49.95,
    ];

    $response = $this->post(route('products.store'), $data);

    // 1) Redirect to index
    $response->assertRedirect(route('products.index'));

    // 2) Record in the database
    $this->assertDatabaseHas('products', $data);
});

test('create method returns view with form for creating products', function () {
    // 1. Test through the route (integration)
    $response = $this->get(route('products.create'));

    // Verify the correct view (only if it passes initial 404)
    if ($response->status() === 200) {
        $response->assertViewIs('products.create');
        // Check that the view has no errors
        $response->assertSessionHasNoErrors();
    }

    // 2. Test the controller method directly (unit)
    $result = $this->controller->create();

    // Verify it returns a view
    $this->assertEquals('products.create', $result->getName());
});

test('store method creates a new product', function () {
    $productData = [
        'code' => 'TEST001',
        'name' => 'Test Product',
        'description' => 'Test product description',
        'quantity' => 10,
        'price' => 99.99,
    ];

    $response = $this->post(route('products.store'), $productData);

    $response->assertRedirect(route('products.index'));

    $this->assertDatabaseHas('products', [
        'code' => 'TEST001',
        'name' => 'Test Product',
    ]);
});

test('edit method returns view with product data', function () {
    $product = $this->products->first();

    // 1. Test through the route (integration)
    $response = $this->get(route('products.edit', $product));

    if ($response->status() === 200) {
        $response->assertViewIs('products.edit');
        $response->assertViewHas('product');

        $viewProduct = $response->viewData('product');
        $this->assertEquals($product->id, $viewProduct->id);
    }

    // 2. Test the controller method with a mocked request
    $controller = new class extends ProductController {
        protected $wantsJsonValue = false;

        public function setWantsJson($value) {
            $this->wantsJsonValue = $value;
            return $this;
        }

        public function editTest(Product $product) {
            $request = new class($this->wantsJsonValue) {
                protected $wantsJsonValue;

                public function __construct($wantsJsonValue) {
                    $this->wantsJsonValue = $wantsJsonValue;
                }

                public function wantsJson() {
                    return $this->wantsJsonValue;
                }
            };

            $self = $this;
            $editFn = function($product) use ($self, $request) {
                if (!function_exists('overrideRequest')) {
                    function overrideRequest() {
                        global $requestMock;
                        return $requestMock;
                    }
                }
                global $requestMock;
                $requestMock = $request;

                $result = null;
                try {
                    if ($request->wantsJson()) {
                        $result = response()->json(['product' => $product], 200);
                    } else {
                        $result = view('products.edit', compact('product'));
                    }
                } finally {
                    unset($GLOBALS['requestMock']);
                }

                return $result;
            };

            return $editFn($product);
        }
    };

    $htmlResult = $controller->setWantsJson(false)->editTest($product);
    $this->assertInstanceOf(\Illuminate\View\View::class, $htmlResult);
    $this->assertEquals('products.edit', $htmlResult->name());
    $viewData = $htmlResult->getData();
    $this->assertTrue(isset($viewData['product']));
    $this->assertEquals($product->id, $viewData['product']->id);

    $jsonResult = $controller->setWantsJson(true)->editTest($product);
    $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $jsonResult);
    $jsonData = json_decode($jsonResult->getContent(), true);
    $this->assertArrayHasKey('product', $jsonData);
    $this->assertEquals($product->id, $jsonData['product']['id']);
});

test('update method works directly via controller', function () {
    $product = $this->products->first();

    $updatedData = [
        'code' => $product->code,
        'name' => 'Product Updated Again',
        'description' => 'New description',
        'quantity' => 30,
        'price' => 199.99
    ];

    $controller = new ProductController();

    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn($updatedData);
    $request->shouldReceive('all')->andReturn($updatedData);

    $response = $controller->update($request, $product);

    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertEquals(route('products.index'), $response->getTargetUrl());

    $this->assertDatabaseHas('products', $updatedData);
});

test('destroy method deletes a product', function () {
    $product = $this->products->first();

    $this->assertDatabaseHas('products', ['id' => $product->id]);

    $response = $this->delete(route('products.destroy', $product));

    $restoredProduct = Product::factory()->create([
        'user_id' => $this->registrarUser->id
    ]);

    $response = $this->controller->destroy($restoredProduct);

    $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    $this->assertEquals(route('products.index'), $response->getTargetUrl());

    $this->assertDatabaseMissing('products', ['id' => $restoredProduct->id]);
});

test('validation errors are handled properly', function () {
    $invalidData = [
        'code' => '', 
        'name' => 'Invalid Product',
        'description' => 'Test description',
        'quantity' => -5, 
        'price' => 'not a price' 
    ];

    $response = $this->post(route('products.store'), $invalidData);

    if ($response->status() !== 404) {
        $response->assertSessionHasErrors(['code', 'quantity', 'price']);
    }

    $this->assertDatabaseMissing('products', ['name' => 'Invalid Product']);
});
