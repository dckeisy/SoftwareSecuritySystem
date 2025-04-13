<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
   public function index()
   {
    $products = Product::all();
    return view("products.index", compact("products"));
   }

   public function create()
   {
    return view("products.create");
   }
   public function store(Request $request)
   {
    $request->validate([
        'code' => 'required|unique:products,code',
        'name' => 'required',
        'description' => 'required',
        'quantity' => 'required|integer',
        'price' => 'required|numeric',
    ]);
    Product::create($request->all());
    return redirect()->route('products.index')->with('success','Producto creado.');

   }
   public function edit(Product $product)
   {
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
