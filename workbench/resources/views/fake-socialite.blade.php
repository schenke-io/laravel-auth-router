<!doctype html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Fake Socialite Login</title>
    @include('auth-router::helper.style')
</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen">
    <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <h1 class="text-2xl font-bold mb-4 dark:text-white">Fake {{ ucfirst($driver) }} Login</h1>
        <p class="mb-6 text-gray-600 dark:text-gray-400">This is a fake login page for demonstration purposes.</p>
        <a href="{{ route('fake-socialite-callback', ['driver' => $driver]) }}" 
           class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-200">
            Login as Fake User
        </a>
    </div>
</body>
</html>
