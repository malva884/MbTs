<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 45px 55px 70px 55px;
        }

        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #333;
            margin: 0;
        }

        /* Header: logo a sinistra, titolo centro, spunta a destra */
        .doc-header {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-header td {
            vertical-align: middle;
            padding: 0;
        }

        .doc-header .logo {
            width: 30%;
        }

        .doc-header .logo img {
            max-width: 180px;
        }

        .doc-header .title {
            width: 40%;
            text-align: center;
        }

        .doc-header .title h1 {
            margin: 0;
            font-size: 20px;
            line-height: 1.3;
        }

        .doc-header .check {
            width: 30%;
            text-align: right;
        }

        .doc-header .check img {
            max-width: 110px;
        }

        .header-rule {
            border: 0;
            border-top: 2px solid #333;
            margin: 18px 0 24px;
        }

        /* Blocco dati variazione */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }

        .info-table td {
            padding: 4px 8px;
            vertical-align: top;
            font-size: 13px;
        }

        .info-table td.label {
            width: 22%;
            color: #666;
        }

        .info-table td.value {
            width: 28%;
            font-weight: bold;
        }

        /* Tabelle approvatori/visualizzatori */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 26px;
        }

        .data-table th {
            background: #eee;
            border: 1px solid #ccc;
            padding: 6px 10px;
            font-size: 12px;
            text-align: left;
        }

        .data-table td {
            border: 1px solid #ddd;
            padding: 5px 10px;
            font-size: 12px;
        }

        .data-table td.stato-ok {
            color: #1a7f37;
            font-weight: bold;
        }

        /* Footer in basso */
        .doc-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            font-size: 11px;
        }

        .doc-footer p {
            margin: 0;
        }
    </style>
</head>

<body>

<table class="doc-header">
    <tr>
        <td class="logo">
            <img src="{{$data['logo']}}">
        </td>
        <td class="title">
            <h1>Log Variazione<br>ordine di produzione</h1>
        </td>
        <td class="check">
            @if(!empty($data['check']))
                <img src="{{$data['check']}}">
            @endif
        </td>
    </tr>
</table>

<hr class="header-rule">

<table class="info-table">
    <tr>
        <td class="label">OL</td>
        <td class="value">{{$data['ol']}}</td>
        <td class="label">Revisione</td>
        <td class="value">{{$data['revisione']}}</td>
    </tr>
    <tr>
        <td class="label">Creato da</td>
        <td class="value">{{$data['creator']}}</td>
        <td class="label">Data creazione</td>
        <td class="value">{{date_format($data['data_creazione'],'d/m/Y')}}</td>
    </tr>
    <tr>
        <td class="label">File</td>
        <td class="value">{{$data['file']}}</td>
        <td class="label">Stato</td>
        <td class="value">{{$data['stato']}}</td>
    </tr>
    <tr>
        <td class="label">Data approvazione</td>
        <td class="value">{{$data['data_approvazione'] ? date('d/m/Y', strtotime($data['data_approvazione'])) : '-'}}</td>
        <td class="label">Data chiusura</td>
        <td class="value">{{$data['end_date'] ? date('d/m/Y', strtotime($data['end_date'])) : '-'}}</td>
    </tr>
</table>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 45%;">Approvatori</th>
            <th style="width: 30%;">Data/ora Firma</th>
            <th style="width: 25%;">Stato</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['users'] as $user)
            <tr>
                <td>{{$user->full_name}}</td>
                <td>{{date_format($user->created_at,'d-m-Y H:i')}}</td>
                <td class="{{$user->approval_action == 'Approved' ? 'stato-ok' : ''}}">
                    {{($user->approval_action == 'Approved' ? 'Approvato' : $user->approval_action)}}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="doc-footer">
    <p>Data: {{($data['end_date'] ? date('d/m/Y', strtotime($data['end_date'])) : date('d/m/Y'))}}</p>
</div>

</body>

</html>
