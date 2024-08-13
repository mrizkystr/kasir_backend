<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Midtrans\Snap;
use Midtrans\Config;

class OrderController extends Controller
{
    public function __construct()
    {
        // Set konfigurasi Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = env('MIDTRANS_IS_SANITIZED', true);
        Config::$is3ds = env('MIDTRANS_IS_3DS', true);
    }

    public function index()
    {
        return Order::all();
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'cart_item_ids' => 'required|array|min:1',
            'cart_item_ids.*' => 'exists:cart_items,id',
        ]);

        // Mengambil item dari keranjang berdasarkan ID yang diberikan
        $cartItems = CartItem::whereIn('id', $request->cart_item_ids)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'No items found in the cart.'], 404);
        }

        // Menghitung total harga
        $totalPrice = $cartItems->sum(function ($item) {
            return $item->product ? $item->product->price * $item->quantity : 0;
        });

        // Membuat pesanan baru
        $order = Order::create([
            'order_number' => Str::uuid(),
            'total_price' => $totalPrice,
            'payment_status' => 'pending',
        ]);

        // Memperbarui item di keranjang untuk menandai bahwa mereka telah dipesan
        foreach ($cartItems as $item) {
            $item->update([
                'order_id' => $order->id,
                'status' => 'ordered',
            ]);
        }

        // Cek apakah user ada, jika tidak, gunakan nilai default
        $user = auth()->user();
        $customerDetails = [
            'first_name' => $user ? $user->name : 'Guest',
            'email' => $user ? $user->email : 'guest@example.com',
            'phone' => $user ? $user->phone : '0000000000',
        ];

        // Membuat transaksi Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $totalPrice,
            ],
            'customer_details' => $customerDetails,
            'item_details' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'price' => optional($item->product)->price ?: 0,
                    'quantity' => $item->quantity,
                    'name' => optional($item->product)->name ?: 'Unknown Product',
                ];
            })->toArray(),
        ];

        // Mendapatkan URL pembayaran dari Midtrans
        $snapToken = Snap::getSnapToken($params);

        // Membuat response dengan product_id
        $orderDetails = $cartItems->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'price' => $item->product ? $item->product->price : 0,
                'quantity' => $item->quantity,
                'subtotal' => $item->product ? $item->product->price * $item->quantity : 0,
            ];
        });

        return response()->json([
            'order_number' => $order->order_number,
            'total_price' => $totalPrice,
            'payment_status' => $order->payment_status,
            'order_details' => $orderDetails,
            'snap_token' => $snapToken,
        ]);
    }



    public function show($id)
    {
        return Order::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $request->validate([
            'payment_status' => 'required|string',
        ]);

        $order->update($request->all());

        return $order;
    }

    public function destroy($id)
    {
        Order::destroy($id);
    }
}
