<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    
    // 1. Create order (pending)
    public function store()
    {
        $order = Order::create([
            'status' => 'pending'
        ]);

        return response()->json($order);
    }

    // 2. Add item
    public function addItem(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order locked']);
        }

        $product = Product::findOrFail($request->product_id);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'price' => $product->price
        ]);

        return response()->json(['message' => 'Item added']);
    }

    // 3. Pay (checkout)
    public function pay(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            $order = Order::with('items.product')->findOrFail($id);

            if ($order->status !== 'pending') {
                return response()->json(['message' => 'Invalid order']);
            }

            $total = 0;

            foreach ($order->items as $item) {
                $total += $item->quantity * $item->price;
            }

            if ($request->paid_amount < $total) {
                return response()->json(['message' => 'Not enough money']);
            }

            $change = $request->paid_amount - $total;

            $order->update([
                'total' => $total,
                'paid_amount' => $request->paid_amount,
                'change' => $change,
                'status' => 'completed',
                'paid_at' => now()
            ]);

            foreach ($order->items as $item) {
                $item->product->decrement('stock', $item->quantity);
            }

            return response()->json([
                'message' => 'Payment success',
                'change' => $change
            ]);
        });
    }

    // 4. Cancel
    public function cancel($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Cannot cancel']);
        }

        $order->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Order cancelled'
        ]);
    }
}
