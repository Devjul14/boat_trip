<!DOCTYPE html>
<html lang="en" class="bg-zinc-950 text-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Boat Trip</title>

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-zinc-900 p-6 rounded-2xl shadow-xl">
        <h2 class="text-center text-lg font-semibold mb-2">Boat Trip</h2>
        <h1 class="text-center text-2xl font-bold mb-6">Reset your password</h1>

        @if (session('status'))
            <div class="mb-4 text-green-500">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            {{-- Email --}}
<div>
    <label for="email" class="block text-sm mb-1">Email address</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
        class="w-full px-4 py-2 rounded-md bg-zinc-900 text-white border border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500">
    @error('email')
        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Password --}}
<div>
    <label for="password" class="block text-sm mb-1">Password</label>
    <div class="relative">
        <input id="password" type="password" name="password" required
            class="w-full px-4 py-2 rounded-md bg-zinc-900 text-white border border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500">
        <span toggle="#password" class="toggle-password absolute inset-y-0 right-3 flex items-center cursor-pointer text-gray-400">
            {{-- Icon mata --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </span>
    </div>
    @error('password')
        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

{{-- Confirm Password --}}
<div>
    <label for="password-confirm" class="block text-sm mb-1">Confirm password</label>
    <div class="relative">
        <input id="password-confirm" type="password" name="password_confirmation" required
            class="w-full px-4 py-2 rounded-md bg-zinc-900 text-white border border-yellow-500 focus:outline-none focus:ring-1 focus:ring-yellow-500">
        <span toggle="#password-confirm" class="toggle-password absolute inset-y-0 right-3 flex items-center cursor-pointer text-gray-400">
            {{-- Icon mata --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </span>
    </div>
</div>


            {{-- Submit --}}
            <div>
                <button type="submit"
                    class="w-full py-2 px-4 bg-yellow-500 hover:bg-yellow-600 rounded-md text-white font-semibold transition">
                    Reset password
                </button>
            </div>
        </form>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(el => {
            el.addEventListener('click', function () {
                const input = document.querySelector(this.getAttribute('toggle'));
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
            });
        });
    </script>
</body>
</html>
