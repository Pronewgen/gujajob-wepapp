<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="page">
        <section class="hero">
            <p class="badge">Laravel + PHP + CSS + JavaScript</p>
            <h1>{{ $appName }}</h1>
            <p class="subtitle">
                Prototype version: ตอนนี้ใช้ข้อมูลแบบ hard code ก่อน ยังไม่เชื่อมต่อ Oracle Database
            </p>
        </section>

        <section class="cards">
            @foreach ($stats as $item)
                <div class="card">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                </div>
            @endforeach
        </section>
    </main>
</body>
</html>
