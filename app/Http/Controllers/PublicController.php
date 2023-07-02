<?php

namespace App\Http\Controllers;

use App\Models\User as ModelsUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    //
    public function Register(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = ModelsUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $clientRepository = new ClientRepository();
        $client = $clientRepository->create($user->id, $user->name . ' Client', '');
        $client->makeVisible(['secret']);

        $user->client_id = $client->id;
        $user->client_secret = $client->secret;
        $user->save();

        return response()->json([
            'message' => 'Registration successful'
        ], 200);
    }
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|exists:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }


        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            $clientRepository = new ClientRepository();
            $client = $clientRepository->createPersonalAccessClient(
                $user->id,
                $user->name . ' Personal Access Client',
                ''
            );
            $client->makeVisible(['secret']);

            $token = $user->createToken('YourAppName')->accessToken;

            return response()->json([
                'token' => $token,
                'client_id' => $client->id,
                'client_secret' => $client->secret,
            ]);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

}
