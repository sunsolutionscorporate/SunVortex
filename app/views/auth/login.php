<!doctype html>
<html lang="en">

<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width,initial-scale=1" />
   <title>Login — Modern UI</title>
   <style>
      :root {
         --bg: #0b1020;
         --bg-soft: #121a33;
         --text: #e6ebff;
         --muted: #9aa6ff;
         --accent: #6ea8fe;
         --accent-2: #7cf3c6;
         --border: #1f2a4c;
         --shadow: 0 20px 60px rgba(0, 0, 0, .35);
         --radius: 14px;
      }

      @media (prefers-color-scheme: light) {
         :root {
            --bg: #f6f8ff;
            --bg-soft: #ffffff;
            --text: #0f1a2a;
            --muted: #5563a6;
            --accent: #1f6feb;
            --accent-2: #0bb37a;
            --border: #e7ecff;
            --shadow: 0 12px 40px rgba(16, 24, 40, .12);
         }
      }

      * {
         box-sizing: border-box
      }

      body {
         margin: 0;
         font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, sans-serif;
         background:
            radial-gradient(1200px 600px at 20% 0%, rgba(110, 168, 254, .25), transparent 60%),
            radial-gradient(1000px 500px at 80% 100%, rgba(124, 243, 198, .2), transparent 60%),
            var(--bg);
         color: var(--text);
         display: grid;
         place-items: center;
         min-height: 100vh;
         padding: 24px;
      }

      .card {
         background: var(--bg-soft);
         border: 1px solid var(--border);
         border-radius: var(--radius);
         box-shadow: var(--shadow);
         width: 100%;
         max-width: 420px;
         padding: 32px;
         display: grid;
         gap: 20px;
      }

      h1 {
         margin: 0;
         font-size: 1.6rem;
         text-align: center;
      }

      .field {
         display: grid;
         gap: 6px;
      }

      label {
         font-size: 14px;
         font-weight: 600;
         color: var(--muted);
      }

      input {
         padding: 12px;
         border-radius: 10px;
         border: 1px solid var(--border);
         background: transparent;
         color: var(--text);
         font-size: 15px;
         transition: border-color .2s ease;
      }

      input:focus {
         outline: none;
         border-color: var(--accent);
      }

      .actions {
         display: grid;
         gap: 12px;
      }

      button {
         padding: 12px;
         border-radius: 10px;
         border: none;
         font-weight: 600;
         cursor: pointer;
         transition: transform .1s ease, box-shadow .2s ease;
      }

      button.primary {
         background: linear-gradient(180deg, var(--accent), color-mix(in oklab, var(--accent) 70%, black 30%));
         color: white;
         box-shadow: 0 8px 24px color-mix(in oklab, var(--accent) 35%, black 65%);
      }

      button.primary:hover {
         transform: translateY(-1px)
      }

      button.secondary {
         background: transparent;
         border: 1px solid var(--border);
         color: var(--text);
      }

      .links {
         text-align: center;
         font-size: 14px;
         color: var(--muted);
      }

      .links a {
         color: var(--accent);
         text-decoration: none;
      }

      .links a:hover {
         text-decoration: underline
      }
   </style>
</head>

<body>
   <form class="card" action="/login" method="post">
      <h1>Welcome Back</h1>
      <div class="field">
         <label for="email">Email</label>
         <input type="email" id="email" name="email" required placeholder="you@example.com" />
      </div>
      <div class="field">
         <label for="password">Password</label>
         <input type="password" id="password" name="password" required placeholder="••••••••" />
      </div>
      <div class="actions">
         <button type="submit" class="primary">Login</button>
         <button type="button" class="secondary" onclick="alert('Sign up flow here')">Sign Up</button>
      </div>
      <div class="links">
         <p><a href="#">Forgot password?</a></p>

         <a href="<?= base_url('auth/login_google'); ?>" class="btn btn-danger">
            Login dengan Google
         </a>

      </div>
   </form>
</body>

</html>