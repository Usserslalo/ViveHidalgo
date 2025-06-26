<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Vive Hidalgo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-image: url('/storage/img/login-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .bg-overlay {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(2px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="w-full min-h-screen flex items-center justify-center">
        <div class="bg-overlay rounded-2xl shadow-2xl p-8 sm:p-12 max-w-md w-full mx-4">
            <div class="flex flex-col items-center mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="#FF6B00"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l2.09 6.26L20 9.27l-5 3.64L16.18 20 12 16.77 7.82 20 9 12.91l-5-3.64 5.91-.91L12 2z" /></svg>
                <h1 class="text-3xl font-extrabold text-[#FF6B00] tracking-wide mb-1">Vive Hidalgo</h1>
                <span class="text-lg text-gray-700 font-medium">Bienvenido de nuevo</span>
            </div>
            @if ($errors->any())
                <div class="mb-4 text-red-600 text-center">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                <div>
                    <label for="email" class="block text-gray-700 font-semibold mb-1">Correo electrónico</label>
                    <input id="email" type="email" name="email" required autofocus placeholder="ejemplo@correo.com"
                        class="w-full border border-orange-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FF6B00] bg-white/90 text-gray-900 placeholder-gray-400 transition" />
                </div>
                <div>
                    <label for="password" class="block text-gray-700 font-semibold mb-1">Contraseña</label>
                    <input id="password" type="password" name="password" required placeholder="Tu contraseña"
                        class="w-full border border-orange-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FF6B00] bg-white/90 text-gray-900 placeholder-gray-400 transition" />
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" type="checkbox" name="remember" class="mr-2 accent-[#FF6B00]">
                        <label for="remember" class="text-sm text-gray-700">Recordarme</label>
                    </div>
                    <a href="#" class="text-sm text-[#FF6B00] hover:underline font-medium">¿Olvidaste tu contraseña?</a>
                </div>
                <button type="submit"
                    class="w-full bg-[#FF6B00] hover:bg-orange-700 text-white font-bold py-2.5 rounded-lg shadow transition-all duration-200 transform hover:-translate-y-0.5 hover:shadow-lg text-lg tracking-wide">
                    Iniciar Sesión
                </button>
            </form>
            <div class="mt-8 text-center">
                <span class="text-gray-600">¿No tienes cuenta?</span>
                <a href="#" class="text-[#FF6B00] font-semibold hover:underline ml-1">Regístrate</a>
            </div>
        </div>
    </div>
</body>
</html> 