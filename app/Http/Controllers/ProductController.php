<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

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
    
    // Validación extendida para seguridad
    $validated = $request->validate([
        'code' => [
            'required',
            'unique:products,code',
            'regex:/^[a-zA-Z0-9_-]+$/', // Solo permitir caracteres seguros
            'max:50'
        ],
        'name' => [
            'required',
            'string',
            'max:255',
            'regex:/^[a-zA-Z0-9\s.,_-]+$/' // Permitir solo caracteres seguros
        ],
        'description' => [
            'required',
            'string',
            'max:1000' // Limitar longitud
        ],
        'quantity' => [
            'required',
            'integer',
            'min:0',
            'max:999999' // Establecer límites razonables
        ],
        'price' => [
            'required',
            'numeric',
            'min:0',
            'max:999999.99' // Establecer límites razonables
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
    
    // Regenerar sesión después de operaciones de escritura
    $request->session()->regenerate();
    
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
    
     // Validación extendida para seguridad
     $validated = $request->validate([
        'code' => [
            'required',
            'unique:products,code,' . $product->id,
            'regex:/^[a-zA-Z0-9_-]+$/', // Solo permitir caracteres seguros
            'max:50'
        ],
        'name' => [
            'required',
            'string',
            'max:255',
            'regex:/^[a-zA-Z0-9\s.,_-]+$/' // Permitir solo caracteres seguros
        ],
        'description' => [
            'required',
            'string',
            'max:1000' // Limitar longitud
        ],
        'quantity' => [
            'required',
            'integer',
            'min:0',
            'max:999999' // Establecer límites razonables
        ],
        'price' => [
            'required',
            'numeric',
            'min:0',
            'max:999999.99' // Establecer límites razonables
        ],
    ]);
    
    // Guardar datos originales para el log
    $originalData = $product->getAttributes();
    
    // Actualizar con datos sanitizados
    $product->update([
        'code' => trim($validated['code']),
        'name' => trim($validated['name']),
        'description' => trim($validated['description']),
        'quantity' => (int)$validated['quantity'],
        'price' => (float)$validated['price'],
    ]);
 
    // Regenerar sesión después de operaciones de escritura
    $request->session()->regenerate();

    return redirect()->route('products.index')->with('success','Producto actualizado.');

   }
   public function destroy(Product $product)
   {

    $product->delete(); 
    // Regenerar sesión después de operaciones de escritura
    $request->session()->regenerate();

    return redirect()->route('products.index')->with('success','Producto eliminado.');

   }

}
