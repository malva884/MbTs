<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifica Scadenza Formazioni</title>
    <style>
        /* Reset stili per compatibilità client email */
        body, table, td, a { text-decoration: none !important; }
        body { margin: 0; padding: 0; background-color: #f6f9fc; font-family: 'Segoe UI', Arial, sans-serif; }

        .main-container { width: 100%; max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        /* Header con identità aziendale */
        .header { background-color: #004a99; padding: 35px 40px; text-align: center; }
        .logo { width: 200px; height: auto; margin-bottom: 15px; }

        /* Contenuto */
        .content { padding: 40px; color: #333333; line-height: 1.6; }
        h1 { color: #004a99; font-size: 20px; margin-top: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .intro-text { font-size: 16px; margin-bottom: 25px; }

        /* Tabella Corsi in Scadenza */
        .training-card { background-color: #fff9f2; border: 1px solid #ffe8cc; border-radius: 6px; padding: 20px; margin: 20px 0; }
        .course-row { border-bottom: 1px solid #eee; padding: 10px 0; display: flex; justify-content: space-between; align-items: center; }
        .course-row:last-child { border-bottom: none; }
        .course-name { font-weight: bold; color: #2d3748; }
        .expiry-tag { color: #e53e3e; font-weight: bold; font-size: 14px; background: #fff5f5; padding: 4px 8px; border-radius: 4px; }

        /* Pulsante CTA */
        .cta-container { text-align: center; margin: 35px 0; }
        .button { background-color: #004a99; color: #ffffff !important; padding: 15px 35px; border-radius: 4px; font-weight: bold; display: inline-block; }

        /* Footer */
        .footer { background-color: #f8fafc; padding: 30px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .footer b { color: #004a99; }
        </div>
    </style>
</head>
<body>
<table class="main-container" align="center" cellpadding="0" cellspacing="0">
    <tr>
        <td class="header">
            <img src="https://www.metallurgicabresciana.it/wp-content/themes/metallurgica/img/logo.png" alt="Metallurgica Bresciana S.p.A." class="logo">
            <div style="color: #ffffff; font-size: 14px; opacity: 0.8;">{{$info['testo']}}</div>
        </td>
    </tr>
    <tr>
        <td class="content">
            <h1>Promemoria Formazione</h1>
            <p class="intro-text">Ciao,</p>
            <p>Ti informiamo che i seguenti moduli formativi obbligatori del Dipendente <strong>{{$info['dipendente']}}</strong> sono in scadenza. È necessario procedere con il corso di aggiornamento per mantenere la validità delle certificazioni richieste:</p>

            <div class="training-card">
                @foreach($info['formazioni'] as $formaszioni)
                    <div class="course-row">
                        <span class="course-name">{{$formaszioni['titolo']}}</span>
                        <span class="expiry-tag">Scade il {{$formaszioni['scadenza']}}</span>
                    </div>
                @endforeach
            </div>

            <div class="cta-container">
                <a href="[LINK_PORTALE]" class="button">Accedi al Portale</a>
            </div>
        </td>
    </tr>
    <tr>
        <td class="footer">
            <p><strong>Metallurgica Bresciana S.p.A.</strong><br>
                Viale G. Marconi, 1 - 25020 Dello (BS), Italia<br>
                <a href="https://www.metallurgicabresciana.it" style="color: #004a99;">www.metallurgicabresciana.it</a></p>
            <p style="margin-top: 15px;">Questa è una comunicazione automatica inviata dal sistema HR.</p>
        </td>
    </tr>
</table>
</body>
</html>
