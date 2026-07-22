<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('name')->get();
        $bitacora = Bitacora::with('usuario')->orderByDesc('created_at')->paginate(20);

        return view('usuarios.index', compact('usuarios', 'bitacora'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'area' => ['required', 'in:hospital,consultorios,cafeteria,vinculacion'],
            'es_admin' => ['nullable', 'boolean'],
            'acceso_reportes' => ['nullable', 'boolean'],
            'acceso_vinculacion' => ['nullable', 'boolean'],
            'es_admin_cafeteria' => ['nullable', 'boolean'],
        ]);

        $usuario = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'area' => $validated['area'],
            'es_admin' => $request->boolean('es_admin'),
            'acceso_reportes' => $request->boolean('acceso_reportes'),
            'acceso_vinculacion' => $request->boolean('acceso_vinculacion'),
            'es_admin_cafeteria' => $request->boolean('es_admin_cafeteria'),
        ]);

        Bitacora::registrar('usuario.crear', "Creó al usuario {$usuario->name} ({$usuario->email}), área {$usuario->area}.");

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
            'area' => ['required', 'in:hospital,consultorios,cafeteria,vinculacion'],
            'es_admin' => ['nullable', 'boolean'],
            'acceso_reportes' => ['nullable', 'boolean'],
            'acceso_vinculacion' => ['nullable', 'boolean'],
            'es_admin_cafeteria' => ['nullable', 'boolean'],
        ]);

        $usuario->name = $validated['name'];
        $usuario->email = $validated['email'];
        $usuario->area = $validated['area'];
        $usuario->es_admin = $request->boolean('es_admin');
        $usuario->acceso_reportes = $request->boolean('acceso_reportes');
        $usuario->acceso_vinculacion = $request->boolean('acceso_vinculacion');
        $usuario->es_admin_cafeteria = $request->boolean('es_admin_cafeteria');

        if (! empty($validated['password'])) {
            $usuario->password = $validated['password'];
        }

        $usuario->save();

        Bitacora::registrar('usuario.actualizar', "Actualizó al usuario {$usuario->name} ({$usuario->email}).");

        return redirect('/usuarios')->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        if ($id === Auth::id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $usuario = User::findOrFail($id);
        $usuario->delete();

        Bitacora::registrar('usuario.eliminar', "Eliminó al usuario {$usuario->name} ({$usuario->email}).");

        return redirect('/usuarios')->with('status', 'Usuario eliminado correctamente.');
    }
}
