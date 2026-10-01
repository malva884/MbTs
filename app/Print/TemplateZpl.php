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
~TA008
~JSN
^LT0
^MNW
^MTT
^PON
^PMN
^LH0,0
^JMA
^PR4,4
~SD15
^JUS
^LRN
^CI27
^PA0,1,1,0
^XZ
^XA
^MMC
^PW432
^LL687
^LS0
^FO32,11^GFA,61,2568,4,:Z64:eJwrYGBgUGDABAlA/ACIC0blR+VH5UflR+VH5UflR+VH5YelPAAwsJGR:6F4C
^FO412,11^GFA,61,2572,4,:Z64:eJx7wMDAUADECgyYIAGIH4zKj8qPyo/Kj8qPyo/Kj8qPyg9beQD7rJJx:4CBF
^FT82,653^A0B,48,56^FH\^CI28^FD$visitatore^FS^CI27
^FT139,616^BQN,2,6
^FH\^FDLA,$id %3BH%3A%3B%3B^FS
^FT112,644^A0B,20,23^FH\^CI28^FD$azienda^FS^CI27";
        if(!empty($w_username)){
            $zpl.="^FT204,364^A0B,14,18^FH\^CI28^FDWfi Username:  ^FS^CI27
^FT228,364^A0B,14,18^FH\^CI28^FDWifi Password: ^FS^CI27
^FT208,239^A0B,20,23^FH\^CI28^FD$w_username^FS^CI27
^FT234,239^A0B,20,23^FH\^CI28^FD$w_password^FS^CI27";
        }

        $zpl.="^FT266,275^A0B,14,18^FH\^CI28^FD$scadenza^FS^CI27
^FT266,364^A0B,14,18^FH\^CI28^FDExpiration^FS^CI27
^FO336,75^GFA,1781,4239,9,:Z64:eJzdlztvG0cQx2cf4q1MmneCg+gcEPSxSZrAoLsrCGNlJ1ETpA6CFAd/ApYsVCwhIQ8gEFSqCvwRXKRw4eIipNDHuAApUl6RQlWQ2dkZEjBlIAlSCKEA4SfpbuY/j50dAQBYB+kzsAyZZjBrBtUG/lPHYJeeYVExTAuGnEFnF2xHdwxKDIbwlmXo2CAs2SCsBBZsEMQFiAsYsmUQ8aDfhk0UditeoGanerIBdqrGApnIWLcMIhlEsnnJsLeRsxSoRc5EYAxiWeycCqzFtBdfVSMgMiT0O1a4gRQuk8KZv1M4K+IHIj4TX9soRLwS8fCaxcPXAl+I5Y8EHkgUTnz9wn/ZuxL4mUFvnEp1lVRXBXHaiq9OYCW+3h2pl0griXT2hzz7J4N7xGBz0WNEhmKA0AqIHt8wSG9kBYP9/iXDp+zdPuPX9YxfMjNxUQhciOVrgd/Z4PYw1u8OOZtzd8aQqTtjyASafwDFPxiBLDaSyjo8O+hUXd6A7aP37iks6ui0zWERT6WqsvDBGO3rwgSVIdhStXB6QUehg6Po1EJDdXMaKohvFwrmJKpQ4YDyXUD7/qMYcQWNMx1BXaqeYFLCimBcwoIgK2Eacw3mkqrjQd3YLNDh76lMCCuNSiJMNSTIFSqJkK0JVG+6gIK8GameYGDgxmPGvIVwUqFEPwL/tIjgoMpLhMrBPPshQWmu5gQj9WtHYIE0u3j0YlwW81knMDBJoGCEUGrs4kFKC4Q4dhymvzUIowixx1AFyiXw+NXG6viUzZjLJkahMHmUVhUc55cg5hfDSNAYhgpCggJ8RW2A4SSwMTUudUAClBBSq0Q1BHPDzTMaMJgbBoDbQPpwsDNt8krAM8QK/n8+d+wu+GeXuNncBf/NfB7IfM5kPt+Vmwg2N9GOr21WxZeRrNqGfdm5QMm+7IhBS89rw61gzHdsUF0zhN/YdPsTQ/cVQ/OYYfkhQ/2AYXLAOsYHbYK84DD2ulfsFS7ZB3wu4XxGgOP9OT/r04WD470gQzjeCwoIx7ujOHC8O4oDx7tNAZ1e6DjgAMe7crAfaAA7cD7OF7iG+CI+WUN8Md05jtOL31N6teZSIqRSKiVNG4JK6fUBXpEI7+FLArxVPyYReKseRhigj2Es5f5ZYW3MgXvmkgs3swwHZ8mXdVeGDoi1LxSdC62fQJ/gYWobpe5z24RvwmPy5U9Daht/1Ka2qaoutU1RLVPbuKJObWOLSWob7Q7TuVB2mE6lOttrKS64snwcXmg+lU8UH4eHcJ7gPhwnGISjLhUwpGcxT9LFnQyZpXRvLd07ke49lOM5lOEgMkBkgMgAtTmV5wLHAiyDqpw+d2LvrdZseR541pWBwxkHnnVZ4LhMYBeqFRedTJtexL8W8W80R/uj4mjz6LREGMYcvgeQdqR7UWqPT+5H09HpXtT0Jm5ELaRk6gbhGMPRuDTADJ2qMu6H0dE5Pkez4VuETyI8D7gfRpi1vB/iCaf9sHLLtB8Wrk77YWknaT8c6Tzth/cUXqYr0nPa0n5oYxfFJFhf9TRydVOcQI5aVO2mWBWECa5TFPu5zgLl+VgZWkVQz7qnRpq1fkVzvujmi7TYLXFZrqiOuIURTLWhHtV48PoICg/eCYW6bptpTAD4vqBVD6oVt0+x4Aq4KVfA5lxttNemuinp2bbnAm7fn8r7ubyfcftp1fH7oW/Sr7x0XSVdV0izOdUlGAB/pOlu+U9z9zhs1x+7A5v/hdUO3GJ618cuFP8GRpvLt9oBcZqjnr8ACQc9Ww==:1B01
^FO328,572^GFA,389,496,8,:Z64:eJxdkbFKA0EQhv9xwOtuX0CMb2AbRBILXyFY+wbpTCGyj2KZ0tIqnL3tdRYjabYKIxJc5ERndk8Edw8+/mNnduf/+bus3FQO/Kv3Vzf7xWJggX+5UZCCB86ghJApuw4KoyAIbl1POmwAwSZi4voOCNHqP52kVuRMnADuSBsxCkm4h3UhDRFAgsycS9JzA6YsU+cRqTpbSuI8pLfOeUBbPw6mDxQdd4X09FKI7WPl9bLyZD3qh8rX9Xj+uZDjbuz3XnTLtX873mf3p/E9q/I+kjPnivTYmSGtFdi02kSfzzfQ2LzmVJkf1QfFAJxG+ztD9WuO6t88Vj9DZ9ax+0uD60ar/5y55EGej3e1vPr+q+//8vuXJy7LuvgBEYyjDQ==:E554
^PQ1,1,1,Y
^XZ";


        //send to printer
        $zpl_ip = $content['Ip_Printer'];
        //trim zpl
        $zpl = trim($zpl);
        //Log::info("IP:" . $zpl_ip . " Barcode:" . $zpl);
        if (!empty($zpl_ip) and !empty($zpl)) {
            ZplPrinter::printer($zpl_ip)->send($zpl);
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
^XA~TA000~JSN^LT0^MNM^MTD^PON^PMN^LH0,0^JMA^PR3,3~SD20^JUS^LRN^CI0^XZ
^XA
^MMT
^PW575
^LL0406
^LS0
^FO64,288^GFA,07680,07680,00060,:Z64:
eJztVs9qIzcY/yTPICGKsWFELjOMcC8mlLKHHsxCYQzp+lQ6gYS9JJBHmEB884LwHmr23AdQfQrbF9jjTNmQawy77+F9g37SKImznrQwvRQ6v8SO8s389On3/ZEE0KFDhw4dOnTo0OH/gKw9lar2XDloz+XtqVBk7blGt6aSm/Zu6b/Qy07ac4Vuz03bU2HcnkqK9lxq/uGFA0myKJIhyQ+meQTkCuQcYBChQX4KcSAhjySBiGQkA5nJDElh7rgCtB5TxTXnf/IRiBWMqcZC5tqM1mjFwUoAaIpWA2PDtWW5L5hNo7KYTYcfv7n6mLyC8yC8gxzOj/KjfHY7rM6Phjfxq2lU/RRfhBekzEPnN6tFvTXavKPfrrgxQsAEKIACQeF6JKhair7SnL5VS6oUVxS4a2mhXCwP2C2pUjbL002eJGQbJBjfMEnJl3nCZuGPyfS3Ycqmn1lWyELCQlqpstbL6ZJqLqgeo+4xGI5JRV0CzGosKB9zqhW+owVcQMnhmj/WeBCzlAVXMcvmVbi9ZOVwi207KOKwuiviJC8WLLs6S1l+BuW0zKEIImTltd6JQJVqIuD3JTfrnh4Zq9dMMA5mIpT5IPS1EmI5Ad3TCh9Ry3J6oVj8sDjeFsnlJjrefD4gr0vMLtlcHrDq7urNdLNNybZYJLIg1W1VunwDyWq9RqmV1mY80itqMylc8PWa9yla8YOhMOqQG6pdW3GfWxvnqpzL8KYsXpP8Z/IpDlzvsF9vFgm7KeeMlQtWVeViWLIwZTZAWF1BvW/TtcY8jsAcEnMNmMKX1tp/R82k/w6+7/cxIL21/oAVRwV1fpX72AqRIGUoyTQnVU5exGRhrfI0zAbyFItdkm2Kj7cSUpnE9hFW3YDk+42hODzpnb4tXW71o0PMtfPbsIkF9mtAtuTkiS0sz1A/Bn8xKHJnsmqzfT5Az4i1fmLApMIfuJqJMnVWrWq180bs/w5Y+WbotgvmLZheIOd2Ezn+Yr1hixMsrB3uvQasXi+Yeu/f2azajctoraHpiLr3m/4SlhDt+A3PTlHrlS0EVrkQBHAEQZPew0Php6be/WRkfaLuZW/pDIcjGB2qfSrZvMH2DXYs8nKGZuwtNktDZzi1aMgvdvFXM6q1F4iV7kZ8tdL4u89lUBRW0SOmn3wo0jh2l4DgyKJBr9g7cXvmRT2YYIJ3lrPPjckGwxztLIQV/nBYFK6s6iRAg96xy+pOCrnQfiFjv6Rnj+SC2ZqJHw1RQsp69GVaZfYvBuOk8d6l+vTp1OqlzzOYOr3+oWrwmxDcjHYuVcdzX1+9W1aPpK3nZ/oXC2p30pVfBO5W/MGv3mcCXBTkfjOqMfU7IjCfXnwcYZSzPSooA3q8O+3DIoR6SK9yP3u4LMk8J4+TkpOh/yf26cVzOHd71h5GVg/dtYh7Z1rVy+HNan3RBE1PLu/veAGevWGW7b8xqWdugO9e8NuV2nuBzOGZ2w35zJJ6JO151NS/bm7dwKUPV3jOm5cW2uKVTUsOszjzI3fuZw3vqGcuc0K/9Mtx/htvXgenrxukACQw96Pd+9VXeK+brPDYObXU5976e9TZz1pxO3To0KFDh/8W/gKxZgRE:CD65
^FO0,2^GB573,401,1^FS
^FO0,319^GB573,0,1^FS
^FT552,326^AAI,18,10^FB517,1,0,C^FH\^FDQuality System Metallurgica Bresciana Dello^FS
^FT565,302^ADI,18,10^FH\^FDID instrument:^FS
^FO371,264^GB0,56,1^FS
^FT194,302^ADI,18,10^FH\^FDSerial Number:\09\09^FS
^FO2,264^GB571,0,1^FS
^FT565,246^ADI,18,10^FH\^FDInspector:^FS
^FO1,204^GB571,0,1^FS
^FT565,185^ADI,18,10^FH\^FDIssuing Body:^FS
^FO1,147^GB571,0,1^FS
^FT568,114^ADI,18,10^FH\^FDFrequency:^FS
^FT188,114^ADI,18,10^FH\^FDmonths^FS
^FO1,85^GB571,0,1^FS
^FT565,65^ADI,18,10^FH\^FDFrom:^FS
^FO290,1^GB0,85,1^FS
^FT283,65^ADI,18,10^FH\^FDDue:^FS
^FT559,270^A0I,25,24^FH\^FD$id^FS
^FT363,270^A0I,25,24^FH\^FD$SerialNumber^FS
^FT559,214^A0I,25,24^FH\^FD$inspector^FS
^FT559,152^A0I,25,24^FH\^FD$issuingBody^FS
^FT432,108^A0I,25,24^FH\^FD$frequency^FS
^FT559,23^A0I,25,24^FH\^FD$from^FS
^FT275,23^A0I,25,24^FH\^FD$due^FS
^PQ1,0,1,Y^XZ";


        //send to printer
        $zpl_ip = $content['Ip_Printer'];
        //trim zpl
        $zpl = trim($zpl);
        //Log::info("IP:" . $zpl_ip . " Barcode:" . $zpl);
        if (!empty($zpl_ip) and !empty($zpl)) {
            ZplPrinter::printer($zpl_ip)->send($zpl);
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
