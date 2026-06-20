<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FAI Report - Metallurgica Bresciana</title>
    <style>
        @page {
            margin: 0px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.35; /* Leggermente ridotto per recuperare spazio sul testo */
            font-size: 12.5px; /* Portato da 13px a 12.5px per compattezza generale */
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Spalla laterale brunita */
        .sidebar-decor {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 12px;
            background: #0f172a;
        }

        /* Container con padding ottimizzato per risparmiare spazio in altezza */
        .container {
            padding: 40px 45px 20px 60px;
        }

        /* Header Tech Layout - Spazi ridotti */
        .header-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px; /* Ridotto da 40px a 20px */
        }
        .brand-zone {
            width: 65%;
            vertical-align: bottom;
        }

        /* Logo aziendale leggermente ridimensionato */
        .company-title {
            font-family: 'Times New Roman', Times, Georgia, serif;
            font-style: italic;
            font-size: 26px; /* Ridotto da 32px per guadagnare spazio in alto */
            font-weight: normal;
            color: #1a202c;
            line-height: 1.1;
        }

        .company-subtitle {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 6px;
            font-weight: bold;
        }
        .meta-zone {
            width: 35%;
            text-align: right;
            vertical-align: top;
        }
        .doc-id-label {
            font-family: Arial, sans-serif;
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 1.2px;
            font-weight: bold;
        }
        .code-display {
            font-family: Arial, sans-serif;
            font-size: 22px;
            font-weight: bold;
            color: #0284c7;
            margin-top: 2px;
        }

        /* Sezioni e Intestazioni più compatte */
        .section {
            margin-bottom: 22px; /* Ridotto da 35px a 22px per avvicinare i blocchi */
            page-break-inside: avoid;
        }
        .section-title {
            font-family: Arial, sans-serif;
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 8px; /* Ridotto da 15px a 8px */
        }

        /* Griglia dei dati Tecnici a colonne compattata */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-table td {
            padding: 5px 0; /* Ridotto da 10px a 5px per stringere le righe */
            vertical-align: top;
            width: 50%;
        }
        .data-block {
            margin-right: 20px;
            border-left: 3px solid #cbd5e1;
            padding-left: 10px;
        }
        .data-label {
            font-family: Arial, sans-serif;
            font-size: 8.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .data-value {
            font-size: 12px;
            color: #0f172a;
            font-weight: bold;
            margin-top: 2px;
        }

        /* Badge di Stato Tecnico Dinamico */
        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .status-conforme {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .status-non-conforme {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .status-in-corso {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fdba74;
        }

        /* Contenitore Descrizioni più sottile */
        .technical-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px; /* Ridotto il padding interno */
            font-size: 12px;
            color: #334155;
            white-space: pre-line;
            box-shadow: inset 3px 0 0 #0f172a;
        }

        /* ==========================================================================
           CHECKLIST PROVE - VERSIONE ULTRA COMPATTA (Stato IN CORSO)
           ========================================================================== */
        .lab-checklist {
            width: 100%;
            border-collapse: collapse;
        }
        .lab-checklist td {
            padding: 3px 10px; /* Ridotto da 4px a 3px */
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .check-zone {
            width: 20px;
        }
        .custom-checkbox {
            width: 5px;
            height: 5px;
            background-color: #0284c7;
            border-radius: 50%;
            margin: 0 auto;
        }
        .probe-name {
            font-weight: bold;
            color: #334155;
            font-size: 10.5px;
        }

        /* ==========================================================================
           TABELLA PROVE ESTESA - MASSIMA COMPATTEZZA (Stato CHIUSO / VALUTATO)
           ========================================================================== */
        .lab-report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .lab-report-table th {
            font-family: Arial, sans-serif;
            padding: 5px 8px; /* Ridotto da 8px/10px a 5px/8px per salvare altezza */
            background: #f8fafc;
            font-size: 9.5px;
            text-transform: uppercase;
            color: #475569;
            font-weight: bold;
            border-bottom: 2px solid #cbd5e1;
            text-align: left;
        }
        .lab-report-table td {
            padding: 5px 8px; /* Ridotto da 8px a 5px per riga per recuperare prezioso spazio verticale */
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 11px;
        }
        .inline-bullet {
            display: inline-block;
            width: 5px;
            height: 5px;
            background-color: #0284c7;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 5px;
        }

        .no-data {
            color: #94a3b8;
            font-style: italic;
            padding: 8px 0;
        }

        /* Piè di pagina Istituzionale statico */
        .footer {
            position: absolute;
            bottom: 25px; /* Alzato leggermente per non sovrapporsi al testo */
            left: 60px;
            right: 45px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="sidebar-decor"></div>

<div class="container">

    <table class="header-container">
        <tr>
            <td class="brand-zone">
                <div class="company-title">Metallurgica Bresciana s.p.a.</div>
                <div class="company-subtitle">Quality Assurance System &bull; First Article Inspection</div>
            </td>
            <td class="meta-zone">
                <div class="doc-id-label">Rapporto Ispezione N°</div>
                <div class="code-display">{{ $fai->codice }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Tracciabilità Flusso Prodotto</div>
        <table class="grid-table">
            <tr>
                <td>
                    <div class="data-block">
                        <div class="data-label">Cliente / Fornitore</div>
                        <div class="data-value">{{ $fai->soggetto }}</div>
                    </div>
                </td>
                <td>
                    <div class="data-block">
                        <div class="data-label">Codice Articolo / Prodotto</div>
                        <div class="data-value">{{ $fai->articolo }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="data-block">
                        <div class="data-label">Ordine di Lavoro (OL)</div>
                        <div class="data-value">{{ $fai->ol }}</div>
                    </div>
                </td>
                <td>
                    <div class="data-block">
                        <div class="data-label">Data Registrazione</div>
                        <div class="data-value">{{ \Carbon\Carbon::parse($fai->data_inizio)->format('d/m/Y') }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    @if(strtoupper(str_replace(' ', '_', $fai->esito)) !== 'IN_CORSO' && $fai->updated_at)
                        <div class="data-block" style="border-left: 3px solid #0284c7;">
                            <div class="data-label" style="color: #0284c7;">Data Chiusura FAI</div>
                            <div class="data-value">{{ \Carbon\Carbon::parse($fai->updated_at)->format('d/m/Y') }}</div>
                        </div>
                    @else
                        <div class="data-block">
                            <div class="data-label">Specifica Tecnica Interna / Cliente</div>
                            <div class="data-value">{{ $fai->specifica ?? 'Nessuna specifica allegata' }}</div>
                        </div>
                    @endif
                </td>
                <td>
                    <div class="data-block">
                        <div class="data-label">Stato Avanzamento FAI</div>
                        <div class="data-value" style="margin-top: 2px;">
                            <span class="status-pill
                                @if(strtoupper(str_replace(' ', '_', $fai->esito)) === 'CONFORME' || strtoupper(str_replace(' ', '_', $fai->esito)) === 'POSITIVO') status-conforme
                                @elseif(strtoupper(str_replace(' ', '_', $fai->esito)) === 'NON_CONFORME' || strtoupper(str_replace(' ', '_', $fai->esito)) === 'NEGATIVO') status-non-conforme
                                @else status-in-corso @endif">
                                {{ str_replace('_', ' ', $fai->esito) }}
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
            @if(strtoupper(str_replace(' ', '_', $fai->esito)) !== 'IN_CORSO' && $fai->updated_at)
                <tr>
                    <td colspan="2">
                        <div class="data-block" style="margin-top: 3px;">
                            <div class="data-label">Specifica Tecnica Interna / Cliente</div>
                            <div class="data-value">{{ $fai->specifica ?? 'Nessuna specifica allegata' }}</div>
                        </div>
                    </td>
                </tr>
            @endif
        </table>
    </div>

    @if($fai->esito_fattibilita)
        <div class="section">
            <div class="section-title">Esito Esame di Fattibilità</div>
            <div class="technical-box" style="background: #ffffff; box-shadow: inset 3px 0 0 #0284c7;">
                {{ $fai->esito_fattibilita }}
            </div>
        </div>
    @endif

    <div class="section">
        <div class="section-title">Descrizione Apertura</div>
        <div class="technical-box">
            {{ $fai->descrizione }}
        </div>
    </div>

    @if(strtoupper(str_replace(' ', '_', $fai->esito)) === 'IN_CORSO')
        <div class="section">
            <div class="section-title">Piano di Controllo e Prove Strumentali</div>
            @if(!empty($nomiProve) && count($nomiProve) > 0)
                <table class="lab-checklist">
                    @foreach($nomiProve as $prova)
                        <tr>
                            <td class="check-zone">
                                <div class="custom-checkbox"></div>
                            </td>
                            <td class="probe-name">{{ $prova }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div class="no-data">Nessuna prova inserita nel piano di campionamento corrente.</div>
            @endif
        </div>
    @else
        <div class="section">
            <div class="section-title">Piano di Controllo e Prove Strumentali (Esiti Finali)</div>
            @if(!empty($proveCompleto) && count($proveCompleto) > 0)
                <table class="lab-report-table">
                    <thead>
                    <tr>
                        <th style="width: 42%;">Test / Prova</th>
                        <th style="width: 20%;">Data Esecuzione</th>
                        <th style="width: 22%;">Operatore Lab</th>
                        <th style="width: 16%; text-align: right;">Esito</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($proveCompleto as $test)
                        <tr>
                            <td style="font-weight: bold; color: #334155;">
                                <span class="inline-bullet"></span>
                                <span style="vertical-align: middle;">{{ $test->nome_prova }}</span>
                            </td>
                            <td style="color: #475569;">
                                {{ $test->data_prova ? \Carbon\Carbon::parse($test->data_prova)->format('d/m/Y') : 'Da pianificare' }}
                            </td>
                            <td style="color: #475569;">
                                {{ $test->operatore ?? 'Non assegnato' }}
                            </td>
                            <td style="font-weight: bold; text-align: right;">
                                @php
                                    $cleanEsito = strtoupper($test->esito ?? '');
                                    $color = '#cbd5e1';

                                    if (in_array($cleanEsito, ['CONFORME', 'POSITIVO', 'PASSED', 'OK'])) {
                                        $color = '#16a34a';
                                    } elseif (in_array($cleanEsito, ['NON CONFORME', 'NEGATIVO', 'FAILED', 'KO'])) {
                                        $color = '#dc2626';
                                    } elseif (in_array($cleanEsito, ['MANCANTE', 'DA CARICARE'])) {
                                        $color = '#64748b';
                                    }
                                @endphp
                                <span style="color: {{ $color }}; font-size: 10.5px;">
                                        {{ $test->esito ?? 'NON VALUTATO' }}
                                    </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-data">Nessun record o prova di laboratorio inserita nel piano corrente.</div>
            @endif
        </div>
    @endif

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="text-align: left;">METALLURGICA BRESCIANA S.P.A. &bull; DOCUMENTAZIONE SISTEMA QUALITÀ</td>
                <td style="text-align: right;">DATA STAMPA: {{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>
