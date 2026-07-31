<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Presenze Mensili</title>
    <style>
        body{margin:0;padding:0;background:#f4f4f8;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#333}
        .wrap{width:100%;max-width:720px;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)}
        .header{background:#6c2bd9;padding:16px 24px}
        .logo{height:28px}
        .content{padding:20px 24px}
        h1{margin:0 0 6px;font-size:20px;color:#1a1a2e}
        .meta{color:#8b8b9e;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin:0 0 12px}
        .footer{background:#faf9fc;padding:14px 24px;border-top:1px solid #ece9f5;font-size:11px;color:#8b8b9e}
    </style>
</head>
<body>
<center>
    <table class="wrap">
        <tr><td class="header"><img src="https://www.metallurgicabresciana.it/assets/img/logo18.png" class="logo"></td></tr>
        <tr><td class="content">
                <p class="meta">Risorse Umane</p>
                <h1>Report Presenze Mensili</h1>
                <p style="margin:0 0 16px;color:#555;font-size:13px">
                    In allegato il report delle presenze del mese di <strong>{{ $meseDescrizione }}</strong>.
                </p>
                <p style="margin:0 0 8px;font-size:13px;color:#555">
                    Il report contiene due fogli:
                </p>
                <ul style="margin:0 0 16px;padding-left:20px;font-size:13px;color:#555">
                    <li><strong>Ore Interni</strong>: dettaglio delle ore per dipendente, suddiviso per causale (straordinari, ferie, malattia, infortuni, maternità, permessi, ecc.)</li>
                    <li><strong>Esterni Premio Presenze</strong>: report annuale del Premio Presenza per i dipendenti dell'azienda 0000000999 (gennaio-dicembre)</li>
                </ul>
            </td></tr>
        <tr><td class="footer">
                <strong>Metallurgica Bresciana S.p.A.</strong><br>
                Viale G. Marconi, 1 · 25020 Dello (BS)<br>
                <a href="https://www.metallurgicabresciana.it" style="color:#6c2bd9;text-decoration:none">www.metallurgicabresciana.it</a><br>
                Comunicazione automatica · Non rispondere a questa email
            </td></tr>
    </table>
</center>
</body>
</html>
