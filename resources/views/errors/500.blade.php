{{-- Standalone page (no layout) so it still works if the database is down --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something went wrong · PaxiliArt</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="container container-md page text-center">
    <img src="{{ asset('images/logo.png') }}" alt="PaxiliArt" style="height:50px;margin:0 auto 20px">
    <h1 style="font-size:5rem;color:var(--orange);margin:0">500</h1>
    <h2>Something went wrong</h2>
    <p class="muted">An unexpected error happened. Please try again later.</p>
    <a href="{{ url('/') }}" class="btn">Back to home</a>
</div>
</body>
</html>
