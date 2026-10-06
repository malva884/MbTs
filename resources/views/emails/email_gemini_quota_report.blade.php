<!doctype html>
<html lang=it>
<head>
    <meta charset=UTF-8>
    <meta name=viewport content="width=device-width,initial-scale=1">
    <title>Report Rate Limit Gemini API - {{ $report['generated_at'] }}</title>
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
                    <p style="margin:0 0 8px;font-size:12px;color:#8b8b9e;font-weight:600;text-transform:uppercase;letter-spacing:1px">AI · Gemini API</p>
                    <h1 style="margin:0 0 16px;font-size:22px;color:#1a1a2e;font-weight:700">Report Rate Limit Gemini API</h1>
                    <p style=margin:0;font-size:15px;color:#555;line-height:1.6>
                        Stato delle chiavi API configurate e quote rilevate — generato il <strong style=color:#0b5394>{{ $report['generated_at'] }}</strong>.
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
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e;width:180px">Chiavi configurate</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ count($report['keys']) }}</td>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e;width:180px">Chiavi valide</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ count(array_filter($report['keys'], fn($k) => $k['valid'])) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e">Modelli sondati</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">
                                            @if ($report['validate_only'])
                                                nessuno (solo validazione)
                                            @else
                                                {{ implode(', ', $report['models_probed']) }}
                                            @endif
                                        </td>
                                        <td style="padding:4px 0;font-size:13px;color:#8b8b9e">Quote sforate</td>
                                        <td style="padding:4px 0;font-size:14px;color:#1a1a2e;font-weight:600">{{ count($report['limits']) }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- Dettaglio per chiave --}}
            @foreach ($report['keys'] as $key)
                <tr>
                    <td style="padding:16px 32px 8px">
                        <p style="margin:0 0 8px;font-size:13px;color:#1a1a2e;font-weight:600">
                            Chiave #{{ $key['index'] }} · <code style="background-color:#faf9fc;padding:2px 6px;border-radius:4px;font-size:12px">{{ $key['key'] }}</code>
                            @if ($key['valid'])
                                — valida, {{ $key['available_models'] }} modelli disponibili, tier: {{ $key['tier'] ?? 'n/d' }}
                            @else
                                — <span style=color:#c0392b>non valida</span>
                            @endif
                        </p>

                        @if (! $key['valid'])
                            <p style="margin:0;font-size:13px;color:#c0392b">{{ \Illuminate\Support\Str::limit($key['error'] ?? '', 200) }}</p>
                        @elseif (! empty($key['probes']))
                            <table role=presentation width=100% cellpadding=0 cellspacing=0 style="border:1px solid #ece9f5;border-radius:6px;overflow:hidden">
                                <tr style=background-color:#faf9fc>
                                    <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Modello</th>
                                    <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Stato</th>
                                    <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Latenza</th>
                                    <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Token</th>
                                    <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Context in/out</th>
                                    <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Quota / Errore</th>
                                </tr>
                                @foreach ($key['probes'] as $probe)
                                    @php
                                        $ml = $key['model_limits'][$probe['model']] ?? null;
                                        $statusColor = match ($probe['status']) {
                                            'ok' => '#1e8e3e',
                                            'quota_exceeded' => '#c0392b',
                                            default => '#b7791f',
                                        };
                                    @endphp
                                    <tr>
                                        <td style="padding:10px 12px;font-size:13px;color:#1a1a2e;font-weight:600;border-bottom:1px solid #f4f2fa">{{ $probe['model'] }}</td>
                                        <td style="padding:10px 12px;font-size:13px;color:{{ $statusColor }};font-weight:600;border-bottom:1px solid #f4f2fa">{{ $probe['status'] }}</td>
                                        <td align=right style="padding:10px 12px;font-size:13px;color:#555;border-bottom:1px solid #f4f2fa">{{ $probe['latency_ms'] !== null ? $probe['latency_ms'] . ' ms' : '-' }}</td>
                                        <td align=right style="padding:10px 12px;font-size:13px;color:#555;border-bottom:1px solid #f4f2fa">{{ $probe['tokens']['total'] ?? '-' }}</td>
                                        <td align=right style="padding:10px 12px;font-size:13px;color:#555;border-bottom:1px solid #f4f2fa">
                                            @if ($ml)
                                                {{ number_format($ml['input_token_limit']) }} / {{ number_format($ml['output_token_limit']) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td style="padding:10px 12px;font-size:12px;color:#555;border-bottom:1px solid #f4f2fa">
                                            @if (! empty($probe['quota_violations']))
                                                @foreach ($probe['quota_violations'] as $v)
                                                    {{ $v['dimension'] }} · {{ $v['metric'] }} = {{ $v['limit'] }}<br>
                                                @endforeach
                                                @if ($probe['retry_after_seconds'] !== null)
                                                    retry in {{ $probe['retry_after_seconds'] }}s
                                                @endif
                                            @elseif ($probe['error'])
                                                {{ \Illuminate\Support\Str::limit($probe['error'], 120) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                </tr>
            @endforeach

            {{-- Rate limit rilevati --}}
            <tr>
                <td style="padding:16px 32px 8px">
                    <p style="margin:0 0 8px;font-size:13px;color:#1a1a2e;font-weight:600">Rate limit rilevati</p>
                    @if (empty($report['limits']))
                        <p style="margin:0;font-size:13px;color:#8b8b9e;line-height:1.5">
                            Nessuna quota sforata durante la sonda: i limiti effettivi non sono rilevabili senza raggiungerli.
                            Per i limiti teorici del tier: <a href=https://ai.google.dev/gemini-api/docs/rate-limits style=color:#0b5394>documentazione Google</a>.
                        </p>
                    @else
                        <table role=presentation width=100% cellpadding=0 cellspacing=0 style="border:1px solid #ece9f5;border-radius:6px;overflow:hidden">
                            <tr style=background-color:#faf9fc>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Chiave</th>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Modello</th>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Dimensione</th>
                                <th align=left style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Metrica</th>
                                <th align=right style="padding:10px 12px;font-size:12px;color:#8b8b9e;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #ece9f5">Limite</th>
                            </tr>
                            @foreach ($report['limits'] as $l)
                                <tr>
                                    <td style="padding:10px 12px;font-size:12px;color:#555;border-bottom:1px solid #f4f2fa"><code>{{ $l['key'] }}</code></td>
                                    <td style="padding:10px 12px;font-size:13px;color:#1a1a2e;font-weight:600;border-bottom:1px solid #f4f2fa">{{ $l['model'] }}</td>
                                    <td style="padding:10px 12px;font-size:13px;color:#555;border-bottom:1px solid #f4f2fa">{{ $l['dimension'] }}</td>
                                    <td style="padding:10px 12px;font-size:12px;color:#555;border-bottom:1px solid #f4f2fa">{{ $l['metric'] }}</td>
                                    <td align=right style="padding:10px 12px;font-size:13px;color:#1a1a2e;font-weight:600;border-bottom:1px solid #f4f2fa">{{ $l['limit'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </td>
            </tr>

            {{-- Nota --}}
            <tr>
                <td style="padding:20px 32px 32px">
                    <p style=margin:0;font-size:13px;color:#8b8b9e;line-height:1.5>
                        {{ $report['note'] }} Questa è una notifica automatica del sistema.
                    </p>
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
