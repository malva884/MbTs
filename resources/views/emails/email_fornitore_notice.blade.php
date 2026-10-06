<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="ltr" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns="http://www.w3.org/1999/xhtml" lang="it">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no" />
    <title>Comunicazione Ufficiale - Metallurgica Bresciana S.p.A.</title>
    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #F4F6F9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2D3748; }

        .notice-content p { margin: 0 0 14px 0; line-height: 1.6; font-size: 15px; color: #374151; }
        .notice-content ul, .notice-content ol { margin: 0 0 14px 20px; padding: 0; }
        .notice-content li { margin-bottom: 6px; line-height: 1.6; font-size: 15px; color: #374151; }
        .notice-content strong { color: #111827; }
        .notice-content a { color: #00406C; text-decoration: underline; }

        @media only screen and (max-width: 620px) {
            .wrapper-table { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding-left: 20px !important; padding-right: 20px !important; }
            .header-padding { padding-left: 20px !important; padding-right: 20px !important; }
            .btn-table { width: 100% !important; }
            .btn-link { display: block !important; width: auto !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F4F6F9;">
    <!-- Preview Text nascosto per client email -->
    <div style="display: none; font-size: 1px; color: #F4F6F9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
        Comunicazione ufficiale da Metallurgica Bresciana S.p.A.: {{ $notice->titolo }}
    </div>

    <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: #F4F6F9; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 30px 10px 40px 10px;">
                <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="600">
                <tr>
                <td align="center" valign="top" width="600">
                <![endif]-->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="wrapper-table" style="max-width: 600px; background-color: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.06); border: 1px solid #E2E8F0;">
                    <!-- Barra superiore brand -->
                    <tr>
                        <td style="background-color: #00406C; height: 5px; line-height: 5px; font-size: 1px;">&nbsp;</td>
                    </tr>

                    <!-- Header Logo -->
                    <tr>
                        <td align="center" class="header-padding" style="padding: 30px 40px 24px 40px; background-color: #FFFFFF; border-bottom: 1px solid #EDF2F7;">
                            <a href="https://www.metallurgicabresciana.it" target="_blank" style="text-decoration: none; display: inline-block;">
                                <img src="https://www.metallurgicabresciana.it/assets/img/logo18.png" alt="Metallurgica Bresciana S.p.A." width="240" style="display: block; width: 240px; max-width: 100%; height: auto; border: 0;" />
                            </a>
                        </td>
                    </tr>

                    <!-- Pre-header badge -->
                    <tr>
                        <td align="left" class="content-padding" style="padding: 28px 40px 0 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="background-color: #EBF4FA; border: 1px solid #BEE3F8; border-radius: 4px; padding: 4px 10px;">
                                        <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #00406C; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            Portale Fornitori &bull; Comunicazione Ufficiale
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Titolo e Saluto -->
                    <tr>
                        <td align="left" class="content-padding" style="padding: 18px 40px 10px 40px;">
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; line-height: 1.35; font-weight: 700; color: #001523; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                {{ $notice->titolo }}
                            </h1>
                            <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #4A5568;">
                                Spettabile <strong>{{ !empty($supplier->ragioneSociale) ? $supplier->ragioneSociale : 'Fornitore' }}</strong>,
                            </p>
                        </td>
                    </tr>

                    <!-- Corpo Avviso -->
                    <tr>
                        <td align="left" class="content-padding" style="padding: 12px 40px 20px 40px;">
                            <div class="notice-content" style="font-size: 15px; line-height: 1.6; color: #374151;">
                                {!! $notice->testo !!}
                            </div>
                        </td>
                    </tr>

                    @if(!empty($notice->scadenza))
                    <!-- Box Scadenza -->
                    <tr>
                        <td align="left" class="content-padding" style="padding: 0 40px 24px 40px;">
                            @php
                                try {
                                    $dataScadenza = \Carbon\Carbon::parse($notice->scadenza)->format('d/m/Y');
                                } catch (\Throwable $e) {
                                    $dataScadenza = $notice->scadenza;
                                }
                            @endphp
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: #FFFBEB; border-radius: 4px; border: 1px solid #FDE68A; border-left: 4px solid #F59E0B;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
                                            <tr>
                                                <td valign="top" width="24" style="padding-right: 10px; font-size: 16px; line-height: 1;">
                                                    &#9200;
                                                </td>
                                                <td valign="middle">
                                                    <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #92400E; display: block;">Termine Richiesto</span>
                                                    <span style="font-size: 15px; font-weight: 700; color: #78350F;">Entro il {{ $dataScadenza }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Pulsante Call to Action -->
                    <tr>
                        <td align="center" class="content-padding" style="padding: 8px 40px 32px 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" role="presentation" class="btn-table">
                                <tr>
                                    <td align="center" bgcolor="#00406C" style="border-radius: 6px;">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://suppliers.metallurgicabresciana.it/build/login" style="height:46px;v-text-anchor:middle;width:290px;" arcsize="13%" stroke="f" fillcolor="#00406C">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold;">
                                                Accedi al Portale Fornitori &rarr;
                                            </center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-- -->
                                        <a href="https://suppliers.metallurgicabresciana.it/build/login" target="_blank" class="btn-link" style="background-color: #00406C; border-radius: 6px; color: #FFFFFF; display: inline-block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 600; line-height: 46px; text-align: center; text-decoration: none; width: auto; padding: 0 32px; -webkit-text-size-adjust: none; mso-hide: all;">
                                            Accedi al Portale Fornitori &rarr;
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Box Assistenza & Informazioni -->
                    <tr>
                        <td align="left" class="content-padding" style="padding: 0 40px 30px 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 700; color: #1E293B; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Hai bisogno di assistenza?
                                        </p>
                                        <p style="margin: 0; font-size: 13px; line-height: 1.55; color: #64748B;">
                                            Per informazioni o supporto tecnico potete consultare la nostra
                                            <a href="https://suppliers.metallurgicabresciana.it/documenti/Suppliers_Portal_IT-IT.pdf" target="_blank" style="color: #00406C; font-weight: 600; text-decoration: underline;">guida al portale (PDF)</a>
                                            oppure contattare il team Qualità all'indirizzo
                                            <a href="mailto:certificazioni.metallurgica@stl.tech" style="color: #00406C; font-weight: 600; text-decoration: underline;">certificazioni.metallurgica@stl.tech</a>.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Chiusura formale -->
                    <tr>
                        <td align="left" class="content-padding" style="padding: 0 40px 32px 40px;">
                            <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #4A5568;">
                                Cordiali saluti,<br />
                                <strong style="color: #001523;">Metallurgica Bresciana S.p.A.</strong><br />
                                <span style="font-size: 13px; color: #718096;">Dipartimento Qualità &amp; Sostenibilità Fornitori</span>
                            </p>
                        </td>
                    </tr>
                    <!-- Footer Aziendale -->
                    <tr>
                        <td align="center" class="content-padding" style="background-color: #0F172A; padding: 26px 40px; color: #94A3B8; border-top: 1px solid #1E293B;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
                                <tr>
                                    <td align="center" style="padding-bottom: 12px;">
                                        <p style="margin: 0; font-size: 13px; font-weight: 700; color: #F1F5F9; letter-spacing: 0.5px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            Metallurgica Bresciana S.p.A. <span style="color: #64748B; font-weight: 400;">&bull; An STL Company</span>
                                        </p>
                                        <p style="margin: 4px 0 0 0; font-size: 12px; line-height: 1.5; color: #94A3B8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            Viale G. Marconi, 31 &bull; 25020 Dello (BS), Italia
                                        </p>
                                        <p style="margin: 2px 0 0 0; font-size: 11px; line-height: 1.5; color: #64748B; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            P.IVA / C.F. 01487690175 &bull; <a href="https://www.metallurgicabresciana.it" target="_blank" style="color: #94A3B8; text-decoration: underline;">www.metallurgicabresciana.it</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="border-top: 1px solid #1E293B; padding-top: 14px;">
                                        <p style="margin: 0; font-size: 10px; line-height: 1.45; color: #64748B; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            Questa comunicazione e gli eventuali allegati sono riservati e destinati esclusivamente al destinatario indicato. Se avete ricevuto questo messaggio per errore, vi preghiamo di cancellarlo immediatamente e di darne tempestiva comunicazione al mittente.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
