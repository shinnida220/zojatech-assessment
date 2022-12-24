<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Route;

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
            return response()->json([
                'status' => false,
                'message' => 'Data validation failed',
                'errors' => $validator->errors()->all()
            ], 400);
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

    /**
     * Verify new user email
     */
    public function verifyEmail(Request $request) {
        // Setup the validator
        $validator = Validator::make($request->all(), [
            "email_verification_code" => "required|string|max:6|min:6",
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please enter a valid OTP. The OTP is the six (6) alphanumeric code you received when you signed up.'
            ], 400);
        }

        try {
            // Lets find the user.
            $user = User::where(['verification_code' => $request->email_verification_code])->first();
            
            if (!$user) {
                // set and return response
                return response()->json([
                    'status' => false,
                    'message' => 'You have entered an invalid OTP. Please check and try again.'
                ], 404);
            } else if(!empty($user->email_verified_at)){
                // set and return response
                return response()->json([
                    'status' => true,
                    'message' => 'Your email is already veorifed.'
                ], 200);
            } else {
                // Lets update the account
                DB::beginTransaction();

                $user->email_verified_at = now();
                $user->verification_code = null;
                $user->save();

                // Finalize the operation
                DB::commit();

                // set and return response
                return response()->json([
                    'status' => true,
                    'message' => 'Your email address was successfully verified. Please proceed to login.'
                ], 200);
            }
        } catch (\Exception $e) {

            Log::error("Email verifiaction error: " . $e);
            DB::rollBack();

            //api response
            return response()->json(['status' => false, 'message' => 'An unexpected error has occured. Please try again later.'], 500);
        }
    }

    /**
     * user sign in
     */
    public function signin(Request $request) {
        // Setup the validator
        $validator = Validator::make($request->all(), [
            "email" => "bail|required|email",
            'password' => 'required',
        ]);

        // Check if we have validation errors and return necessary error message
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Please ensure your email and password are entered and try again',
            ], 400);
        }

        try {
            $routeName = Route::currentRouteName();
            $user = null;
            if ($routeName === 'admin.signin') {
                $user = User::where(['email' => $request->email, 'user_type' => 'admin'])->first();

                if (!$user || !Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Login failed. Incorrect email/password combination',
                    ], 401);
                }

                DB::beginTransaction();
                // First invalidate all other tokens
                $user->tokens()->delete();
                // Create user token
                $token = $user->createToken('apiToken', ['admin'])->plainTextToken;

                // Finalize
                DB::commit();
            } else {
                $user = User::with('wallet')->where('email', $request->email)->first();

                if (!$user || !Hash::check($request->password, $user->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Login failed. Incorrect email/password combination'
                    ], 401);
                }

                // Check for verification
                if (!$user->isVerified()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Login failed. Please verify your email to proceed.'
                    ], 401);
                }

                // Check for suspension
                // Check for verification
                if (!$user->isActive()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Login failed. Your account has been suspended.'
                    ], 401);
                }

                DB::beginTransaction();
                // First invalidate all other tokens
                $user->tokens()->delete();
                // Create user token
                $token = $user->createToken('apiToken', ['user'])->plainTextToken;
                // Finalize
                DB::commit();
            }
            
            // Return response
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => compact('token','user')
            ], 200);

        } catch( \Exception $e) {
            Log::error("Login error: " . $e);
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error has occured, please try again.',
            ], 500);
        }
    }

    /** Signout user */
    public function signout(Request $request) {
        try {
            DB::beginTransaction();
            // Invalidate all other tokens
            $user->tokens()->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Logout successful'
            ], 200);
        } catch (\Exception $e){
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Logout failed. Please try again.'
            ], 422);
        }
    }
}
