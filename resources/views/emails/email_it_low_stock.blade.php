<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Minimo Raggiunto</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f8; font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f8;">
        <tr>
            <td align="center" style="padding: 24px 12px;">

                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width: 560px; width: 100%; background-color: #ffffff; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background-color: #6c2bd9; padding: 24px 32px; text-align: left;">
                            <img src="{{ asset('images/custom/logo_mb.png') }}" alt="Metallurgica Bresciana" style="height: 32px; width: auto; max-width: 180px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px 32px 8px;">
                            <p style="margin: 0 0 8px; font-size: 12px; color: #8b8b9e; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">IT Asset Management</p>
                            <h1 style="margin: 0 0 16px; font-size: 22px; color: #1a1a2e; font-weight: 700;">Stock minimo raggiunto</h1>
                            <p style="margin: 0; font-size: 15px; color: #555; line-height: 1.6;">Gentile Ufficio IT,<br>lo stock per l'asset <strong style="color: #6c2bd9;">{{$info['brand']}} {{$info['model']}}</strong> è sceso sotto il livello minimo configurato.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #faf9fc; border: 1px solid #ece9f5; border-radius: 6px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e; width: 140px;">Marca</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{$info['brand']}}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Modello</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{$info['model']}}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Quantità disponibile</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #e6a700; font-weight: 600;">{{$info['available_count']}}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Quantità minima</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{$info['min_stock']}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 32px 8px;">
                            <p style="margin: 0 0 12px; font-size: 14px; color: #1a1a2e; font-weight: 600;">Stato attuale</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding: 12px 16px; background-color: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size: 14px; color: #c53030;">⚠️ Stock critico - È necessario rifornire questo asset</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 32px 32px;">
                            <p style="margin: 0 0 20px; font-size: 13px; color: #8b8b9e; line-height: 1.5;">Si consiglia di procedere con l'acquisto di nuovi asset per evitare interruzioni delle attività.</p>
                            <a href="{{ config('app.url', url('/')) }}/#/it/assets/list" style="display: inline-block; background-color: #6c2bd9; color: #ffffff !important; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 6px; text-decoration: none;">Vai alla lista asset</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #faf9fc; padding: 20px 32px; border-top: 1px solid #ece9f5;">
                            <p style="margin: 0 0 4px; font-size: 13px; color: #1a1a2e; font-weight: 600;">Metallurgica Bresciana S.p.A.</p>
                            <p style="margin: 0 0 2px; font-size: 12px; color: #8b8b9e;">Viale G. Marconi, 1 &middot; 25020 Dello (BS)</p>
                            <p style="margin: 0 0 12px; font-size: 12px; color: #8b8b9e;"><a href="https://www.metallurgicabresciana.it" style="color: #6c2bd9; text-decoration: none;">www.metallurgicabresciana.it</a></p>
                            <p style="margin: 0; font-size: 11px; color: #b0b0c0;">Comunicazione automatica &middot; Non rispondere a questa email</p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
