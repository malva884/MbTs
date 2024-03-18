<!DOCTYPE html>
<!--html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Email</title>
</head>
<body>
<p>
    Dear,
    <br><br>
    Berikut QR Code yang bisa anda gunakan untuk absensi:<br>
<div class="row mt-5">
    <div class="col">
        <p class="mb-0">With Image</p>
        <tr>
            <td><img src="https://www.metallurgicabresciana.it/qrcode_portale/{{$output_file}}"></td>
        </tr>
    </div>
</div>

</p>
</body>
</html -->

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
      xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <title>
    </title>

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
        #outlook a {
            padding: 0;
        }

        .ReadMsgBody {
            width: 100%;
        }

        .ExternalClass {
            width: 100%;
        }

        .ExternalClass * {
            line-height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        p {
            display: block;
            margin: 13px 0;
        }
    </style>

    <style type="text/css">
        @media only screen and (max-width: 480px) {
            @-ms-viewport {
                width: 320px;
            }
            @viewport {
                width: 320px;
            }
        }
    </style>

    <style type="text/css">
        @media only screen and (min-width: 480px) {
            .mj-column-per-100 {
                width: 100% !important;
            }
        }
    </style>
    <style type="text/css">
    </style>
</head>
<body style="background-color:#f9f9f9;">
<div style="background-color:#f9f9f9;">

    <div style="background:#f9f9f9;background-color:#f9f9f9;Margin:0px auto;max-width:600px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
               style="background:#f9f9f9;background-color:#f9f9f9;width:100%;">
            <tbody>
            <tr>
                <td style="border-bottom:#daa520 solid 5px;direction:ltr;font-size:0px;padding:20px 0;text-align:center;vertical-align:top;">

                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div style="background:#fff;background-color:#fff;Margin:0px auto;max-width:600px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
               style="background:#fff;background-color:#fff;width:100%;">
            <tbody>
            <tr>
                <td style="border:#daa520 solid 1px;border-top:0px;direction:ltr;font-size:0px;padding:20px 0;text-align:center;vertical-align:top;">

                    <div class="mj-column-per-100 outlook-group-fix"
                         style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:bottom;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation"
                               style="vertical-align:bottom;" width="100%">
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                    <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                                           style="border-collapse:collapse;border-spacing:0px;">
                                        <tbody>
                                        <tr>
                                            <td style="width:364px; ">
                                                <div style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;font-size:15px;font-weight:bold;line-height:1;text-align:center;color:#555;">
                                                    <a href="{{env('APP_URL')}}" style="text-decoration: none;">
                                                        <h1 style="color: #7367f0;"> {{env('APP_NAME')}}</h1>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td align="center"
                                    style="font-size:0px;padding:10px 25px;padding-bottom:15px;word-break:break-word;">

                                    <div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:16px;line-height:22px;text-align:left;color:#555;">
                                        Ciao,<br>
                                        Di seguito gli account da creare.
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td align="center"
                                    style="font-size:0px;padding:10px 25px;padding-bottom:40px;word-break:break-word;">
                                    @foreach($users as $user)
                                        <table align="center" border="0" cellpadding="1" cellspacing="1" role="presentation"
                                               style="border-collapse:collapse;border-spacing:1px;">
                                            <tbody>
                                            <tr >
                                                <td style="width:364px;">
                                                    <div class="mb-2" style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;font-size:15px;font-weight:bold;line-height:1;text-align:center;color:#555;">
                                                        {{$user['nome']}}
                                                    </div>
                                                </td>
                                                <td style="width:364px; ">
                                                    <div style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;font-size:15px;font-weight:bold;line-height:1;text-align:center;color:#555;">
                                                        {{$user['email']}}
                                                    </div>
                                                </td>
                                                <td style="width:364px; ">
                                                    <div style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;font-size:15px;font-weight:bold;line-height:1;text-align:center;color:#555;">
                                                        {{$user['inizio']. (!empty($user['fine']) ? ' / '.$user['fine']:'')}}
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>

                                    @endforeach
                                </td>
                            </tr>
                            <tr>
                                <td align="left"
                                    style="font-size:0px;padding:10px 25px;word-break:break-word;padding-bottom:50px">

                                    <div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:14px;line-height:22px;text-align:center;color:#555;">
                                        Per accedere al portale clicca <a href="{{env('APP_URL')}}"
                                                                          style="color:#2F67F6">qui</a>.
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">

                                    <div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:10px;line-height:22px;text-align:center;color:#555;">
                                        Si prega di non rispondere direttamente a questa email. By <a href="{{env('APP_URL')}}"
                                                                                                      style="color:#2F67F6">Metallurgica
                                            Bresciana.</a>
                                    </div>

                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div style="Margin:0px auto;max-width:600px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
            <tbody>
            <tr>
                <td style="direction:ltr;font-size:0px;padding:20px 0;text-align:center;vertical-align:top;">
                    <div class="mj-column-per-100 outlook-group-fix"
                         style="font-size:13px;text-align:left;direction:ltr;display:inline-block;vertical-align:bottom;width:100%;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                            <tbody>
                            <tr>
                                <td style="vertical-align:bottom;padding:0;">
                                    <table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
                                        <tr>
                                            <td align="center" style="font-size:0px;padding:0;word-break:break-word;">
                                                <div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:12px;font-weight:300;line-height:1;text-align:center;color:#575757;">
                                                    Viale G. Marconi, 31 25020 Dello - Brescia
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                </td>
            </tr>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>





