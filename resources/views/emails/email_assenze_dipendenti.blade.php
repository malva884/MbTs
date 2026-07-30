<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Giornaliero Assenze Dipendenti</title>
    <style>
        body{margin:0;padding:0;background:#f4f4f8;font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#333}
        .wrap{width:100%;max-width:720px;background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)}
        .header{background:#6c2bd9;padding:16px 24px}
        .logo{height:28px}
        .content{padding:20px 24px}
        h1{margin:0 0 6px;font-size:20px;color:#1a1a2e}
        .meta{color:#8b8b9e;font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin:0 0 12px}
        .summary{background:#faf9fc;border:1px solid #ece9f5;border-radius:6px;padding:12px 16px;margin-bottom:16px}
        .summary td{padding:3px 0;font-size:13px}
        .summary .l{color:#8b8b9e;width:150px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:5px 6px;text-align:left;font-size:11px;border-bottom:1px solid #e4e4ec;vertical-align:top}
        th{background:#6c2bd9;color:#fff;font-weight:600}
        tr:nth-child(even){background:#faf9fc}
        .num{text-align:right;white-space:nowrap}
        .doc{font-weight:700;color:#1a1a2e}
        .small{font-size:10px;color:#666;line-height:1.3}
        .red{color:#e74c3c;font-weight:600}
        .green{color:#27ae60;font-weight:600}
        .footer{background:#faf9fc;padding:14px 24px;border-top:1px solid #ece9f5;font-size:11px;color:#8b8b9e}
    </style>
</head>
<body>
<center>
    <table class="wrap">
        <tr><td class="header"><img src="https://www.metallurgicabresciana.it/assets/img/logo18.png" class="logo"></td></tr>
        <tr><td class="content">
                <p class="meta">Risorse Umane</p>
                <h1>Report Giornaliero Assenze Dipendenti</h1>
                <p style="margin:0 0 16px;color:#555;font-size:13px">Report delle assenze dipendenti per la data odierna.</p>
                <div class="summary">
                    <table><tr><td class="l">Data</td><td>{{ date('d-m-Y') }}</td></tr>
                        <tr><td class="l">Tipo report</td><td>Assenze giornaliere</td></tr></table>
                </div>
                <p style="margin:0;font-size:13px;color:#555">Si allega il file Excel con l'elenco completo dei dipendenti assenti oggi, comprensivo dei dettagli delle tipologie di assenza (Ferie, Permesso, Malattia, Assenza, 104).</p>
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
