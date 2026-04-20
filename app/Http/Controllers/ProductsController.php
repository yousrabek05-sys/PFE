<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'patient') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $products = Product::orderBy('name')->get();
        $products->transform(function ($product) {
            $product->stock_status = $this->getStockStatus($product);
            return $product;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $products
        ]);
    }

    // Add new product
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'assistant') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only assistants can add products'
            ], 403);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'quantity'    => 'required|integer|min:0',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock_alert' => 'nullable|integer|min:1',
        ]);

        $product = Product::create([
            'name'        => $request->name,
            'quantity'    => $request->quantity,
            'sold'        => 0, // starts at 0
            'price'       => $request->price,
            'description' => $request->description,
            'stock_alert' => $request->stock_alert ?? 5,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Product added successfully',
            'data'    => $product
        ], 201);
    }

    //Edit product info
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'assistant') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only assistants can update products'
            ], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'quantity'    => 'sometimes|integer|min:0',
            'price'       => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
            'stock_alert' => 'sometimes|integer|min:1',
        ]);

        $product->update($request->only([
            'name',
            'quantity',
            'price',
            'description',
            'stock_alert'
        ]));

        $this->verifyStock($product);

        return response()->json([
            'status'  => 'success',
            'message' => 'Product updated successfully',
            'data'    => $product
        ]);
    }
    // Delete product
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'assistant') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only assistants can delete products'
            ], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        $product->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }

    // Reduce product quantity
    public function diminuerStock(Request $request, $id)
    {
        $request->validate([
            'quantity_used' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        if ($user->role === 'patient') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        if ($product->quantity < $request->quantity_used) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Not enough stock available',
                'available' => $product->quantity
            ], 400);
        }

        $product->update([
            'quantity' => $product->quantity - $request->quantity_used,
            'sold'     => $product->sold + $request->quantity_used,
        ]);

        $this->verifyStock($product->fresh()); 

        return response()->json([
            'status'       => 'success',
            'message'      => 'Stock updated',
            'remaining'    => $product->quantity,
        ]);
    }

    // Check if stock is low
    private function verifyStock(Product $product)
    {
        if ($product->quantity <= $product->stock_alert) {

            $assistant = User::where('role', 'assistant')->first();

            if ($assistant) {
                Notification::create([
                    'user_id' => $assistant->id,
                    'message' => 'Low stock alert: ' . $product->name .
                                 ' has only ' . $product->quantity .
                                 ' units remaining',
                    'type'    => 'stock_alert',
                    'date'    => now(),
                    'is_read' => false,
                    'channel' => 'in_app',
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
            }
        }
    }
    private function getStockStatus(Product $product): string
    {
        if ($product->quantity === 0) {
            return 'out';      
        }

        if ($product->quantity <= $product->stock_alert) {
            return 'low';  
        }
    }
}