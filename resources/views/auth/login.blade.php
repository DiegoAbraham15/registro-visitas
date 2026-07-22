<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Accesos - Login</title>
    @vite('resources/css/app.css')
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</head>
<body class="min-h-screen bg-[#031e5d] text-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-6xl rounded-[2rem] overflow-hidden bg-white/5 backdrop-blur-xl border border-white/10 shadow-[0_40px_120px_rgba(0,0,0,0.35)] grid gap-6 lg:grid-cols-[1.2fr_0.9fr]">
            <section class="relative overflow-hidden bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.16),_transparent_35%),linear-gradient(180deg,_#081536_0%,_#0f2a62_100%)] p-10 text-white">
                <div class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.25),_transparent_25%),radial-gradient(circle_at_bottom_left,_rgba(255,255,255,0.12),_transparent_20%)]"></div>
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-3xl bg-white px-5 py-3 mb-8 shadow-lg shadow-black/20">
                            <img src="{{ asset('images/logo-medica-mia.png') }}" alt="Médica MIA" class="h-8 w-auto">
                        </div>
                        <h1 class="text-4xl sm:text-5xl font-black leading-tight mb-4">Control de Accesos Hospitalarios</h1>
                        <p class="max-w-xl text-sm text-slate-200/90 leading-relaxed">Bienvenido al sistema de registro de visitas. Accede con tus credenciales para administrar áreas, autorizaciones y seguimiento de visitas en Clínica Médica MIA.</p>
                    </div>

                </div>
            </section>

            <section class="bg-white p-8 sm:p-10 text-slate-900">
                <div class="mb-6">
                    <h2 class="mt-3 text-3xl font-black">Inicia sesión</h2>
                    <p class="mt-2 text-sm text-slate-500">Ingresa las credenciales de tu área asignada para continuar.</p>
                </div>

                @if($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="/login" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-2">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="ejemplo@test.com"
                               class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-900 shadow-sm transition focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-2">Contraseña</label>
                        <input type="password" name="password" required placeholder="••••••••"
                               class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-900 shadow-sm transition focus:border-[#4978eb] focus:outline-none focus:ring-2 focus:ring-[#4978eb]/20" />
                    </div>

                    <button type="submit" class="w-full rounded-3xl bg-[#031e5d] px-4 py-4 text-sm font-semibold uppercase tracking-[0.08em] text-white shadow-lg shadow-[#031e5d]/20 transition hover:bg-[#0f3562] active:translate-y-[1px]">Iniciar Sesión</button>
                </form>

                <div class="mt-8 border-t border-slate-200/70 pt-5 text-center text-sm text-slate-500">
                    Sistema de Registro de Visitas Hospitalarias
                </div>
            </section>
        </div>
    </div>

</body>
</html>