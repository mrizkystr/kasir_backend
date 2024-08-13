<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::with('product')->get();

        // Map the response to include product name safely
        $cartItems = $cartItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product ? $item->product->name : 'Unknown Product',
                'quantity' => $item->quantity,
                'note' => $item->note,
            ];
        });

        return response()->json($cartItems);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required_without:items|exists:products,id',
            'quantity' => 'required_without:items|integer|min:1',
            'items' => 'array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $cartItems = [];

        if (isset($validatedData['items'])) {
            foreach ($validatedData['items'] as $item) {
                $cartItem = CartItem::create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
                $cartItems[] = $cartItem;
            }
        } else {
            $cartItem = CartItem::create([
                'product_id' => $validatedData['product_id'],
                'quantity' => $validatedData['quantity'],
            ]);
            $cartItems[] = $cartItem;
        }

        return response()->json($cartItems, 201);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'note' => 'sometimes|nullable|string|max:255',
        ]);

        $cartItem->update($request->only(['quantity', 'note']));

        return response()->json($cartItem);
    }

    public function destroy($id)
    {
        CartItem::destroy($id);
    }
}
