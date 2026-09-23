<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Scheda EHS</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { display: table; width: 100%; margin-bottom: 15px; }
        .header .logo { display: table-cell; width: 120px; vertical-align: middle; }
        .header .title { display: table-cell; vertical-align: middle; text-align: center; }
        .header h1 { font-size: 18px; margin: 0; }
        .header .subtitle { font-size: 12px; color: #666; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.info td { border: 1px solid #ccc; padding: 5px 8px; vertical-align: top; }
        table.info td.label { background: #f2f2f2; font-weight: bold; width: 22%; }
        .section-title { font-size: 13px; font-weight: bold; margin: 14px 0 6px; border-bottom: 2px solid #444; padding-bottom: 3px; }
        .text-block { border: 1px solid #ccc; padding: 6px 8px; min-height: 30px; white-space: pre-wrap; }
        .footer { margin-top: 20px; font-size: 9px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if(!empty($data['logo']) && file_exists($data['logo']))
                <img src="{{ $data['logo'] }}" style="max-width: 110px;">
            @endif
        </div>
        <div class="title">
            <h1>{{ $data['infortunio']['tipo_scheda'] == 1 ? 'SCHEDA INFORTUNIO' : 'SCHEDA EVENTO AMBIENTALE' }}</h1>
            <div class="subtitle">{{ $data['infortunio']['t_tipo_inf'] ?? '' }}</div>
        </div>
    </div>

    <div class="section-title">Dati Dipendente</div>
    <table class="info">
        <tr>
            <td class="label">Dipendente</td>
            <td>{{ $data['infortunio']['nome'] ?? '-' }}</td>
            <td class="label">Matricola</td>
            <td>{{ $data['infortunio']['matricola'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Qualifica</td>
            <td>{{ $data['infortunio']['qualifica'] ?? '-' }}</td>
            <td class="label">Inserito da</td>
            <td>{{ $data['infortunio']['user'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Dati Evento</div>
    <table class="info">
        <tr>
            <td class="label">Data Evento</td>
            <td>{{ $data['infortunio']['data_evento'] ?? '-' }}</td>
            <td class="label">Ora Lavorativa</td>
            <td>{{ $data['infortunio']['ora_lavorativa'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Reparto</td>
            <td>{{ $data['infortunio']['department'] ?? '-' }}</td>
            <td class="label">Sede</td>
            <td>{{ $data['infortunio']['site'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Descrizione Dinamica</div>
    <div class="text-block">{{ $data['infortunio']['desc_dinamica'] ?? '-' }}</div>

    <div class="section-title">Testimoni</div>
    <div class="text-block">{{ $data['infortunio']['testimoni'] ?? '-' }}</div>

    @if($data['infortunio']['tipo_scheda'] == 1)
        <div class="section-title">Dati Infortunio</div>
        <table class="info">
            <tr>
                <td class="label">Tipo Lesione</td>
                <td>{{ $data['infortunio']['injurie'] ?? '-' }}</td>
                <td class="label">Sede Anatomica</td>
                <td>{{ $data['infortunio']['anatomical'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Giorni Infortunio</td>
                <td colspan="3">{{ $data['infortunio']['giorni_infortunio'] ?? '-' }}</td>
            </tr>
        </table>
    @else
        <div class="section-title">Dati Evento Ambientale</div>
        <table class="info">
            <tr>
                <td class="label">Tipo Evento</td>
                <td colspan="3">{{ $data['infortunio']['event'] ?? '-' }}</td>
            </tr>
        </table>
    @endif

    <div class="section-title">Analisi e Azioni</div>
    <table class="info">
        <tr>
            <td class="label">Causa</td>
            <td>{{ $data['infortunio']['causa'] ?? '-' }}</td>
            <td class="label">Responsabile Azione</td>
            <td>{{ $data['infortunio']['responsabile_azione'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Analisi Causa</div>
    <div class="text-block">{{ $data['infortunio']['analisi_causa'] ?? '-' }}</div>

    <div class="section-title">Azioni di Contenimento</div>
    <div class="text-block">{{ $data['infortunio']['azioni_contenimento'] ?? '-' }}</div>

    <div class="section-title">Azioni Correttive</div>
    <div class="text-block">{{ $data['infortunio']['azioni'] ?? '-' }}</div>

    <div class="footer">
        Documento generato il {{ date('d/m/Y H:i') }} — MbTs EHS
    </div>
</body>
</html>
