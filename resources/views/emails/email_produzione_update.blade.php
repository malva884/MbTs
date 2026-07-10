<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiornamento Produzione</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f8; font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f8;">
        <tr>
            <td align="center" style="padding: 24px 12px;">

                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width: 560px; width: 100%; background-color: #ffffff; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background-color: #6c2bd9; padding: 24px 32px; text-align: left;">
                            <img src="https://www.metallurgicabresciana.it/assets/img/logo18.png" style="height: 32px; width: auto; max-width: 180px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px 32px 8px;">
                            <p style="margin: 0 0 8px; font-size: 12px; color: #8b8b9e; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Produzione</p>
                            <h1 style="margin: 0 0 16px; font-size: 22px; color: #1a1a2e; font-weight: 700;">Aggiornamento Produzione</h1>
                            <p style="margin: 0; font-size: 15px; color: #555; line-height: 1.6;">È stato effettuato un aggiornamento sulla produzione con ID <strong style="color: #6c2bd9;">{{ $idProduzione }}</strong>.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #faf9fc; border: 1px solid #ece9f5; border-radius: 6px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e; width: 140px;">ID Produzione</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{ $idProduzione }}</td>
                                            </tr>
                                            @if($ordine)
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Ordine</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{ $ordine }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Tipo aggiornamento</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">
                                                    @if($updateType === 'fabbisogni')
                                                        Aggiornamento solo Fabbisogni
                                                    @elseif($updateType === 'avanzamento_fabbisogni')
                                                        Aggiornamento Avanzamento e Fabbisogni
                                                    @else
                                                        {{ $updateType }}
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($userName)
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Eseguito da</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{ $userName }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Data e ora</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{ now()->format('d/m/Y H:i:s') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 32px 32px;">
                            <p style="margin: 0; font-size: 13px; color: #8b8b9e; line-height: 1.5;">Questa è una notifica automatica del sistema.</p>
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
