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
    if (request()->wantsJson()) {
        return response()->json(['products' => $products], 200);
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
        'code' => 'required|string|max:10|unique:products,code',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
        'quantity' => 'required|integer|min:0',
        'price' => 'required|numeric|min:0',
    ]);

    $validated['user_id'] = Auth::user()->id;

    $product = Product::create($validated);
    if ($request->wantsJson()) {
        return response()->json(['message' => 'Producto creado.'], 201);
    }
    return redirect()->route('products.index')->with('success','Producto creado.');

   }
   public function edit(Product $product)
   {
    if (request()->wantsJson()) {
        return response()->json(['product' => $product], 200);
    }
    return view('products.edit', compact('product'));
   }
   public function update(Request $request, Product $product)
   {
    $request->validate([
        'code' => 'required|unique:products,code,'.$product->id,
        'name' => 'required',
        'description' => 'required',
        'quantity' => 'required|integer',
        'price' => 'required|numeric',
    ]);
    $product->update($request->all());
    return redirect()->route('products.index')->with('success','Producto actualizado.');
   }
   public function destroy(Product $product)
   {
    $product->delete();
    return redirect()->route('products.index')->with('success','Producto eliminado.');
   }
}
