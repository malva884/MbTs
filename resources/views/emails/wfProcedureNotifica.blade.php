<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisione Documento</title>
    <style>
        /* Setup generale per client email */
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; }
        table { border-spacing: 0; width: 100%; }
        td { padding: 0; }
        img { border: 0; }

        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding-bottom: 60px; }
        .main { background-color: #ffffff; width: 100%; max-width: 600px; margin: 40px auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; }

        /* Design Element */
        .accent-bar { height: 6px; background: linear-gradient(90deg, #1e293b 0%, #334155 100%); }

        .header { padding: 40px 40px 20px 40px; text-align: center; }
        .content { padding: 0 40px 40px 40px; line-height: 1.8; font-size: 16px; }

        .doc-box { background-color: #f1f5f9; border-radius: 8px; padding: 24px; margin: 30px 0; border-left: 4px solid #1e293b; }
        .doc-title { display: block; font-weight: 700; color: #1e293b; font-size: 18px; margin-bottom: 4px; }
        .doc-subtitle { font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }

        .button-wrapper { text-align: center; margin-top: 30px; }
        .button { background-color: #1e293b; color: #ffffff !important; padding: 16px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px; display: inline-block; letter-spacing: 0.5px; transition: background-color 0.3s; }

        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 20px; padding: 0 20px; }
        .footer a { color: #64748b; text-decoration: underline; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="main">
        <div class="accent-bar"></div>

        <div class="header">
            <h2 style="font-weight: 300; color: #0f172a; margin: 0; font-size: 28px; letter-spacing: -0.5px;">Richiesta di Revisione</h2>
        </div>

        <div class="content">
            <p>Cioa,</p>
            <p>È stato rmrsso un nuovo documento che richiede la tua attenzione. Di seguito trovi i dettagli della procedura:</p>

            <div class="doc-box">
                <span class="doc-subtitle">Documento da approvare</span>
                <span class="doc-title">{{ $document->procedura }}</span>
                <p style="margin: 8px 0 0 0; font-size: 14px; color: #475569;">
                    Categoria: {{ $document->Categoria->categoria }}<br>
                    Processo: {{ $document->Processo->categoria }}<br>
                    Procedura: {{ $document->procedura }}<br>
                    Descrizione: {{ $document->descrizione }}<br>
                    Emesso: <span style="color: #e11d48;">{{ date_format($document->created_at,'d/m/Y') }}</span>
                </p>
            </div>

            <p>Ti invitiamo a visionare il file e procedere con l'approvazione tramite il portale dedicato.</p>

            <div class="button-wrapper">
                <a href="{{ $url }}" class="button">Esamina Documento</a>
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Metallurgica Bresciana S.p.A.</strong><br>
            Viale G. Marconi, 1 - 25020 Dello (BS), Italy</p>
        <div class="divider"></div>
        <p>Questa è una comunicazione automatica generata dal sistema gestionale.<br>Si prega di non rispondere a questo indirizzo email.</p>
    </div>
</div>
</body>
</html>
