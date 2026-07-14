<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>agriAid</title>

    <link rel="icon" type="image/png" href="{{ asset('agriAid-logo.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-black text-white min-h-screen">

    <div class="flex flex-col items-center justify-center min-h-screen">

        <img
            src="{{ asset('agriAid-logo.png') }}"
            alt="agriAid Logo"
            class="w-48 mb-8">

        <h1 class="text-6xl font-bold text-green-500">
            agriAid
        </h1>

        <p class="mt-4 text-gray-300 text-xl">
            Agricultural Visibility, Credibility, Warehouse, Receipt & Financing Platform
        </p>

        <div class="mt-10">
            <span
                class="bg-green-600 px-6 py-3 rounded-full text-white">
                Laravel DDD Backend Running Successfully
            </span>
        </div>

    </div>

</body>

</html>