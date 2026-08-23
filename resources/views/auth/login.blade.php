<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>เข้าสู่ระบบ — ระบบบริหารจัดการคลังวัสดุและครุภัณฑ์</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2b5788 50%, #1a4a7a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-brand-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.15);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            backdrop-filter: blur(8px);
        }

        .login-brand-icon svg {
            width: 30px;
            height: 30px;
            stroke: #fff;
            fill: none;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .login-brand h1 {
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.4;
        }

        .login-brand p {
            color: rgba(255,255,255,0.65);
            font-size: 13px;
            margin-top: 4px;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 36px 32px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .login-card h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1e2d40;
            margin-bottom: 24px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            color: #b91c1c;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error svg {
            flex-shrink: 0;
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13.5px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 14.5px;
            font-family: inherit;
            color: #111827;
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }

        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }

        .form-group input.is-invalid {
            border-color: #f87171;
        }

        .btn-login {
            width: 100%;
            padding: 11px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.15s, transform 0.1s;
        }

        .btn-login:hover { background: #1d4ed8; }
        .btn-login:active { transform: scale(0.99); }
        .btn-login:disabled { background: #93c5fd; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-brand">
            <div class="login-brand-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                    <path d="M3.3 7 12 12l8.7-5"/>
                    <path d="M12 22V12"/>
                </svg>
            </div>
            <h1>ระบบบริหารจัดการ<br>คลังวัสดุและครุภัณฑ์</h1>
            <p>GUJAJOB WebApp</p>
        </div>

        <div class="login-card">
            <h2>เข้าสู่ระบบ</h2>

            @if ($errors->has('form'))
                <div class="alert-error">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    {{ $errors->first('form') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                @csrf
                <div class="form-group">
                    <label for="login_name">ชื่อผู้ใช้งาน</label>
                    <input
                        type="text"
                        id="login_name"
                        name="login_name"
                        value="{{ old('login_name') }}"
                        autocomplete="username"
                        autofocus
                        class="{{ $errors->has('login_name') || $errors->has('form') ? 'is-invalid' : '' }}"
                        placeholder="กรอกชื่อผู้ใช้งาน"
                    >
                </div>

                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        class="{{ $errors->has('form') ? 'is-invalid' : '' }}"
                        placeholder="กรอกรหัสผ่าน"
                    >
                </div>

                <button type="submit" class="btn-login" id="btnLogin">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            document.getElementById('btnLogin').disabled = true;
            document.getElementById('btnLogin').textContent = 'กำลังเข้าสู่ระบบ...';
        });
    </script>
</body>
</html>
