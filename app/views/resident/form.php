<!doctype html>
<html lang="en">

<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width,initial-scale=1" />
   <title>Edit Resident — Modern UI</title>
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
         background: var(--bg);
         color: var(--text);
         min-height: 100vh;
         display: flex;
         flex-direction: column;
      }

      header {
         padding: 20px;
         text-align: center;
         background: var(--bg-soft);
         border-bottom: 1px solid var(--border);
         box-shadow: var(--shadow);
      }

      header h1 {
         margin: 0;
         font-size: 1.8rem;
      }

      main {
         flex: 1;
         display: grid;
         place-items: center;
         padding: 24px;
      }

      form {
         background: var(--bg-soft);
         border: 1px solid var(--border);
         border-radius: var(--radius);
         box-shadow: var(--shadow);
         width: 100%;
         max-width: 520px;
         padding: 32px;
         display: grid;
         gap: 20px;
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
         display: flex;
         gap: 12px;
         justify-content: flex-end;
      }

      button {
         padding: 12px 18px;
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

      footer {
         text-align: center;
         padding: 16px;
         font-size: 13px;
         color: var(--muted);
      }
   </style>
</head>

<body>
   <header>
      <h1>Edit Resident Data</h1>
   </header>
   <main>
      <form action=<?= base_url("residents/update") ?> method="post">
         <div class="field">
            <label for="id">Resident ID</label>
            <input type="text" id="id" name="id" value=<?= $data['id']; ?> readonly />
         </div>
         <div class="field">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?= $data['name']; ?>" required />
         </div>
         <div class="field">
            <label for="address">NIK</label>
            <input type="text" id="nik" name="nik" value="<?= $data['nik']; ?>" required />
         </div>
         <div class="actions">
            <button type="submit" class="primary">Save Changes</button>
            <button type="button" class="secondary" onclick="history.back()">Cancel</button>
         </div>
      </form>
   </main>
   <footer>
      &copy; 2025 Residents Management System
   </footer>
</body>

</html>