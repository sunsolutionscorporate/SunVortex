<!doctype html>
<html lang="en">

<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width,initial-scale=1" />
   <meta name="color-scheme" content="light dark" />
   <title>Terjadi kesalahan — Mohon maaf</title>
   <style>
      :root {
         --bg: #0b1020;
         --bg-soft: #0f162e;
         --text: #e6ebff;
         --muted: #9aa6ff;
         --accent: #6ea8fe;
         --accent-2: #7cf3c6;
         --border: #1f2a4c;
         --danger: #ff6b6b;
         --shadow: 0 20px 60px rgba(0, 0, 0, .35);
         --radius: 16px;
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
            --danger: #d7263d;
            --shadow: 0 12px 40px rgba(16, 24, 40, .12);
         }
      }

      * {
         box-sizing: border-box
      }

      html,
      body {
         height: 100%
      }

      body {
         margin: 0;
         font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, "Helvetica Neue", Arial, "Apple Color Emoji", "Segoe UI Emoji";
         background:
            radial-gradient(1200px 600px at 20% 0%, rgba(110, 168, 254, .25), transparent 60%),
            radial-gradient(1000px 500px at 80% 100%, rgba(124, 243, 198, .2), transparent 60%),
            var(--bg);
         color: var(--text);
         line-height: 1.6;
         display: grid;
         place-items: center;
         padding: 24px;
      }

      .wrap {
         width: 100%;
         max-width: 960px;
         display: grid;
         gap: 16px;
      }

      .card {
         background: linear-gradient(180deg, rgba(255, 255, 255, .04), rgba(255, 255, 255, .02));
         backdrop-filter: blur(12px);
         border: 1px solid var(--border);
         border-radius: var(--radius);
         box-shadow: var(--shadow);
         padding: 28px;
         display: grid;
         grid-template-columns: 140px 1fr;
         gap: 24px;
      }

      @media (max-width: 720px) {
         .card {
            grid-template-columns: 1fr;
            padding: 22px
         }
      }

      .art {
         position: relative;
         align-self: center;
         justify-self: center;
         width: 140px;
         height: 140px;
      }

      .art svg {
         width: 100%;
         height: 100%
      }

      .pulse {
         position: absolute;
         inset: 0;
         border-radius: 50%;
         animation: pulse 2.8s ease-in-out infinite;
         background: radial-gradient(closest-side, rgba(110, 168, 254, .18), transparent);
         filter: blur(8px);
      }

      @keyframes pulse {
         0% {
            transform: scale(.95);
            opacity: .8
         }

         50% {
            transform: scale(1.05);
            opacity: .4
         }

         100% {
            transform: scale(.95);
            opacity: .8
         }
      }

      .title {
         margin: 0 0 8px;
         font-size: clamp(22px, 3.5vw, 28px);
         letter-spacing: -.02em;
      }

      .lead {
         margin: 0 0 14px;
         color: var(--muted)
      }

      .code-badge {
         display: inline-flex;
         align-items: center;
         gap: 8px;
         padding: 8px 12px;
         border-radius: 999px;
         border: 1px dashed var(--border);
         background: rgba(255, 255, 255, .04);
         font-weight: 600;
         color: var(--accent);
      }

      .details {
         margin-top: 16px;
         display: grid;
         gap: 8px;
      }

      .detail-row {
         display: flex;
         gap: 8px;
         align-items: center;
         flex-wrap: wrap;
         font-size: 14px;
         color: var(--muted);
      }

      .detail-label {
         font-weight: 600;
         color: var(--text)
      }

      .actions {
         display: flex;
         gap: 12px;
         flex-wrap: wrap;
         margin-top: 18px;
      }

      .btn {
         appearance: none;
         border: 1px solid var(--border);
         padding: 10px 14px;
         border-radius: 12px;
         background: var(--bg-soft);
         color: var(--text);
         cursor: pointer;
         font-weight: 600;
         transition: transform .08s ease, border-color .2s ease, box-shadow .2s ease;
      }

      .btn:hover {
         transform: translateY(-1px);
         border-color: var(--accent)
      }

      .btn.primary {
         background: linear-gradient(180deg, var(--accent), color-mix(in oklab, var(--accent) 70%, black 30%));
         color: white;
         border: none;
         box-shadow: 0 8px 24px color-mix(in oklab, var(--accent) 35%, black 65%);
      }

      .btn.ghost {
         background: transparent
      }

      .support {
         margin-top: 6px;
         font-size: 13px;
         color: var(--muted);
      }

      .footer {
         text-align: center;
         font-size: 12px;
         color: var(--muted);
      }

      .sr-only {
         position: absolute;
         width: 1px;
         height: 1px;
         padding: 0;
         margin: -1px;
         overflow: hidden;
         clip: rect(0, 0, 0, 0);
         white-space: nowrap;
         border: 0;
      }

      /* Optional: toast */
      .toast {
         position: fixed;
         bottom: 24px;
         left: 50%;
         transform: translateX(-50%);
         background: var(--bg-soft);
         border: 1px solid var(--border);
         border-radius: 12px;
         padding: 10px 14px;
         color: var(--text);
         box-shadow: var(--shadow);
         opacity: 0;
         pointer-events: none;
         transition: opacity .2s ease, transform .2s ease;
      }

      .toast.show {
         opacity: 1;
         transform: translateX(-50%) translateY(-4px)
      }
   </style>
</head>

<body>
   <main class="wrap" role="main" aria-labelledby="page-title">
      <div class="card" role="group" aria-label="Pesan kesalahan">
         <div class="art" aria-hidden="true">
            <div class="pulse"></div>
            <!-- Minimal SVG illustration -->
            <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
               <defs>
                  <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                     <stop offset="0" stop-color="#6ea8fe" />
                     <stop offset="1" stop-color="#7cf3c6" />
                  </linearGradient>
               </defs>
               <circle cx="60" cy="60" r="42" fill="url(#g1)" opacity=".15" />
               <path d="M60 35 L85 90 H35 Z" fill="url(#g1)" opacity=".35" />
               <circle cx="60" cy="60" r="24" fill="none" stroke="url(#g1)" stroke-width="3" />
               <path d="M52 52 L68 68 M68 52 L52 68" stroke="#ff6b6b" stroke-width="3" stroke-linecap="round" />
            </svg>
         </div>

         <section>
            <h1 id="page-title" class="title">An error occurred in the application</h1>
            <p class="lead">Maaf atas ketidaknyamanannya. Kami sudah mencatatnya dan sedang menyiapkan perbaikannya.</p>

            <div class="code-badge" aria-live="polite">
               <span class="sr-only">Kode error:</span>
               <span id="error-code">ERR-<?= $code; ?></span>
               <span aria-hidden="true">•</span>
               <span id="error-title"><?= $status; ?></span>
            </div>

            <div class="details" id="error-details" aria-describedby="detail-help">
               <div class="detail-row">
                  <span class="detail-label">Waktu:</span>
                  <span id="error-time">—</span>
               </div>
               <div class="detail-row">
                  <span class="detail-label">ID insiden:</span>
                  <span id="incident-id">—</span>
               </div>
               <div class="detail-row">
                  <span class="detail-label">Rincian:</span>
                  <span id="error-message"><?= $message; ?></span>
               </div>
            </div>
            <p id="detail-help" class="sr-only">Rincian ini membantu tim dukungan menyelidiki masalah.</p>

            <div class="actions">
               <button class="btn primary" id="retry">Coba lagi</button>
               <button class="btn" id="home">Kembali ke beranda</button>
               <button class="btn ghost" id="copy">Salin rincian</button>
               <a class="btn ghost" id="report" href="#" rel="noopener">Laporkan</a>
            </div>
            <p class="support">Butuh bantuan? Kirimkan ID insiden saat menghubungi dukungan.</p>
         </section>
      </div>

      <p class="footer">Jika masalah berlanjut, silakan coba bersihkan cache atau gunakan koneksi internet lain.</p>
   </main>

   <div class="toast" id="toast" role="status" aria-live="polite">Rincian disalin</div>

   <script>
      // Minimal runtime for UX
      (function() {
         const now = new Date();
         const timeEl = document.getElementById('error-time');
         const idEl = document.getElementById('incident-id');
         const msgEl = document.getElementById('error-message');
         const copyBtn = document.getElementById('copy');
         const retryBtn = document.getElementById('retry');
         const homeBtn = document.getElementById('home');
         const reportLink = document.getElementById('report');
         const toast = document.getElementById('toast');

         // Populate basics
         timeEl.textContent = now.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
         });

         // Generate a short incident ID (replace with server-provided ID when available)
         // const incidentId = 'INC-' + Math.random().toString(36).slice(2, 8).toUpperCase();
         const incidentId = "<?= $request_id; ?>";
         idEl.textContent = incidentId;

         // Wire actions
         retryBtn.addEventListener('click', () => {
            // Prefer a precise retry route if you have it, else reload
            location.reload();
         });

         homeBtn.addEventListener('click', () => {
            // Replace with your app's home route
            history.length > 1 ? history.back() : (location.href = '/');
         });

         copyBtn.addEventListener('click', async () => {
            const payload = {
               code: document.getElementById('error-code')?.textContent || 'UNKNOWN',
               title: document.getElementById('error-title')?.textContent || '',
               time: timeEl.textContent,
               incidentId,
               message: msgEl.textContent,
               url: location.href,
               ua: navigator.userAgent
            };
            try {
               await navigator.clipboard.writeText(JSON.stringify(payload, null, 2));
               toast.classList.add('show');
               setTimeout(() => toast.classList.remove('show'), 1600);
            } catch (e) {
               alert('Gagal menyalin rincian. Silakan salin manual.');
            }
         });

         // Report channel (email, issue tracker)
         const subject = encodeURIComponent(`[${incidentId}] Laporan error`);
         const body = encodeURIComponent(
            `Halo tim,\n\nTerjadi error pada halaman:\nURL: ${location.href}\nWaktu: ${timeEl.textContent}\nID: ${incidentId}\n\nRingkasan:\n${msgEl.textContent}\n\nTerima kasih.`
         );
         reportLink.href = `mailto:support@example.com?subject=${subject}&body=${body}`;
      }());
   </script>
</body>

</html>