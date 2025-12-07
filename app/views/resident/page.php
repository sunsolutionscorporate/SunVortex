<!doctype html>
<html lang="en">

<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width,initial-scale=1" />
   <title>Residents Data — Modern UI</title>
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
         --radius: 12px;
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
         padding: 24px;
         display: grid;
         place-items: center;
      }

      .table-wrap {
         width: 100%;
         max-width: 960px;
         background: var(--bg-soft);
         border-radius: var(--radius);
         box-shadow: var(--shadow);
         overflow-x: auto;
      }

      table {
         width: 100%;
         border-collapse: collapse;
         font-size: 15px;
      }

      thead {
         background: var(--accent);
         color: white;
      }

      th,
      td {
         padding: 12px 16px;
         text-align: left;
         border-bottom: 1px solid var(--border);
      }

      tbody tr:hover {
         background: rgba(110, 168, 254, .08);
      }

      .actions {
         display: flex;
         gap: 8px;
      }

      .btn {
         padding: 6px 10px;
         border-radius: 8px;
         border: none;
         cursor: pointer;
         font-size: 13px;
         font-weight: 600;
         transition: background .2s ease;
      }

      .btn.view {
         background: var(--accent);
         color: white
      }

      .btn.edit {
         background: var(--accent-2);
         color: black
      }

      .btn.delete {
         background: #ff6b6b;
         color: white
      }

      .btn:hover {
         opacity: .85
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
      <h1>Residents Data</h1>
   </header>
   <main>
      <div class="table-wrap">
         <table>
            <thead>
               <tr>
                  <th>ID</th>
                  <th>Full Name</th>
                  <th>Address</th>
                  <th>Age</th>
                  <th>Actions</th>
               </tr>
            </thead>
            <tbody>
               <?php
               foreach ($data as $key => $value) { ?>
                  <tr>
                     <td><?= $key ?></td>
                     <td><?= $value['name'] ?></td>
                     <td><?= $value['nik'] ?></td>
                     <td><?= $value['placebirth'] ?></td>
                     <td class="actions">
                        <a class="btn view">View</a>
                        <a href="<?= base_url('residents/form/' . $value['id']); ?>" class="btn edit">Edit</a>
                        <a href="<?= base_url('residents/delete/' . $value['id']); ?>" class="btn delete">Delete</a>
                     </td>
                  </tr>
               <?php }; ?>
            </tbody>
         </table>
      </div>
   </main>
   <footer>
      &copy; 2025 Residents Management System
   </footer>
</body>

</html>