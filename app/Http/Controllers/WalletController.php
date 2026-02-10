<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $transactions = $user->walletTransactions()
            ->paginate(10)
            ->through(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('User/Wallet', [
            'wallet' => [
                'balance' => $user->balance,
                'total_deposit' => $user->walletTransactions()->where('type', 'deposit')->sum('amount'),
                'total_spent' => $user->walletTransactions()->whereIn('type', ['purchase', 'withdraw'])->sum('amount'),
            ],
            'transactions' => $transactions,
        ]);
    }

    public function deposit(Request $request)
    {
        // Placeholder for logic if we implement automated deposit check here
        // Usually redirects to a payment gateway or shows bank info
        // The original PHP code generated a QR code.
        // We will handle the QR generation in the Vue component as per original logic.
        return back();
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50000',
            'bank_name' => 'required|string',
            'bank_account' => 'required|string',
            'account_name' => 'required|string',
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        if ($user->balance < $amount) {
            return back()->withErrors(['amount' => 'Số dư không đủ.']);
        }

        DB::transaction(function () use ($user, $amount, $request) {
            $balanceBefore = $user->balance;
            $newBalance = $balanceBefore - $amount;

            $user->update(['balance' => $newBalance]);

            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'withdraw',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'description' => "Rút tiền - {$request->bank_name} - {$request->bank_account} - {$request->account_name}",
            ]);
        });

        return back()->with('success', 'Yêu cầu rút tiền thành công.');
    }
}
