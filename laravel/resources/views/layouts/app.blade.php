<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MelioraWeb</title>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
<main class="container mx-auto p-6">
    @yield('content')
</main>

@livewireScripts
</body>
</html>
