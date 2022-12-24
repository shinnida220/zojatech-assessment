<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Route;
use Notification;
use App\Notifications\InviteUser;

class InviteController extends Controller
{
    public function invite(Request $request) {
        $routeName = Route::currentRouteName();
        $invitees = [];

        if ($routeName === 'admin.invite.single') {
            // Setup the validator
            $validator = Validator::make($request->all(), [
                "email" => "bail|required|email",
                'invite_text' => 'required|string|min:10',
            ]);

            // Check if we have validation errors and return necessary error message
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please ensure you enter a valid email and invite text.',
                ], 400);
            }

            $invitees[0] = $request->email;
        } else {
            // Setup the validator
            $validator = Validator::make($request->all(), [
                "email.*" => "bail|required|email",
                'invite_text' => 'required|string|min:10',
            ]);

            // Check if we have validation errors and return necessary error message
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please ensure you enter valid emails and a proper invite text.',
                ], 400);
            }

            $invitees = $request->email;
        }

        try {
            // Queue up the notification 
            foreach($invitees as $invitee){
                Notification::route('mail', $invitee)->notify(new InviteUser($request->invite_text));
            }

            return response()->json([
                'status' => true,
                'message' => 'Invite has been sent.',
                'data' => [
                    'invitees' => $invitees
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An unexpectee error has occured. Please try again.',
            ], 400);
        }
    }
}
