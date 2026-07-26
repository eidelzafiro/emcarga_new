<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex">
        <aside
            id="sidebar"
            class="fixed top-0 left-0 z-30 h-full bg-white border-r border-gray-200 shadow-sm flex flex-col sidebar-transition"
            style="width: 16rem;"
        >
            <div class="flex items-center h-16 px-4 border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-sm">
                        E
                    </div>
                    <span class="font-semibold text-gray-900">EMCARGA</span>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                @foreach ($menu ?? [] as $item)
                    @if (isset($item['children']) && count($item['children']))
                        <div class="space-y-1">
                            <span class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-400 uppercase tracking-wider">
                                {{ $item['label'] }}
                            </span>
                            @foreach ($item['children'] as $child)
                                <a
                                    href="{{ $child['url'] ?? '#' }}"
                                    class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors
                                        {{ request()->is(ltrim($child['url'] ?? '', '/')) ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                                >
                                    @if ($child['icono'] ?? false)
                                        <i class="{{ $child['icono'] }} text-lg w-5 text-center"></i>
                                    @endif
                                    <span>{{ $child['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a
                            href="{{ $item['url'] ?? '#' }}"
                            class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors
                                {{ request()->is(ltrim($item['url'] ?? '', '/')) ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                        >
                            @if ($item['icono'] ?? false)
                                <i class="{{ $item['icono'] }} text-lg w-5 text-center"></i>
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="flex items-center justify-center h-12 border-t border-gray-100 shrink-0">
                <button
                    onclick="document.getElementById('sidebar').classList.toggle('collapsed')"
                    class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                >
                    <i class="pi pi-chevron-left text-sm"></i>
                </button>
            </div>
        </aside>

        <div class="flex flex-col flex-1 lg:ml-64 min-h-screen">
            <header class="sticky top-0 z-10 bg-white/95 backdrop-blur-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-4 lg:px-6">
                    <div class="flex items-center gap-3">
                        <button
                            onclick="document.getElementById('sidebar').classList.toggle('collapsed')"
                            class="p-1.5 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 lg:hidden"
                        >
                            <i class="pi pi-bars text-lg"></i>
                        </button>
                        <h1 class="text-lg font-semibold text-gray-800">
                            @yield('title', 'Dashboard')
                        </h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-700 font-medium">{{ auth()->user()->name ?? 'Usuario' }}</span>
                        <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center text-white text-xs font-bold">
                            {{ collect(explode(' ', auth()->user()->name ?? 'U'))->map(fn($w) => $w[0])->take(2)->join('') }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-6">
                @yield('content')
            </main>

            <footer class="px-4 lg:px-6 py-3 text-center text-xs text-gray-400 border-t border-gray-100">
                EMCARGA &copy; {{ date('Y') }} — Sistema de Gestión Empresarial
            </footer>
        </div>
    </div>

    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
