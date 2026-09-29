<!doctype html>
<html lang=it>
<head>
    <meta charset=UTF-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <title>Report Costi Spedizioni DDT - {{ $periodo }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f8;font-family:'Segoe UI','Helvetica Neue',Arial,sans-serif">
<table role=presentation width=100% cellpadding=0 cellspacing=0 style=background-color:#f4f4f8>
<tr>
    <td align=center style="padding:24px 12px">
        <table role=presentation width=680 cellpadding=0 cellspacing=0 style="max-width:680px;width:100%;background-color:#fff;border-radius:6px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)">

            {{-- Header --}}
            <tr>
                <td style="background-color:#0b5394;padding:24px 32px;text-align:left">
                    <img src=https://www.metallurgicabresciana.it/assets/img/logo18.png alt="Metallurgica Bresciana" style=height:32px;width:auto;max-width:180px>
                </td>
            </tr>

            {{-- Titolo --}}
            <tr>
                <td style="padding:32px 32px 12px">
                    <p style="margin:0 0 8px;font-size:12px;color:#8b8b9e;font-weight:600;text-transform:uppercase;letter-spacing:1px">Spedizioni · DDT</p>
                    <h1 style="margin:0 0 16px;font-size:22px;color:#1a1a2e;font-weight:700">Report Costi Spedizioni DDT</h1>
                    <p style=margin:0;font-size:15px;color:#555;line-height:1.6>
                        Riepilogo mensile dei costi di spedizione calcolati sui DDT — periodo <strong style=color:#0b5394>{{ $periodo }}</strong>.
                    </p>
                </td>
            </tr>

            {{-- KPI riepilogo --}}
            <tr>
                <td style="padding:12px 32px 8px">
                    <table role=presentation width=100% cellpadding=0 cellspacing=0 style="background-color:#faf9fc;border:1px solid #ece9f5;border-radius:6px">
                        <tr>
                            <td style="padding:16px 20px">
                                <table role=presentation width=100% cellpadding=0 cellspacing=0>
                                    <tr>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e;width:180px">DDT totali</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ $stats->totale }}</td>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e;width:180px">Colli</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ (int) $stats->colli }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e">Con costo calcolato</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ (int) $stats->con_costo }}</td>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e">Peso totale</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ number_format((float) $stats->peso, 2, ',', '.') }} kg</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e">Senza costo</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ (int) $stats->senza_costo }}</td>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e">Costo totale</td>
                                        <td style="padding:4px 0;font-size:14px;color:#0b5394;font-weight:700">€ {{ number_format((float) $stats->costo_totale, 2, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- Costi per vettore --}}
            <tr>
                <td style="padding:16px 32px 8px">
                    <p style="margin:0 0 8px;font-size:13px;color:#1a1a2e;font-weight:600">Costi per vettore</p>
                    <table role=presentation width=100% cellpadding=0 cellspacing=0 style="border:1px solid #ece9f5;border-radius:6px;overflow:hidden">
                        <tr style=background-color:#faf9fc>
                            <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Vettore</th>
                            <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">DDT</th>
                            <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Colli</th>
                            <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Peso (kg)</th>
                            <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Senza costo</th>
                            <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Costo totale</th>
                        </tr>
                        @foreach ($perVettore as $v)
                            <tr>
                                <td style="padding:10px 12px;font-size:14px;color:#1a1a2e;font-weight:600;border-bottom:1px solid #f4f2fa">{{ $v->vettore }}</td>
                                <td align=right style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ $v->numero_ddt }}</td>
                                <td align=right style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ (int) $v->colli }}</td>
                                <td align=right style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ number_format((float) $v->peso, 2, ',', '.') }}</td>
                                <td align=right style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ (int) $v->senza_costo }}</td>
                                <td align=right style="padding:10px 12px;font-size:14px;color:#1a1a2e;font-weight:600;border-bottom:1px solid #f4f2fa">€ {{ number_format((float) $v->costo_totale, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>

            {{-- DDT senza costo (con listino attivo) --}}
            @if ($senzaCosto->isNotEmpty())
                <tr>
                    <td style="padding:16px 32px 8px">
                        <p style="margin:0 0 8px;font-size:13px;color:#1a1a2e;font-weight:600">DDT senza costo calcolato con listino attivo ({{ $senzaCostoConListino }})</p>
                        <table role=presentation width=100% cellpadding=0 cellspacing=0 style="border:1px solid #ece9f5;border-radius:6px;overflow:hidden">
                            <tr style=background-color:#faf9fc>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">DDT</th>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Vettore</th>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Destinazione</th>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Motivo</th>
                            </tr>
                            @foreach ($senzaCosto as $d)
                                <tr>
                                    <td style="padding:10px 12px;font-size:14px;color:#1a1a2e;font-weight:600;border-bottom:1px solid #f4f2fa">{{ $d->numero_ddt }}</td>
                                    <td style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ $d->vettore }}</td>
                                    <td style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ $d->destinazione_nome }}</td>
                                    <td style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ $d->costo_note ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </table>
                        @if ($senzaCostoConListino > $senzaCosto->count())
                            <p style="margin:8px 0 0;font-size:12px;color:#8b8b9e">
                                ... e altri {{ $senzaCostoConListino - $senzaCosto->count() }} DDT senza costo.
                            </p>
                        @endif
                    </td>
                </tr>
            @endif

            {{-- Riepilogo motivi mancati calcoli --}}
            @if ($sommarioMotivi->isNotEmpty())
                <tr>
                    <td style="padding:16px 32px 8px">
                        <p style="margin:0 0 8px;font-size:13px;color:#1a1a2e;font-weight:600">Riepilogo costi non calcolati ({{ $stats->senza_costo }})</p>
                        <table role=presentation width=100% cellpadding=0 cellspacing=0 style="border:1px solid #ece9f5;border-radius:6px;overflow:hidden">
                            <tr style=background-color:#faf9fc>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Motivo</th>
                                <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">DDT</th>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Vettori coinvolti</th>
                            </tr>
                            @foreach ($sommarioMotivi as $m)
                                <tr>
                                    <td style="padding:10px 12px;font-size:14px;color:#1a1a2e;font-weight:600;border-bottom:1px solid #f4f2fa">{{ $m['motivo'] }}</td>
                                    <td align=right style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ $m['numero'] }}</td>
                                    <td style="padding:10px 12px;font-size:14px;color:#555;border-bottom:1px solid #f4f2fa">{{ $m['vettori'] ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            @endif

            {{-- Nota automatica --}}
            <tr>
                <td style="padding:20px 32px 32px">
                    <p style=margin:0;font-size:13px;color:#8b8b9e;line-height:1.5>Questa è una notifica automatica del sistema.</p>
                </td>
            </tr>

            {{-- Footer --}}
            <tr>
                <td style="background-color:#faf9fc;padding:20px 32px;border-top:1px solid #ece9f5">
                    <p style="margin:0 0 4px;font-size:13px;color:#1a1a2e;font-weight:600">Metallurgica Bresciana S.p.A.</p>
                    <p style="margin:0 0 2px;font-size:12px;color:#8b8b9e">Viale G. Marconi, 1 — 25020 Dello (BS)</p>
                    <p style="margin:0 0 12px;font-size:12px;color:#8b8b9e"><a href=https://www.metallurgicabresciana.it style=color:#0b5394;text-decoration:none>www.metallurgicabresciana.it</a></p>
                    <p style=margin:0;font-size:11px;color:#b0b0c0>Comunicazione automatica — Non rispondere a questa email</p>
                </td>
            </tr>
        </table>
    </td>
</tr>
</table>
</body>
</html>
