<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{

    /**
     * Signup method for regular user.
     */
    public function signup(Request $request){

        // Setup the validator
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:50",
            "email" => "required_without:telephone|email|max:50|unique:users",
            'password' => ['required', 'confirmed', Password::min(6)->mixedCase()->numbers()->uncompromised()],
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {

            $response = [
                'status' => false,
                'message' => 'Data validation failed',
                'errors' => $validator->errors()->all()
            ];

            return response()->json($response, 400);
        }

        try {
            DB::beginTransaction();

            // Lets setup the new user info to be created
            $payload = $request->only('name', 'email');
            $payload['password'] = Hash::make($request->password);
            $payload['user_type'] = 'user';
            $payload['verification_code'] = (new User)->generateCode(6);

            // Create the user
            User::create($payload);

            // Finalize the operation
            DB::commit();

            // set and return response
            return response()->json([
                'status' => true,
                'message' => 'Registration successful. A verification link has been sent to your email address.'
            ], 200);

        } catch (\Exception $e) {

            Log::error("Sign-up Error: " . $e);
            DB::rollBack();

            //api response
            return response()->json(['status' => false, 'message' => 'An unexpected error has occured. Please try again later.'], 500);
        }
    }
}
