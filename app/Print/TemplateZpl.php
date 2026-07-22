<?php

namespace App\Print;

class TemplateZpl
{
    public static function printReception(array $content)
    {
        $id = $content['Id'];
        $visitatore = $content['Visitatore'];
        $azienda = $content['Azienda'];
        $w_username = $content['Username'];
        $w_password = $content['Password'];
        $scadenza = date_format(date_create($content['Scadenza']),"Y/m/d");
        $zpl ="^XA
~TA016
~JSN
^LT0
^MNM,0
^MTD
^PON
^PMN
^LH0,0
^JMA
^PR3,3
~SD10
^JUS
^LRN
^CI27
^PA0,1,1,0
^XZ
^XA
^MMC
^PW607
^LL408
^LS0
^FO15,13^GFA,41,288,72,:Z64:eJzj4KAKYJChDmCwoQ5gEKAOYAAAavEfEQ==:24EF
^FO18,386^GFA,41,288,72,:Z64:eJzj4KAKYJChDmCwoQ5gEKAOYAAAavEfEQ==:24EF
^FT18,63^A0N,48,48^FH\^CI28^FD$visitatore^FS^CI27
^FT66,311^BQN,2,6
^FH\^FDLA,$id^FS
^FT18,93^A0N,20,20^FH\^CI28^FD$azienda^FS^CI27
^FT295,185^A0N,14,15^FH\^CI28^FDWfi Username:  ^FS^CI27
^FT295,208^A0N,14,15^FH\^CI28^FDWifi Password: ^FS^CI27
^FO31,314^GFA,2069,4096,64,:Z64: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:9C52
^FT404,189^A0N,20,20^FH\^CI28^FD$w_username^FS^CI27
^FT404,214^A0N,20,20^FH\^CI28^FD$w_password^FS^CI27
^FT357,243^A0N,11,13^FH\^CI28^FD$scadenza^FS^CI27
^FT295,243^A0N,11,13^FH\^CI28^FDExpiration^FS^CI27
^PQ1,1,1,Y
^XZ";


        //send to printer
        $zpl_ip = $content['Ip_Printer'] ?? null;
        $zpl_port = $content['Port_Printer'] ?? 9100;
        //trim zpl
        $zpl = trim($zpl);
        //Log::info("IP:" . $zpl_ip . " Barcode:" . $zpl);
        if (!empty($zpl_ip) and !empty($zpl)) {
            ZplPrinter::printer($zpl_ip, $zpl_port)->send($zpl);
        }
    }

    public static function printQuality(array $content)
    {
        $id = $content['id_instrument'];
        $SerialNumber = $content['serial_number'];
        $inspector = $content['inspector'];
        $issuingBody = $content['issuing_body'];
        $frequency = $content['frequency'];
        $months = $content['months'];
        $from = $content['from'];
        $due = $content['due'];
        $zpl ="CT~~CD,~CC^~CT~
^XA~TA000~JSN^LT0^MNM^MTD^PON^PMN^LH0,0^JMA^PR1,1~SD18^JUS^LRN^CI0^XZ
^XA
^MMT
^PW575
^LL0406
^LS0
^FO32,288^GFA,08192,08192,00064,:Z64:
eJztV79v20YUfnfS5Qg2UGjUglCAAg9uB8LNkNEoOpwQu14ZQEIWCVlaoCMNyJuKHphFU6b+AYQnIX8FGURIRw0uOoZjRgV1xwJ9xyMl2mZQoFwKlJ9J+vSOT+++9+PeCaBFixYtWrRo0eK/h3FDfdFMnZw206fN1IF1m+lbDe07QTN9oZrpn8lm+lEzddKQPlXN9Ieymb7/r7TIbhDWzvfzucI1SuEdCwtiqnwbQF+qeJHG+RuWvqAUWvoVxDJ/svXFNU8vps9ZMFuPJ5L8lbDfZgDBuJ8Gk8s0YGs5ZnI8YXIyuoYpsPdyIrWi2RmspbBodGRRy/KvMN3QpK2rToAQlohRrAQF8RW1hIgsOMI5lRclFbn+webS8brzIXe+uLhmT2EAcgFEsn43+MEZbU6dg83FA+diQpwR+cUl38EhyL6233dy/SPl+z1rZffgOBY2hR6oTFP2H6oPJ5H61Yf4qidEj6o3sLTgFS4ssbQrLFOa5+lNuHi2nQ7lz4l0PViQdQI8OZx75OaSk+3FeXrtyu9dvn7LNi5zcWlJX1MfG/4U4ngFyj+GL5X2/wo6Ou1E7NM4tqmKaRRbaunbHUUVegm0hzR1Q594nTT9+DQNfyJ/EB66oy33AO2PkpCnW5eT9YBvh2QTejx4AU+633IgiaNNB4Y/Oj/KqMpW9E1kZb7KLJ1zaCujUeb36LJnK59mOFSvwLf8ByV1ZfhPvfPzt49Y+ue7d+NJ8vE8Gc+1mI9+93gSvuDjqScXPE0Ws7MphEHoauoYACIN/xOb2kv0exxR6KgrzVyLbRpp8r4t0Ce5H45xYbGZ1PlBywyfdz933CFJZ51nA3LDydONlnucYwTS0BuFC7L1+ChZHIRkO9rqinCIBAaG/yq2InHsY+5TtYTlA9uU/De2lfXoVWyr7EeqHvcihUGiSZSJvAIU3jl/dibZSE7GjAQsGYPLu490cgFKkimbSLZOAp4s3Cm76QNPz9MEJw8hwPtuZ6R5NIri2kHpihSmFDtRR5kqFHAfTDPrggf9yqaHTMEjW50SAAM+6Gihk6/AuaNftBofjle3J3xA3UdouGf1qHnTeKACWfzvwoJdT8x6ChlZoG0yw09Td5q3wzEwXNct/vHOCTEtYlu2XhrbYHZCHxOwhrhGudHxZ1s3J43DQtQPnxcvzJ25Y/iTLtzjn+OzZRYXW11p38fEB8jw/iA+CMN//7zDf/YeI9zNP5Wdf7LGnNch0OmXz/QnGrUnI/H1S/SsqIoiXZE6K2n8MjYrOzrCS9zXBrIZIG1HVkSch6FpCTzlKezt17VGqnoPcRHVr8YNOCsmIxpVmNeFgidTJCur9sezPOcR3ql3ZkRSo46/DSfadtW+OCqbsG3Z/3Qc8iB8glwr9omcl4ngOq6JuVNe9/AQMnFHpJa9YuSLoh1/mr/LEomBr9hn7NothnM5Nz4/BDitPxmfdJTZXnagVDwphrGK9+IaZYTD0bZDKpJuXxa1QVKSmoU5ef3V8T+xdOOtfjmGv1gNjTrFqKb+CwS4x7OEVyTOZXkE5KfchB+Zm7+a9efWb5HbrQbDvxfW6GocBIDN1a1I+qS0sws/Oghfq+WvO7u6deras8Twi1Kmdv3vNob5s1pZ+/P/ZTKXZnSIWyML6vibVFM1MwBXEJcT2iWi7p2BfvC6GSBr8q4Y5v1/Usc/P+7XFxld0mUxzM8/dbVIEqRO6n/y8YCXE/nWU7f/UfA/eea21W5brJ7/7mAQHFzWquPWMCyHt86/d/D6db159Iq/H2qsal9jn/rBe6jLvhxqNP1l3KJFixYtWrT4P+Jvwn09aQ==:F044
^FO0,2^GB573,401,1^FS
^FO0,302^GB573,0,1^FS
^FT541,312^AAI,18,10^FB517,1,0,C^FH\^FDQuality System Metallurgica Bresciana Dello^FS
^FT565,283^ADI,18,10^FH\^FDID instrument:^FS
^FO371,247^GB0,56,1^FS
^FT195,286^ADI,18,10^FH\^FDSerial Number:\09\09^FS
^FO2,247^GB571,0,1^FS
^FT565,228^ADI,18,10^FH\^FDInspector:^FS
^FO1,187^GB571,0,1^FS
^FT564,170^ADI,18,10^FH\^FDIssuing Body:^FS
^FO1,130^GB571,0,1^FS
^FT564,108^ADI,18,10^FH\^FDFrequency:^FS
^FT184,108^ADI,18,10^FH\^FDmonths^FS
^FO1,85^GB571,0,1^FS
^FT565,65^ADI,18,10^FH\^FDFrom:^FS
^FO290,1^GB0,85,1^FS
^FT283,65^ADI,18,10^FH\^FDDue:^FS
^FT559,257^A0I,20,19^FH\^FD$id^FS
^FT362,257^A0I,20,19^FH\^FD$SerialNumber^FS
^FT559,198^A0I,20,19^FH\^FD$inspector^FS
^FT559,139^A0I,20,19^FH\^FD$issuingBody^FS
^FT433,102^A0I,20,19^FH\^FD$frequency^FS
^FT98,102^A0I,20,19^FH\^FD$months^FS
^FT559,28^A0I,20,19^FH\^FD$from^FS
^FT275,28^A0I,20,19^FH\^FD$due^FS
^PQ1,0,1,Y^XZ";


        //send to printer
        $zpl_ip = $content['Ip_Printer'] ?? null;
        $zpl_port = $content['Port_Printer'] ?? 9100;
        //trim zpl
        $zpl = trim($zpl);
        //Log::info("IP:" . $zpl_ip . " Barcode:" . $zpl);
        if (!empty($zpl_ip) and !empty($zpl)) {
            ZplPrinter::printer($zpl_ip, $zpl_port)->send($zpl);
        }
    }

    public static function printAsset(array $content)
    {
        $serialNumber = $content['serial_number'] ?? 'N/A';
        $assetTag = $content['asset_tag'] ?? 'N/A';
        $model = $content['model'] ?? '';
        $matricola = $content['matricola'] ?? '';

        // QR code data: serial_number;model;matricola
        $qrData = $serialNumber;
        if ($model) {
            $qrData .= ';' . $model;
        }
        if ($matricola) {
            $qrData .= ';' . $matricola;
        }

        $zpl ="CT~~CD,~CC^~CT~
^XA~TA000~JSN^LT0^MNW^MTT^PON^PMN^LH0,0^JMA^PR5,5~SD30^JUS^LRN^CI0^XZ
^XA
^MMT
^PW559
^LL0400
^LS0
^FO224,256^GFA,07040,07040,00044,:Z64:
eJztWL1uo0AQXlaHdKKwLgV95JLCD3CVC19PAe+DrspjoFQWkVKfyMu4jFzwDLd/87e74Et5EmMlXpjl4+NjdmbWSu22227/j73w8SBcv/rLWZzohI97mmVZ7iM7oZeR+e4Tc9W9sZadKPuz8DHXPNkPo1XNVxxZ35VcvTPGuMa7lM5F9PXi7E5zT3CgT96H9Is+uljRQR/dU0/eiLA5kC4kXMQXE27ZR/cMfBlhcwDEF0m4iC9WKGndR/fUczAkbMY48AaEAZdeD94DPHhPHZPSwBc9wJ9wW8LtpYdwp1hF0Bc9KH4RX4y4JeLCs+iYFOpbEd+BYKQQ67ghfs0HLjZjj4seDJbk7QBOneLiW79F+lrH/XjifIst3PA8gKuGYXjjAoO+jR80IrgDNrw4htsGORS3dxbBjO9n4M9xHTe2yjiu/Ra41YxRi/FrvsfgE3OVwO3glJOm5IvcknpHVqgvxLTmyQMIR3xR8rqVcytSEfTFM9Ug55b04hLc2DQFRMw3seILuDZqRxxFZ2KjgHiMG96+1PczP7fO4bb5uQ0GMNN3ys8tc7hdfu4B2fH1Nq7hUnqEJ1ghbKL0jx9B/P4UWXkNt0PcvMIi7SJfWanBiizfaKmBoZowqOLCt4FbynTEDTMwDA5JpUZDbnG9yMw1eTbwDfGrQ/a9pnNXcTNBkeibVL4NXKwk52TuNL0NYRCEbuLKt4Gb9A+c7yCIU4H7B9y0BQCb548hDFgmnrNBzHCBYL0iBJYFTQSh9N0e80XCyapL9fUpIidwBhcUTnBTfUMZyYRwDjfpVNV393+egr4Qv9bMmpv58RauB2YdsF+qGX2NHd9FQBRPG7iiUltOo6OZ0VeFLDHA0Te/vVjBlX1J43Fz+lo78f7haZOv7EvC+87EryfMA7hL+IoFVjBc045avrn4VUrJfqd4wJfjVtOmvqyPsPJu68txm2lbX8X4Pn2BL9BL47e6wjHgXi6XFdyyi3A14Kb6nlzmZfoaeX+s4PqEI3EDrURf79FM3yBvtl5YwqXYt1wFX1E4Rh+/6hHfwmecOoPr4nf+cAn3w3wGt7N74Z2xunTEl21V+nN+nwXshgNtgZYb7ZQgnwm+NcNtCRfzJNcXKppLurR/w/zL44HB9l0Wl+Jh4UZ8bzCXx+8K7hnmVuF12/ilHZvtJpKdnVhvAjfdMeI6Nrx+r/BF3GKFL6nN0hDLDxO3XP+wyheio01wTZwqwRcbnhvNDXxr/uSeZSwD1gvT7tIvDj5op6R9CPXC4tYSN6magGt/1jkxuncox7wah/pWmOfl6wL2sLLb8fGgDXzF5B39O52uA58bfiKDTTZ/+nivqZ7919H+vaK5c/r4+qx222233bbtLwyBL78=:C952
^FT17,415^BQN,2,7
^FH\^FDLA,$qrData^FS
^FT548,126^A0I,25,24^FH\^FDS.no: ^FS
^FT487,126^A0I,25,28^FH\^FD$serialNumber^FS
^FT548,95^A0I,25,26^FH\^FDItaly^FS
^FT444,95^A0I,25,24^FH\^FD$model^FS
^FT380,59^A0I,20,19^FH\^FD+39 - 030 - 9771911^FS
^FT447,32^A0I,23,24^FH\^FDSterlite Technologles Limited^FS
^BY1,3,25^FT492,229^BCI,,Y,N
^FD>$serialNumber^FS
^PQ1,0,1,Y^XZ";

        //send to printer
        $zpl_ip = $content['Ip_Printer'] ?? null;
        $zpl_port = $content['Port_Printer'] ?? 9100;
        //trim zpl
        $zpl = trim($zpl);
        if (!empty($zpl_ip) and !empty($zpl)) {
            ZplPrinter::printer($zpl_ip, $zpl_port)->send($zpl);
        }
    }
}
