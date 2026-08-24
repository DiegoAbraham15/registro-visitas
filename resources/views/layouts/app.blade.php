<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Visitas</title>
    @vite('resources/css/app.css')
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <style>
        select { color-scheme: dark; }
        select option { background-color: #0b1531; color: #ffffff; }
    </style>
</head>
<body class="min-h-screen bg-[#031e5d] text-slate-100">
    @php
        $navItems = [
            ['href' => '/dashboard_area', 'label' => 'Dashboard', 'icon' => 'grid'],
        ];
        if (auth()->user()?->acceso_reportes) {
            $navItems[] = ['href' => '/reportes-graficos', 'label' => 'Reportes Diarios', 'icon' => 'chart'];
        }
        if (auth()->user()?->area === 'vinculacion' || auth()->user()?->acceso_vinculacion || auth()->user()?->es_admin) {
            $navItems[] = ['href' => '/vinculacion/dashboard', 'label' => 'Vinculación', 'icon' => 'clipboard'];
            $navItems[] = ['href' => '/vinculacion/menus', 'label' => 'Menú semanal', 'icon' => 'menu'];
        }
        if (auth()->user()?->es_admin_cafeteria || auth()->user()?->es_admin) {
            $navItems[] = ['href' => '/cafeteria/resumen', 'label' => 'Resumen Cafetería', 'icon' => 'chart'];
        }
        if (auth()->user()?->es_admin) {
            $navItems[] = ['href' => '/bitacora', 'label' => 'Bitácora', 'icon' => 'clipboard'];
            $navItems[] = ['href' => '/usuarios', 'label' => 'Usuarios', 'icon' => 'users'];
        }
        if (auth()->user()?->es_admin || auth()->user()?->acceso_catalogos) {
            $navItems[] = ['href' => '/catalogos', 'label' => 'Habitaciones y Áreas', 'icon' => 'building'];
        }
        if (auth()->user()?->es_admin || auth()->user()?->acceso_medicos) {
            $navItems[] = ['href' => '/medicos', 'label' => 'Médicos', 'icon' => 'medico'];
        }
        $icons = [
            'grid' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6v6h-6v-6Zm10.5 0h6v6h-6v-6Zm-10.5 10.5h6v6h-6v-6Zm10.5 0h6v6h-6v-6Z" />',
            'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M8.25 17.25v-6M13.5 17.25V6.75M18.75 17.25v-10.5" />',
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
            'menu' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />',
            'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H6a2.25 2.25 0 0 1-2.25-2.25V6a2.25 2.25 0 0 1 2.25-2.25h3.879a1.5 1.5 0 0 1 1.06.44l1.121 1.12a1.5 1.5 0 0 0 1.06.44H18a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25Z" />',
            'building' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h9a.75.75 0 0 1 .75.75V21H4.5V3.75A.75.75 0 0 1 5.25 3ZM15 10.5h3.75a.75.75 0 0 1 .75.75V21h-4.5v-10.5ZM7.5 6.75h1.5m-1.5 3.75h1.5m-1.5 3.75h1.5m3-7.5h1.5m-1.5 3.75h1.5m-1.5 3.75h1.5" />',
            'medico' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
        ];
        $currentPath = '/' . ltrim(request()->path(), '/');
    @endphp

    <div class="min-h-screen flex flex-col">
        <header class="sticky top-0 z-30 border-b border-white/10 bg-[#081536]/95 backdrop-blur shadow-[0_0_35px_rgba(0,0,0,0.25)]">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 items-center rounded-2xl bg-white px-3 shadow-lg shadow-black/20 ring-1 ring-white/10">
                        <img src="{{ asset('images/logo-medica-mia.png') }}" alt="Médica MIA" class="h-6 w-auto">
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400">Médica MIA</p>
                        <h1 class="text-lg font-bold text-white">Sistema de Registro de Visitas</h1>
                    </div>
                </div>
                <div class="hidden md:flex items-center gap-4 text-sm text-slate-300">
                    <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 py-1.5 pl-1.5 pr-4">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#4978eb]/20 text-xs font-bold uppercase text-[#9db6f7]">
                            {{ Str::substr(auth()->user()->nombre ?? 'Admin', 0, 1) }}
                        </span>
                        <span class="font-medium text-white">{{ auth()->user()->nombre ?? 'Admin' }}</span>
                    </div>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.3em]">Área asignada</span>
                </div>
            </div>

            <nav class="flex gap-2 overflow-x-auto border-t border-white/10 px-4 py-2 sm:px-6 lg:hidden">
                @foreach ($navItems as $item)
                    @php $isActive = $currentPath === $item['href']; @endphp
                    <a href="{{ $item['href'] }}"
                       class="flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold whitespace-nowrap transition
                              {{ $isActive ? 'bg-[#4978eb] text-white shadow-md shadow-[#4978eb]/30' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            {!! $icons[$item['icon']] !!}
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <aside class="hidden w-72 shrink-0 border-r border-white/10 bg-[#081536] px-5 py-6 lg:block">
                <div class="space-y-4">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Navegación</div>
                    <nav class="space-y-2">
                        @foreach ($navItems as $item)
                            @php $isActive = $currentPath === $item['href']; @endphp
                            <a href="{{ $item['href'] }}"
                               class="flex items-center gap-3 rounded-2xl border px-4 py-3 text-sm font-semibold shadow-sm transition
                                      {{ $isActive
                                            ? 'border-[#4978eb]/60 bg-[#4978eb]/15 text-white shadow-[#4978eb]/10'
                                            : 'border-white/10 bg-white/5 text-slate-300 hover:border-[#4978eb]/50 hover:bg-[#4978eb]/10 hover:text-white' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                     class="h-5 w-5 {{ $isActive ? 'text-[#9db6f7]' : 'text-slate-500' }}" stroke="currentColor">
                                    {!! $icons[$item['icon']] !!}
                                </svg>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <main class="flex-1 overflow-y-auto bg-[#06173f] px-4 py-6 sm:px-6">
                <div class="mx-auto w-full max-w-7xl">
                    @yield('content')
                </div>
                
            </main>
        </div>
    </div>
</body>
</html>
