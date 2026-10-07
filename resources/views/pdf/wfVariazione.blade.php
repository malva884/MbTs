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
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            margin: 0;
        }

        /* Header a 3 colonne */
        .doc-header {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-header td {
            vertical-align: top;
            padding: 0;
        }

        .doc-header .company {
            width: 28%;
        }

        .doc-header .company h4 {
            margin: 0;
            font-size: 13px;
            line-height: 1.4;
        }

        .doc-header .title {
            width: 44%;
            text-align: center;
        }

        .doc-header .title h1 {
            margin: 0;
            font-size: 19px;
            line-height: 1.3;
        }

        .doc-header .doc-meta {
            width: 28%;
            text-align: right;
        }

        .doc-header .doc-meta h4 {
            margin: 0;
            font-size: 12px;
            line-height: 1.45;
        }

        .header-rule {
            border: 0;
            border-top: 2px solid #333;
            margin: 20px 0 30px;
        }

        /* Riferimento OL */
        .ref p {
            margin: 0;
            font-size: 15px;
        }

        .ref .ol-number {
            display: inline-block;
            min-width: 170px;
            margin-left: 20px;
            padding: 0 15px 3px;
            text-align: center;
            font-size: 18px;
            border-bottom: 1px solid #333;
        }

        /* Oggetto della variazione */
        .subject-label {
            margin: 32px 0 0;
            font-size: 15px;
        }

        .subject-rule {
            border: 0;
            border-top: 1px solid #333;
            margin: 10px 0 16px;
        }

        .subject-body {
            font-size: 14px;
            line-height: 1.6;
        }

        .subject-body p {
            margin: 0 0 6px;
        }

        /* Footer in basso a destra */
        .doc-footer {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 260px;
            font-size: 11px;
            line-height: 1.5;
        }

        .doc-footer p {
            margin: 0;
        }

        .doc-footer .underline {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<table class="doc-header">
    <tr>
        <td class="company">
            <h4>Metallurgica</h4>
            <h4>Bresciana S.p.a</h4>
            <h4>Ufficio Commerciale</h4>
        </td>
        <td class="title">
            <h1>Comunicazione di variazione<br>ordine di produzione</h1>
        </td>
        <td class="doc-meta">
            <h4>Mod. Var-Com</h4>
            <h4>Rev. {{$data['rev']}}</h4>
            <h4>Foglio 1</h4>
        </td>
    </tr>
</table>

<hr class="header-rule">

<div class="ref">
    <p>Rif.: Ordine di produzione n.
        <span class="ol-number">{{$data['ol']}}</span>
    </p>
</div>

<p class="subject-label">Oggetto della variazione:</p>
<hr class="subject-rule">

<div class="subject-body">{!! $data['text'] !!}</div>

<div class="doc-footer">
    <p>Il contratto è stato rilasciato</p>
    <p>secondo le modalità della Proc.</p>
    <p>3Cq-012 - Per U.C.:</p>
    <p><span class="underline">Nicoletta Piccinelli</span>&nbsp;&nbsp;Data: <span class="underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></p>
</div>

</body>

</html>
