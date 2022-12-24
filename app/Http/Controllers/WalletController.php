<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Wallet;

class WalletController extends Controller
{
    /**
     * withdraw
     */
    public function withdraw(Request $request) {
        // Setup the validator
        $validator = Validator::make($request->all(), [
            "amount" => "bail|required|numeric|min:1",
            "description" => "sometimes|string",
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Data validation failed',
                'errors' => $validator->errors()->all()
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            // Use a lock for update..
            $wallet = Wallet::where('user_id', $request->user()->id)
                ->lockForUpdate() // We don't want any update on this by any other process untill we are done
                ->first();

            // This will always return a wallet but lets wrap it aroud a check just in case.
            if ($wallet){
                // User may have logged in before suspension
                // Check for active sccount.

                if (!auth()->user()->isActive()) {
                    // Available balance not enough
                    DB::rollBack();

                    // api response
                    return response()->json([
                        'status' => false, 
                        'message' => 'Withdrawal failed. Your account is suspended.'
                    ], 403);
                }

                // keep the current balance..
                $currentBalance = $wallet->balance;

                if ($wallet->balance >= $request->amount) {
                    $wallet->balance -= $request->amount;
                    $history = $wallet->history;

                    // Generate the transaction record
                    $transaction = [
                        'initiated_by' => auth()->user()->id,
                        'amount' => $request->amount,
                        'initial_balance' => $currentBalance,
                        'current_balance' => $wallet->balance,
                        'description' => 'Self initiated withdrawal on '. now()->format('d/m/Y g:i a'),
                        'created_at' => now()->format('Y-m-d H:i:s')
                    ];

                    if (count($history) > 0){
                        $history = [$transaction, ...$history];
                    } else {
                        $history = [$transaction];
                    }

                    $wallet->history = $history;
                    $wallet->save();

                    // Now do a refresh and verify.
                    // This way if another process had this before us, we can be sure we are dealing wht the right values
                    $wallet->refresh();

                    if ($wallet->balance === ($currentBalance - $request->amount)) {
                        DB::commit();

                        return response()->json([
                            'status' => true,
                            'message' => 'Your withdrawal of '.number_format($request->amount, 2, '.', ',').' was successful',
                            'data' => [
                                'wallet' => [ 
                                    'initial_balance' => $currentBalance,
                                    'current_balance' => $wallet->balance 
                                ]
                            ]
                        ], 200);

                    } else {
                        // Account is tampered. We didn't have the expected amount after deduction
                        // Reverse the transaction
                        DB::rollBack();

                        // api response
                        return response()->json([
                            'status' => false, 
                            'message' => 'Withdrawal service is unavilable at the moment. Please try again later.'
                        ], 403);
                    }
                } else {
                    // Available balance not enough
                    DB::rollBack();

                    // api response
                    return response()->json([
                        'status' => false, 
                        'message' => 'Withdrawal failed. Your have insufficient balance.'
                    ], 403);
                }
            } else {
                // No wallet, should not happen but hey - u are here..
                DB::rollBack();

                // api response
                return response()->json([
                    'status' => false, 
                    'message' => 'Withdrawal service is unavilable at the moment. Please try again later.'
                ], 403);
            }
        } catch (\Exception $e){
            DB::rollBack();

            // api response
            return response()->json([
                'status' => false, 
                'message' => 'An unexpected error has occured. Please try again later.'
            ], 500);
        }
    }


    /**
     * fundWallet
     */
    public function fund(Request $request) {
        $routeName = Route::currentRouteName();

        if ($routeName !== 'admin.wallet.fund') {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to perform this operation'
            ], 403);
        }

        // Setup the validator
        $validator = Validator::make($request->all(), [
            "amount" => "bail|required|numeric|min:1",
            "user_id" => "required|numeric",
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Data validation failed',
                'errors' => $validator->errors()->all()
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            // Use a lock for update..
            $wallet = Wallet::with('user')->where('user_id', $request->user_id)
                ->lockForUpdate() // We don't want any update on this by any other process untill we are done
                ->first();

            // This will always return a wallet but lets wrap it aroud a check just in case.
            // Only user account, only performed by an admin
            if ($wallet && $wallet->user && $wallet->user->user_type === 'user' && $wallet->user->id !== auth()->user()->id){
                // keep the current balance..
                $currentBalance = $wallet->balance;
                
                // Update wallet
                $wallet->balance += $request->amount;
                $history = $wallet->history;

                // Generate the transaction record
                $transaction = [
                    'initiated_by' => auth()->user()->id,
                    'amount' => $request->amount,
                    'initial_balance' => $currentBalance,
                    'current_balance' => $wallet->balance,
                    'description' => 'Wallet funded by admin on '. now()->format('d/m/Y g:i a'),
                    'created_at' => now()->format('Y-m-d H:i:s')
                ];

                if (count($history) > 0){
                    $history = [$transaction, ...$history];
                } else {
                    $history = [$transaction];
                }

                $wallet->history = $history;
                $wallet->save();

                // Now do a refresh and verify.
                // This way if another process had this before us, we can be sure we are dealing wht the right values
                $wallet->refresh();

                if ($wallet->balance === ($currentBalance + $request->amount)) {
                    DB::commit();

                    return response()->json([
                        'status' => true,
                        'message' => 'Wallet was successfully funded with the sum of '.number_format($request->amount, 2, '.', ','),
                        'data' => [
                            'wallet' => [ 
                                'initial_balance' => $currentBalance,
                                'current_balance' => $wallet->balance 
                            ]
                        ]
                    ], 200);

                } else {
                    // Account is tampered. We didn't have the expected amount after deduction
                    // Reverse the transaction
                    DB::rollBack();

                    // api response
                    return response()->json([
                        'status' => false, 
                        'message' => 'Wallet funding service is unavilable at the moment. Please try again later.'
                    ], 403);
                }
                
            } else {
                // No wallet, should not happen but hey - u are here..
                DB::rollBack();

                // api response
                return response()->json([
                    'status' => false, 
                    'message' => 'Wallet funding service is unavilable at the moment. Please try again later.'
                ], 403);
            }
        } catch (\Exception $e){
            DB::rollBack();

            // api response
            return response()->json([
                'status' => false, 
                'message' => 'An unexpected error has occured. Please try again later.'
            ], 500);
        }
    }
}
