<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário (Super Admin ou Dono de Loja)
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_STORE_OWNER,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'message' => 'Usuário criado com sucesso',
        ], 201);
    }

    /**
     * Login de usuário
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Conta inativa. Entre em contato com o suporte.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->load('store'),
                'token' => $token,
            ],
            'message' => 'Login realizado com sucesso',
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * Dados do usuário logado
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['store', 'stores']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Atualizar perfil
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
            'message' => 'Perfil atualizado com sucesso',
        ]);
    }

    /**
     * Alterar senha
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Senha atual incorreta'],
            ]);
        }

        $user->update([
            'password' => $request->password,
        ]);

        // Invalidar outros tokens (opcional)
        // $user->tokens()->where('id', '!=', $user->currentAccessTokenId())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Senha alterada com sucesso',
        ]);
    }

    /**
     * Esqueci a senha - enviar email de reset
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Aqui você enviaria o email com o link de reset
        // Por agora, retornamos sucesso simulado

        return response()->json([
            'success' => true,
            'message' => 'Se o email existir, você receberá um link de recuperação.',
        ]);
    }

    /**
     * Reset de senha
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Aqui você validaria o token e resetaria a senha
        // Por agora, retornamos sucesso simulado

        return response()->json([
            'success' => true,
            'message' => 'Senha resetada com sucesso',
        ]);
    }
}
