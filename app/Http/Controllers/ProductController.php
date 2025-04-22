<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
   public function index()
   {         
        $products = Product::all();
        
        // Escapar datos para evitar XSS
        foreach ($products as $product) {
            $product->name = e($product->name);
            $product->description = e($product->description);
            $product->code = e($product->code);
        }
        
        return view("products.index", compact("products"));
   }

   public function create()
   {
    return view("products.create");
   }
   public function store(Request $request)
   {
    
    $validated = $request->validate([
        'code' => [
            'required',
            'unique:products,code',
            'regex:/^[a-zA-Z0-9_-]+$/', 
            'max:50'
        ],
        'name' => [
            'required',
            'string',
            'max:255',
            'regex:/^[a-zA-Z0-9\s.,_-]+$/' 
        ],
        'description' => [
            'required',
            'string',
            'max:1000',
            'regex:/^[\w\s\.,!?()\-&]+$/' 
        ],
        'quantity' => [
            'required',
            'integer',
            'min:0',
            'max:999999' 
        ],
        'price' => [
            'required',
            'numeric',
            'min:0',
            'max:999999.99' 
        ],
    ]);
    
    // Crear producto con datos sanitizados
    $product = Product::create([
        'code' => trim($validated['code']),
        'name' => trim($validated['name']),
        'description' => trim($validated['description']),
        'quantity' => (int)$validated['quantity'],
        'price' => (float)$validated['price'],
    ]);
    
    return redirect()->route('products.index')->with('success','Producto creado.');

   }
   public function edit(Product $product)
   {
    // Sanitizar datos para la vista
    $product->name = e($product->name);
    $product->description = e($product->description);
    $product->code = e($product->code);

    return view('products.edit', compact('product'));
   }
   public function update(Request $request, Product $product)
   {
    
     $validated = $request->validate([
        'code' => [
            'required',
            'unique:products,code,' . $product->id,
            'regex:/^[a-zA-Z0-9_-]+$/', 
            'max:50'
        ],
        'name' => [
            'required',
            'string',
            'max:255',
            'regex:/^[a-zA-Z0-9\s.,_-]+$/' 
        ],
        'description' => [
            'required',
            'string',
            'max:1000',
            'regex:/^[\w\s\.,!?()\-&]+$/' 
        ],
        'quantity' => [
            'required',
            'integer',
            'min:0',
            'max:999999' 
        ],
        'price' => [
            'required',
            'numeric',
            'min:0',
            'max:999999.99' 
        ],
    ]);

    // Actualizar con datos sanitizados
    $product->update([
        'code' => trim($validated['code']),
        'name' => trim($validated['name']),
        'description' => trim($validated['description']),
        'quantity' => (int)$validated['quantity'],
        'price' => (float)$validated['price'],
    ]);
 
    return redirect()->route('products.index')->with('success','Producto actualizado.');

   }
   public function destroy(Product $product)
   {

    $product->delete(); 

    return redirect()->route('products.index')->with('success','Producto eliminado.');

   }

}
