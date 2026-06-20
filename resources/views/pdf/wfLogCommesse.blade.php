<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            height: 842px;
            width: 650px;
            /* to centre page on screen*/
            margin-left: auto;
            margin-right: auto;
        }
        .invoice-box {
            max-width: 850px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;

        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        /** RTL **/
        .rtl {
            direction: rtl;
            font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        }

        .rtl table {
            text-align: right;
        }

        .rtl table tr td:nth-child(2) {
            text-align: left;
        }
    </style>
</head>

<body>
<div class="invoice-box">
    <div style="height: 900px ">
        <div style="height: 850px ">
            <table cellpadding="0" cellspacing="0">
                <tr class="top">
                    <td colspan="2">
                        <table>
                            <tr>
                                <td class="title">
                                    <img src="{{$data['logo']}}" style="width:100%; max-width:300px;">
                                </td>

                                <td>

                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="information">
                    <td colspan="2">
                        <table>
                            <tr>
                                <td>
                                    <img src="{{$data['check']}}" style="width:100%; max-width:150px;">

                                </td>

                                <td>
                                    Commessa: <b>{{$data['commessa']}}</b><br>
                                    Tipologia: <b>{{$data['tipologia']}}</b><br>
                                    Data Crezione: <b>{{date_format($data['data_creazione'],'d/m/Y')}}</b><br>
                                    File: <b>{{$data['file']}}</b><br>
                                    Stato: <b>{{$data['stato']}}</b><br>
                                    Data Approvazione: <b>{{$data['data_approvazione']}}</b><br>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr class="heading">
                    <td>
                        Approvatori
                    </td>

                    <td>
                        Data/ora Firma
                    </td>
                    <td>
                        Stato
                    </td>
                </tr>
                @foreach($data['users'] as $user)
                    <tr class="details" >
                        <td style="line-height: 0.6;">
                            {{$user->full_name}}
                        </td>

                        <td style="line-height: 0.6;">
                            {{date_format($user->created_at,'d-m-Y')}}
                        </td>
                        <td style="line-height: 0.6;">
                            {{($user->approval_action == 'Approved' ? 'Approvato':'')}}
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="" style="position: absolute;  float: right;">
            <div class="invoice-total-wrapper">
                <div class="invoice-total-item">
                    <p style="font-size: 12px; line-height: 0.6;">Il contratto è stato rilasciato</p>
                    <p style="font-size: 12px; line-height: 0.6;">secondo le modalità della Proc.</p>
                    <p style="font-size: 12px; line-height: 0.6;">3Cq-032</p>
                </div>
            </div>
        </div>
    </div>

</div>

</body>

</html>
