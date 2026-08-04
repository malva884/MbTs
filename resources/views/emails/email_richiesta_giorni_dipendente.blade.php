<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richiesta Da Approvare</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f8; font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f8;">
        <tr>
            <td align="center" style="padding: 24px 12px;">

                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width: 560px; width: 100%; background-color: #ffffff; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">

                    <tr>
                        <td style="background-color: #0b5394; padding: 24px 32px; text-align: left;">
                            <img src="https://www.metallurgicabresciana.it/assets/img/logo18.png" alt="Metallurgica Bresciana" style="height: 32px; width: auto; max-width: 180px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px 32px 8px;">
                            <p style="margin: 0 0 8px; font-size: 12px; color: #8b8b9e; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">HR Management</p>
                            <h1 style="margin: 0 0 16px; font-size: 22px; color: #1a1a2e; font-weight: 700;">Nuova Richiesta Da Approvare</h1>
                            <p style="margin: 0; font-size: 15px; color: #555; line-height: 1.6;">Ciao,<br>hai ricevuto una nuova richiesta di <strong style="color: #0b5394;">{{ $info['tipologia'] }}</strong> da <strong style="color: #0b5394;">{{ $info['dipendente'] }}</strong> da approvare.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 24px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #faf9fc; border: 1px solid #ece9f5; border-radius: 6px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e; width: 140px;">Dipendente</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{ $info['dipendente'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Matricola</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{ $info['matricola'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 5px 0; font-size: 13px; color: #8b8b9e;">Tipologia</td>
                                                <td style="padding: 5px 0; font-size: 15px; color: #1a1a2e; font-weight: 500;">{{ $info['tipologia'] }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if(!empty($approvatori))
                    <tr>
                        <td style="padding: 20px 32px 8px;">
                            <p style="margin: 0 0 12px; font-size: 14px; color: #1a1a2e; font-weight: 600;">Approvatori</p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #faf9fc; border: 1px solid #ece9f5; border-radius: 6px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        @foreach($approvatori as $approvatore)
                                            <p style="margin: 0 0 8px; font-size: 15px; color: #1a1a2e;">{{ $approvatore }}</p>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td style="padding: 20px 32px 8px;">
                            <p style="margin: 0 0 12px; font-size: 14px; color: #1a1a2e; font-weight: 600;">Date richieste</p>
                            @foreach($giorni as $giorno)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                    <tr>
                                        <td style="padding: 12px 16px; background-color: #f0f7ff; border: 1px solid #d4e4ff; border-radius: 6px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="font-size: 15px; color: #0b5394; font-weight: 600;">{{$giorno->data}}</td>
                                                    @if($info['tipologia'] == 'Permesso' && $giorno->ora_inizio && $giorno->ora_fine)
                                                        <td style="text-align: right; font-size: 13px; color: #555;">
                                                            {{$giorno->ora_inizio}} - {{$giorno->ora_fine}}
                                                            @php
                                                                $inizio = strtotime($giorno->ora_inizio);
                                                                $fine = strtotime($giorno->ora_fine);
                                                                $ore = ($fine - $inizio) / 3600;
                                                            @endphp
                                                            <span style="color: #8b8b9e;">({{number_format($ore, 1)}}h)</span>
                                                        </td>
                                                    @endif
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                        </td>
                    </tr>

                    @if($token)
                    <tr>
                        <td style="padding: 24px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:0;Margin:0">
                                        <span class="es-button-border" style="border-style:solid;border-color:#2CB543;background:#5C68E2;border-width:0px;display:inline-block;border-radius:5px;width:auto">
                                            <a href="https://portale.metallurgicabresciana.it/build/hr/richieste/view/{{ $id }}" target="_blank" class="es-button" style="mso-style-priority:100 !important;text-decoration:none !important;mso-line-height-rule:exactly;color:#FFFFFF;font-size:20px;padding:10px 30px 10px 30px;display:inline-block;background:#5C68E2;border-radius:5px;font-family:merriweather, georgia, 'times new roman', serif;font-weight:bold;font-style:normal;line-height:24px;width:auto;text-align:center;letter-spacing:0;mso-padding-alt:0;mso-border-alt:10px solid #5C68E2">Accedi Al Portale</a>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td style="background-color: #faf9fc; padding: 20px 32px; border-top: 1px solid #ece9f5;">
                            <p style="margin: 0 0 4px; font-size: 13px; color: #1a1a2e; font-weight: 600;">Metallurgica Bresciana S.p.A.</p>
                            <p style="margin: 0 0 2px; font-size: 12px; color: #8b8b9e;">Viale G. Marconi, 31 &middot; 25020 Dello (BS)</p>
                            <p style="margin: 0 0 12px; font-size: 12px; color: #8b8b9e;"><a href="https://www.metallurgicabresciana.it" style="color: #0b5394; text-decoration: none;">www.metallurgicabresciana.it</a></p>
                            <p style="margin: 0; font-size: 11px; color: #b0b0c0;">Comunicazione automatica &middot; Non rispondere a questa email</p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
