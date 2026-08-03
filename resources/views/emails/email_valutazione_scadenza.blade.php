<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promemoria Valutazioni Competenze</title>
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
                            <p style="margin: 0 0 8px; font-size: 12px; color: #8b8b9e; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Risorse Umane</p>
                            <h1 style="margin: 0 0 16px; font-size: 22px; color: #1a1a2e; font-weight: 700;">Promemoria valutazioni competenze</h1>
                            <p style="margin: 0; font-size: 15px; color: #555; line-height: 1.6;">Gentile Responsabile,<br>ti ricordiamo che le valutazioni delle competenze per l'anno <strong style="color: #6c2bd9;">{{$info['anno']}}</strong> sono in scadenza. Di seguito i dipendenti con valutazioni ancora da completare:</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 32px 8px;">
                            @foreach($info['dipendenti'] as $dipendente)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
                                    <tr>
                                        <td style="padding: 14px 16px; background-color: #ffffff; border: 1px solid #e4e4ec; border-radius: 6px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="font-size: 14px; color: #1a1a2e; font-weight: 500;">
                                                        {{$dipendente['nome']}} {{$dipendente['cognome']}}
                                                        <span style="font-size: 12px; color: #8b8b9e; margin-left: 8px;">Matr. {{$dipendente['matricola']}}</span>
                                                    </td>
                                                    <td align="right" style="font-size: 12px; white-space: nowrap;">
                                                        @if($dipendente['valutate'] == 0)
                                                            <span style="color: #e53e3e; font-weight: 600;">&#9679; Da valutare</span>
                                                        @else
                                                            <span style="color: #e6a700; font-weight: 600;">&#9679; {{$dipendente['valutate']}}/{{$dipendente['totali']}} completate</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 32px 32px;">
                            <p style="margin: 0 0 20px; font-size: 13px; color: #8b8b9e; line-height: 1.5;">Si prega di completare le valutazioni entro la fine dell'anno.</p>
                            <a href="{{ config('app.url', url('/')) }}/#/hr/competenze" style="display: inline-block; background-color: #6c2bd9; color: #ffffff !important; font-size: 14px; font-weight: 600; padding: 12px 28px; border-radius: 6px; text-decoration: none;">Vai alle valutazioni</a>
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
