<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Notification;
use App\Notifications\AppNotification;

class AccountManagementController extends Controller
{
    /** Place a bank on an account */
    public function ban(Request $request) {
        // Setup the validator
        $validator = Validator::make($request->all(), [
            "user_id" => "bail|required|numeric"
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ], 400);
        }

        try {
            if (auth()->user()->id == $request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to ban your own account.',
                ], 403);
            }

            DB::beginTransaction();
            $user = User::where(['id' => $request->user_id])->first();

            // If we have a valid user
            if ($user) {
                $user->is_active = 0;
                $user->save();

                // Logout the user from our system
                $user->tokens()->delete();

                // Notify user
                $message = 'This is to notify you that your account has been suspended';
                $subject = "Account Update Notification";
                Notification::route('mail', $user->email)->notify(
                    new AppNotification($message, $subject)
                );

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'The user account was successfully banned.',
                ], 201);

            } else {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'The user account you selected no longer exist. Please check and try again.',
                ], 403);
            }
        } catch( \Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error has occured. Please try again later.',
            ], 500);
        }
    }

    /** Lift the ban on an account */
    public function unban(Request $request) {
        // Setup the validator
        $validator = Validator::make($request->all(), [
            "user_id" => "bail|required|numeric"
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ], 400);
        }

        try {
            if (auth()->user()->id == $request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to re-instate your own account.',
                ], 403);
            }

            DB::beginTransaction();
            $user = User::where(['id' => $request->user_id])->first();

            // If we have a valid user
            if ($user) {
                $user->is_active = 1;
                $user->save();

                // Notify user
                $message = 'This is to notify you that the ban on your account has been removed.';
                $subject = "Account Update Notification";
                Notification::route('mail', $user->email)->notify(
                    new AppNotification($message, $subject)
                );

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'The user account was successfully re-instated.',
                ], 201);

            } else {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'The user account you selected no longer exist. Please check and try again.',
                ], 403);
            }
        } catch( \Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error has occured. Please try again later.',
            ], 500);
        }
    }

    /** Promote a regular user account to admin account */
    public function promote(Request $request) {
        // Setup the validator
        $validator = Validator::make($request->all(), [
            "user_id" => "bail|required|numeric"
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ], 400);
        }

        try {
            if (auth()->user()->id == $request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to promote your own account.',
                ], 403);
            }

            DB::beginTransaction();
            $user = User::where(['id' => $request->user_id, 'user_type' => 'user'])->first();

            // If we have a valid user
            if ($user) {
                $user->user_type = 'admin';
                $user->save();

                // Notify user
                $message = 'This is to notify you that your account role has changed from  Regular to Admin.';
                $subject = "Account Update Notification";
                Notification::route('mail', $user->email)->notify(
                    new AppNotification($message, $subject)
                );

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'The user account was successfully promoted.',
                ], 201);

            } else {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'The user account you selected no longer exist or is not a regular account. Please check and try again.',
                ], 403);
            }
        } catch( \Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error has occured. Please try again later.',
            ], 500);
        }
    }

    /** Make an admin account a regular account */
    public function demote(Request $request) {
        // Setup the validator
        $validator = Validator::make($request->all(), [
            "user_id" => "bail|required|numeric"
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ], 400);
        }

        try {
            if (auth()->user()->id == $request->user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to promote your own account.',
                ], 403);
            }

            DB::beginTransaction();
            $user = User::where(['id' => $request->user_id, 'user_type' => 'admin'])->first();

            // If we have a valid user
            if ($user) {
                $user->user_type = 'user';
                $user->save();

                // Notify user
                $message = 'This is to notify you that your account role has changed from Admin to Regular.';
                $subject = "Account Update Notification";
                Notification::route('mail', $user->email)->notify(
                    new AppNotification($message, $subject)
                );

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'The user account was successfully demoted.',
                ], 201);

            } else {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'The user account you selected no longer exist or is not an admin account. Please check and try again.',
                ], 403);
            }
        } catch( \Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error has occured. Please try again later.',
            ], 500);
        }
    }
}
