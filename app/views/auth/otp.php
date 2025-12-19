<!-- otp-email-template.html -->
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width" />
  <title>Kode OTP</title>
  <style>
    body {
      background: white;
    }
  </style>
</head>

<body>
  <table
    cellpadding="0"
    cellspacing="0"
    role="presentation"
    style="
        background: white;
        color: #4b4b4b;
        text-align: center;
        width: 100%;
        font-size: 0.9em;
      "
    width="100%"
    align="center">
    <tbody>
      <tr>
        <td style="font-weight: 500; padding: 10px">
          <h2 style="color: #005085">CBN-HUB</h2>
        </td>
      </tr>
      <tr>
        <td style="font-weight: 500; padding: 10px">
          Hai <?= $name; ?>
        </td>
      </tr>
      <tr>
        <td style="font-weight: 500; padding: 10px">
          Terima kasih telah mendaftar di <strong>CBNLink</strong>.<br />
          Berikut adalah kode verifikasi (OTP) API Anda untuk menyelesaikan
          proses verifikasi akun
        </td>
      </tr>
      <tr>
        <td style="font-weight: 500; padding: 10px">
          <div
            style="
                font-size: 2.2em;
                font-weight: 700;
                letter-spacing: 10px;
                color: #005085;
              ">
            <?= $otp_code; ?>
          </div>
        </td>
      </tr>
      <tr>
        <td style="font-weight: 500; padding: 10px">
          Kode ini hanya berlaku selama <strong>15 menit</strong>.<br />
          Jangan bagikan kode ini kepada siapapun untuk alasan keamanan.
        </td>
      </tr>
      <tr>
        <td style="font-weight: 500; padding: 10px">
          Jika Anda tidak melakukan pendaftaran ini, abaikan email ini.<br />
          Butuh bantuan? Hubungi kami di
          <a
            href="#m_-6562564654925948633_"
            style="color: orangered; text-decoration: none; font-weight: 650">+6282279905665</a>
        </td>
      </tr>
      <tr>
        <td style="font-weight: 500; padding: 10px" align="center">
          <div
            style="
                border-top: 1px solid #0000002f;
                border-bottom: 1px solid #0000002f;
                padding: 15px;
                width: 80%;
              ">
            <a
              href="#m_-6562564654925948633_"
              style="
                  display: inline-block;
                  color: orangered;
                  text-decoration: none;
                  margin: 5px;
                "><img
                src="https://ci3.googleusercontent.com/meips/ADKq_NbigaawrPZbv20r48DB6zUvxwVw6JdR7xfIUvUmlwYvFyycxUId8QddzSobPeSn5raj5C1JOxkLnxS1Bi-Yf4YEOp1NXR4RjVP9wfZLQZs=s0-d-e1-ft#https://f.shopee.sg/file/3814109aa6a3721b577919d5c8a36cfe"
                style="height: 40px; width: 40px" /></a>
            <a
              href="#m_-6562564654925948633_"
              style="
                  display: inline-block;
                  color: orangered;
                  text-decoration: none;
                  margin: 5px;
                "><img
                src="https://ci3.googleusercontent.com/meips/ADKq_NbhLO4xZqadIyff6oMoGIsIx_rMZAnbUZFEjvR4s1PuKqRoNk6AnA0PwKq0U-AawuE5VEMf2BwPOkmsTtHW39M3NacYvpQakylcPhwJnGE=s0-d-e1-ft#https://f.shopee.sg/file/3b08ded58cbbb9beb74ec4d5ad4c7d53"
                style="height: 40px; width: 40px" /></a>
            <a
              href="#m_-6562564654925948633_"
              style="
                  display: inline-block;
                  color: orangered;
                  text-decoration: none;
                  margin: 5px;
                "><img
                src="https://ci3.googleusercontent.com/meips/ADKq_Nb47I3RBNsNq986mS-mon1E5ANa9NxphRZGGnEjVM0HRkCN-1wcSLL5oQclu6E0p1ti_2IsEEJes6pe--rI62qlIu8N36QtjWnO0-JTr8w=s0-d-e1-ft#https://f.shopee.sg/file/e7ab71dae1223ca9eb4b9f120353a31e"
                style="height: 40px; width: 40px" /></a>
          </div>
        </td>
      </tr>
      <tr>
        <td style="font-weight: 500; padding: 10px; font-size: 0.85em">
          © 2025 PT.SUN Solutions Corp. — Semua hak dilindungi.<br />
          Email ini dikirim secara otomatis, mohon tidak membalas.
          <span style="display: block; padding: 10px">
            Jl. Raya Bratasena No.1 Dusun 3 Cabang Bandar Surabaya Lampung
            Tengah, 341559
          </span>
        </td>
      </tr>
    </tbody>
  </table>
</body>

</html>