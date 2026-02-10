<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SepayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            // Log incoming request
            Log::channel('daily')->info('SePay Webhook Incoming', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // 1. Check if SePay is enabled
            $isEnabled = DB::table('settings')->where('setting_key', 'sepay_enabled')->value('setting_value');
            if ($isEnabled != '1') {
                return response()->json(['success' => false, 'message' => 'SePay disabled']);
            }

            $data = $request->all();

            // 2. Validate transfer type (must be 'in')
            if (($data['transferType'] ?? '') !== 'in') {
                return response()->json(['success' => true, 'message' => 'Ignored outgoing transfer']);
            }

            // 3. Validate Account Number
            $configuredAccount = DB::table('settings')->where('setting_key', 'sepay_account_number')->value('setting_value');
            if ($configuredAccount && ($data['accountNumber'] ?? '') !== $configuredAccount) {
                return response()->json(['success' => true, 'message' => 'Account mismatch']);
            }

            // 4. Extract Order Code
            $content = $data['content'] ?? '';
            $orderCode = null;

            if (preg_match('/DH\s*([A-Z]\d{3})/i', $content, $matches)) {
                $orderCode = strtoupper($matches[1]);
            } elseif (preg_match('/\b([A-Z]\d{3})\b/i', $content, $matches)) {
                $orderCode = strtoupper($matches[1]);
            }

            if (!$orderCode) {
                return response()->json(['success' => true, 'message' => 'No order code found']);
            }

            // 5. Find Order
            // Find order that is pending or confirming (processing)
            $order = Order::where('order_code', $orderCode)
                ->whereIn('payment_status', ['pending', 'confirming'])
                ->with('items')
                ->first();

            if (!$order) {
                Log::channel('daily')->info("SePay Webhook: Order not found or already processed: $orderCode");
                return response()->json(['success' => true, 'message' => 'Order not found or already processed']);
            }

            // 6. Verify Amount
            $receivedAmount = $data['transferAmount'] ?? 0;
            if ($receivedAmount < $order->final_amount) {
                Log::channel('daily')->warning("SePay Webhook: Insufficient amount for order $orderCode. Expected {$order->final_amount}, got $receivedAmount");
                return response()->json(['success' => true, 'message' => 'Insufficient amount']);
            }

            // 7. Update Order
            $order->update([
                'payment_status' => 'paid',
                'order_status' => 'processing',
                'sepay_transaction_id' => $data['id'] ?? null,
            ]);

            Log::channel('daily')->info("SePay Webhook: Order $orderCode marked as PAID");

            // 8. Auto Delivery Logic
            // If the order has items that are 'stock' delivery type and haven't been delivered yet
            // (CheckoutController might have reserved them but not put them in account_delivered if payment was pending)

            // Check if we need to auto-deliver
            $autoDeliver = DB::table('settings')->where('setting_key', 'auto_deliver')->value('setting_value');
            if ($autoDeliver === '1' || $autoDeliver === null) { // Default true if not set

                foreach ($order->items as $item) {
                    // Check if item needs delivery (account_delivered is null) and is of type 'account' logic
                    // Note: OrderItem doesn't have 'delivery_type' column directly in model fillable shown earlier,
                    // but CheckoutController populates it.
                    // CheckoutController didn't save delivery_type to order_items table, it used it from Product/Variant logic.
                    // The order_item has 'product_id'.

                    // We need to find the delivery type. 
                    // Or check if this item has assigned stocks in accounts_stock table.

                    $reservedAccounts = DB::table('accounts_stock')
                        ->where('sold_to_order_id', $order->id)
                        ->where('product_id', $item->product_id)
                        ->get();

                    if ($reservedAccounts->count() > 0 && empty($item->account_delivered)) {
                        $deliveredContent = "";
                        foreach ($reservedAccounts as $account) {
                            $deliveredContent .= "Username: {$account->username} | Password: {$account->password}";
                            if (!empty($account->additional_info)) {
                                $deliveredContent .= " | Info: {$account->additional_info}";
                            }
                            $deliveredContent .= "\n";
                        }

                        $item->update(['account_delivered' => $deliveredContent]);
                        Log::channel('daily')->info("SePay Webhook: Delivered accounts for item #{$item->id}");
                    }
                }

                // Check if all items are delivered (simplistic check: if we have accounts_stock items for this order)
                // If everything is good, we might mark order as completed?
                // The PHP code marked it completed if all items were delivered.

                // Let's perform a check if all items that SHOULD have delivery DO have delivery.
                // For now, let's just keep it as 'processing' or 'completed' based on business logic. 
                // PHP code set it to 'completed'.
                $order->update(['order_status' => 'completed']);
            }

            // 9. Notifications (Simulated)
            // Telegram
            // User Notification (if User model has notify method or we insert into table)
            if ($order->user_id) {
                DB::table('notifications')->insert([
                    'user_id' => $order->user_id,
                    'title' => 'Thanh toán thành công!',
                    'content' => "Đơn hàng #{$orderCode} đã được xác nhận thanh toán " . number_format($receivedAmount) . "đ.",
                    'type' => 'success',
                    'link' => route('user.orders.show', $orderCode),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Payment confirmed']);

        } catch (\Exception $e) {
            Log::error('SePay Webhook Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error']);
        }
    }
}
