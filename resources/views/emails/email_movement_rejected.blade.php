<html dir="ltr" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="telephone=no" name="format-detection">
    <title></title>
</head>
<body class="body">
<div dir="ltr" class="es-wrapper-color">
    <table width="100%" cellspacing="0" cellpadding="0" class="es-wrapper">
        <tbody>
        <tr>
            <td valign="top" class="esd-email-paddings">
                <table cellpadding="0" cellspacing="0" align="center" class="es-header">
                    <tbody>
                    <tr>
                        <td align="center" class="esd-stripe">
                            <table bgcolor="#ffffff" align="center" cellpadding="0" cellspacing="0" width="600" class="es-header-body">
                                <tbody>
                                <tr>
                                    <td align="left" class="esd-structure es-p20">
                                        <table cellpadding="0" cellspacing="0" width="100%">
                                            <tbody>
                                            <tr>
                                                <td align="center" class="esd-block-image es-p10b" style="font-size:0px">
                                                    <a target="_blank">
                                                        <img src="https://www.metallurgicabresciana.it/assets/img/logo18.png" alt="" width="300" title="" style="display:block;font-size:12px">
                                                    </a>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <table cellpadding="0" cellspacing="0" align="center" class="es-content">
                    <tbody>
                    <tr>
                        <td align="center" class="esd-stripe">
                            <table bgcolor="#ffffff" align="center" cellpadding="0" cellspacing="0" width="600" class="es-content-body">
                                <tbody>
                                <tr>
                                    <td align="left" class="esd-structure es-p15t es-p20r es-p20l">
                                        <table cellpadding="0" cellspacing="0" width="100%">
                                            <tbody>
                                            <tr>
                                                <td width="560" align="center" valign="top" class="esd-container-frame">
                                                    <table cellpadding="0" cellspacing="0" width="100%">
                                                        <tbody>
                                                        <tr>
                                                            <td align="center" class="esd-block-text es-p15t es-p15b">
                                                                <h1 style="font-size:28px;line-height:130% !important;color:#cc0000">
                                                                    Movimento Magazzino Rifiutato
                                                                </h1>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="center" class="esd-block-text es-p10t">
                                                                <h3 style="text-align:center">
                                                                    <em>il seguente movimento &egrave; stato rifiutato da {{ $info['approver'] }}:</em>
                                                                </h3>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" class="esd-structure es-p20">
                                        <table cellpadding="0" cellspacing="0" width="100%">
                                            <tbody>
                                            <tr>
                                                <td width="560" align="left" valign="top">
                                                    <table cellpadding="8" cellspacing="0" width="100%" style="border-collapse:collapse;font-family:tahoma,verdana,segoe,sans-serif;font-size:14px">
                                                        <tbody>
                                                        <tr style="background-color:#efefef">
                                                            <td style="border:1px solid #cccccc"><strong>Documento Materiale</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->documento_materiale }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="border:1px solid #cccccc"><strong>Tipo Movimento</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->tipo_movimento }}</td>
                                                        </tr>
                                                        <tr style="background-color:#efefef">
                                                            <td style="border:1px solid #cccccc"><strong>Materiale</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->materiale }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="border:1px solid #cccccc"><strong>Descrizione</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->descrizione }}</td>
                                                        </tr>
                                                        <tr style="background-color:#efefef">
                                                            <td style="border:1px solid #cccccc"><strong>Quantit&agrave;</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->quantita }} {{ $movement->um }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="border:1px solid #cccccc"><strong>Importo</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->importo }} &euro;</td>
                                                        </tr>
                                                        <tr style="background-color:#efefef">
                                                            <td style="border:1px solid #cccccc"><strong>Data Documento</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->data_documento }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="border:1px solid #cccccc"><strong>Utente SAP</strong></td>
                                                            <td style="border:1px solid #cccccc">{{ $movement->user }}</td>
                                                        </tr>
                                                        @if(!empty($info['comment']))
                                                            <tr style="background-color:#efefef">
                                                                <td style="border:1px solid #cccccc"><strong>Motivazione</strong></td>
                                                                <td style="border:1px solid #cccccc">{{ $info['comment'] }}</td>
                                                            </tr>
                                                        @endif
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <table cellpadding="0" cellspacing="0" align="center" class="es-footer">
                    <tbody>
                    <tr>
                        <td align="center" class="esd-stripe">
                            <table align="center" cellpadding="0" cellspacing="0" width="600" class="es-footer-body" style="background-color:transparent">
                                <tbody>
                                <tr>
                                    <td align="left" class="esd-structure es-p20t es-p20b es-p20r es-p20l">
                                        <table cellpadding="0" cellspacing="0" width="100%">
                                            <tbody>
                                            <tr>
                                                <td width="560" align="center" class="esd-container-frame">
                                                    <table cellpadding="0" cellspacing="0" width="100%">
                                                        <tbody>
                                                        <tr>
                                                            <td align="center" class="esd-block-text es-p35b">
                                                                <p>
                                                                    © Metallurgica Bresciana S.p.A., Inc. All Rights Reserved.
                                                                </p>
                                                                <p>
                                                                    Viale G. Marconi, 31 25020 Dello - Brescia
                                                                </p>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="center" class="esd-block-text es-infoblock">
                                                                <p style="color:#3d85c6">
                                                                    Questa è un'email automatica, non è prevista la possibilità di rispondere.
                                                                </p>
                                                            </td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>
