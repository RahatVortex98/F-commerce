<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerAccountController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user();
        $orders = $customer->orders()
            ->with('product')
            ->latest()
            ->paginate(10);
        $orderCounts = $customer->orders()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('account.dashboard', [
            'orders' => $orders,
            'orderCount' => $orderCounts->sum(),
            'activeOrderCount' => $orderCounts->only(['pending', 'confirmed', 'shipped'])->sum(),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $order = $request->user()->orders()
            ->with('product')
            ->whereKey($order->getKey())
            ->firstOrFail();

        return view('account.orders.show', compact('order'));
    }

    public function confirmReceived(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order): void {
            $customerOrder = $request->user()->orders()
                ->lockForUpdate()
                ->findOrFail($order->getKey());

            if ($customerOrder->customer_received_at !== null) {
                return;
            }

            abort_unless($customerOrder->status === 'delivered', 409);

            $customerOrder->forceFill(['customer_received_at' => now()])->save();
        });

        return redirect()->route('account.orders.show', $order)
            ->with('status', 'Thanks for confirming that your order arrived.');
    }
}
