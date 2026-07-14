<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'area' => ['required', 'in:hospital,consultorios,cafeteria'],
            'es_admin' => ['nullable', 'boolean'],
            'acceso_reportes' => ['nullable', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'area' => $validated['area'],
            'es_admin' => $request->boolean('es_admin'),
            'acceso_reportes' => $request->boolean('acceso_reportes'),
        ]);

        return redirect('/usuarios')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(int $id)
    {
        $usuario = User::findOrFail($id);

        return view('usuarios.editar', compact('usuario'));
    }

    public function update(Request $request, int $id)
    {
        $usuario = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'area' => ['required', 'in:hospital,consultorios,cafeteria'],
            'es_admin' => ['nullable', 'boolean'],
            'acceso_reportes' => ['nullable', 'boolean'],
        ]);

        $usuario->name = $validated['name'];
        $usuario->email = $validated['email'];
        $usuario->area = $validated['area'];
        $usuario->es_admin = $request->boolean('es_admin');
        $usuario->acceso_reportes = $request->boolean('acceso_reportes');

        if (!empty($validated['password'])) {
            $usuario->password = $validated['password'];
        }

        $usuario->save();

        return redirect('/usuarios')->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        if ($id === Auth::id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        User::findOrFail($id)->delete();

        return redirect('/usuarios')->with('status', 'Usuario eliminado correctamente.');
    }
}
