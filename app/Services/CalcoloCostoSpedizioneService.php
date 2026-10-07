<?php

namespace App\Services;

use App\Models\ListinoSpedizione;
use App\Models\ListinoSpedizioneVoce;
use Illuminate\Support\Facades\Log;

class CalcoloCostoSpedizioneService
{
    /**
     * Mappa completa Province italiane (sigla) -> Regione (maiuscolo come nei listini).
     */
    public const PROVINCIA_REGIONE = [
        // Piemonte
        'AL' => 'PIEMONTE', 'AT' => 'PIEMONTE', 'BI' => 'PIEMONTE', 'CN' => 'PIEMONTE',
        'NO' => 'PIEMONTE', 'TO' => 'PIEMONTE', 'VB' => 'PIEMONTE', 'VC' => 'PIEMONTE',
        // Valle d'Aosta
        'AO' => "VALLE D'AOSTA",
        // Lombardia
        'BG' => 'LOMBARDIA', 'BS' => 'LOMBARDIA', 'CO' => 'LOMBARDIA', 'CR' => 'LOMBARDIA',
        'LC' => 'LOMBARDIA', 'LO' => 'LOMBARDIA', 'MN' => 'LOMBARDIA', 'MI' => 'LOMBARDIA',
        'MB' => 'LOMBARDIA', 'PV' => 'LOMBARDIA', 'SO' => 'LOMBARDIA', 'VA' => 'LOMBARDIA',
        // Trentino-Alto Adige
        'BZ' => 'TRENTINO ALTO ADIGE', 'TN' => 'TRENTINO ALTO ADIGE',
        // Veneto
        'BL' => 'VENETO', 'PD' => 'VENETO', 'RO' => 'VENETO', 'TV' => 'VENETO',
        'VE' => 'VENETO', 'VR' => 'VENETO', 'VI' => 'VENETO',
        // Friuli-Venezia Giulia
        'GO' => 'FRIULI VENEZIA GIULIA', 'PN' => 'FRIULI VENEZIA GIULIA',
        'TS' => 'FRIULI VENEZIA GIULIA', 'UD' => 'FRIULI VENEZIA GIULIA',
        // Liguria
        'GE' => 'LIGURIA', 'IM' => 'LIGURIA', 'SP' => 'LIGURIA', 'SV' => 'LIGURIA',
        // Emilia-Romagna
        'BO' => 'EMILIA ROMAGNA', 'FE' => 'EMILIA ROMAGNA', 'FC' => 'EMILIA ROMAGNA',
        'FO' => 'EMILIA ROMAGNA', 'MO' => 'EMILIA ROMAGNA', 'PR' => 'EMILIA ROMAGNA',
        'PC' => 'EMILIA ROMAGNA', 'RA' => 'EMILIA ROMAGNA', 'RE' => 'EMILIA ROMAGNA',
        'RN' => 'EMILIA ROMAGNA',
        // Toscana
        'AR' => 'TOSCANA', 'FI' => 'TOSCANA', 'GR' => 'TOSCANA', 'LI' => 'TOSCANA',
        'LU' => 'TOSCANA', 'MS' => 'TOSCANA', 'PI' => 'TOSCANA', 'PT' => 'TOSCANA',
        'PO' => 'TOSCANA', 'SI' => 'TOSCANA',
        // Umbria
        'PG' => 'UMBRIA', 'TR' => 'UMBRIA',
        // Marche
        'AN' => 'MARCHE', 'AP' => 'MARCHE', 'FM' => 'MARCHE', 'MC' => 'MARCHE', 'PU' => 'MARCHE', 'PS' => 'MARCHE',
        // Lazio
        'FR' => 'LAZIO', 'LT' => 'LAZIO', 'RI' => 'LAZIO', 'RM' => 'LAZIO', 'VT' => 'LAZIO',
        // Abruzzo
        'AQ' => 'ABRUZZO', 'CH' => 'ABRUZZO', 'PE' => 'ABRUZZO', 'TE' => 'ABRUZZO',
        // Molise
        'CB' => 'MOLISE', 'IS' => 'MOLISE',
        // Campania
        'AV' => 'CAMPANIA', 'BN' => 'CAMPANIA', 'CE' => 'CAMPANIA', 'NA' => 'CAMPANIA', 'SA' => 'CAMPANIA',
        // Puglia
        'BA' => 'PUGLIA', 'BT' => 'PUGLIA', 'BR' => 'PUGLIA', 'FG' => 'PUGLIA',
        'LE' => 'PUGLIA', 'TA' => 'PUGLIA',
        // Basilicata
        'MT' => 'BASILICATA', 'PZ' => 'BASILICATA',
        // Calabria
        'CZ' => 'CALABRIA', 'CS' => 'CALABRIA', 'KR' => 'CALABRIA', 'RC' => 'CALABRIA', 'VV' => 'CALABRIA',
        // Sicilia
        'AG' => 'SICILIA', 'CL' => 'SICILIA', 'CT' => 'SICILIA', 'EN' => 'SICILIA',
        'ME' => 'SICILIA', 'PA' => 'SICILIA', 'RG' => 'SICILIA', 'SR' => 'SICILIA', 'TP' => 'SICILIA',
        // Sardegna
        'CA' => 'SARDEGNA', 'NU' => 'SARDEGNA', 'OR' => 'SARDEGNA', 'SS' => 'SARDEGNA',
        'SU' => 'SARDEGNA', 'OT' => 'SARDEGNA', 'OG' => 'SARDEGNA', 'VS' => 'SARDEGNA', 'CI' => 'SARDEGNA',
    ];

    /**
     * Capoluoghi di provincia (sigla provincia -> nomi comune).
     * La quota fissa di inoltro NON si applica alle spedizioni verso il capoluogo;
     * si applica a tutte le altre destinazioni (es. Vibo Valentia -> no inoltro, Tropea -> si).
     */
    public const PROVINCIA_CAPOLUOGO = [
        // Piemonte
        'AL' => ['ALESSANDRIA'], 'AT' => ['ASTI'], 'BI' => ['BIELLA'], 'CN' => ['CUNEO'],
        'NO' => ['NOVARA'], 'TO' => ['TORINO'], 'VB' => ['VERBANIA'], 'VC' => ['VERCELLI'],
        // Valle d'Aosta
        'AO' => ['AOSTA'],
        // Lombardia
        'BG' => ['BERGAMO'], 'BS' => ['BRESCIA'], 'CO' => ['COMO'], 'CR' => ['CREMONA'],
        'LC' => ['LECCO'], 'LO' => ['LODI'], 'MN' => ['MANTOVA'], 'MI' => ['MILANO'],
        'MB' => ['MONZA'], 'PV' => ['PAVIA'], 'SO' => ['SONDRIO'], 'VA' => ['VARESE'],
        // Trentino-Alto Adige
        'BZ' => ['BOLZANO'], 'TN' => ['TRENTO'],
        // Veneto
        'BL' => ['BELLUNO'], 'PD' => ['PADOVA'], 'RO' => ['ROVIGO'], 'TV' => ['TREVISO'],
        'VE' => ['VENEZIA'], 'VR' => ['VERONA'], 'VI' => ['VICENZA'],
        // Friuli-Venezia Giulia
        'GO' => ['GORIZIA'], 'PN' => ['PORDENONE'], 'TS' => ['TRIESTE'], 'UD' => ['UDINE'],
        // Liguria
        'GE' => ['GENOVA'], 'IM' => ['IMPERIA'], 'SP' => ['LA SPEZIA'], 'SV' => ['SAVONA'],
        // Emilia-Romagna
        'BO' => ['BOLOGNA'], 'FE' => ['FERRARA'], 'FC' => ['FORLÌ', 'FORLI', 'CESENA'],
        'FO' => ['FORLÌ', 'FORLI'], 'MO' => ['MODENA'], 'PR' => ['PARMA'],
        'PC' => ['PIACENZA'], 'RA' => ['RAVENNA'], 'RE' => ['REGGIO EMILIA', "REGGIO NELL'EMILIA"],
        'RN' => ['RIMINI'],
        // Toscana
        'AR' => ['AREZZO'], 'FI' => ['FIRENZE'], 'GR' => ['GROSSETO'], 'LI' => ['LIVORNO'],
        'LU' => ['LUCCA'], 'MS' => ['MASSA', 'CARRARA'], 'PI' => ['PISA'], 'PT' => ['PISTOIA'],
        'PO' => ['PRATO'], 'SI' => ['SIENA'],
        // Umbria
        'PG' => ['PERUGIA'], 'TR' => ['TERNI'],
        // Marche
        'AN' => ['ANCONA'], 'AP' => ['ASCOLI PICENO'], 'FM' => ['FERMO'], 'MC' => ['MACERATA'],
        'PU' => ['PESARO', 'URBINO'], 'PS' => ['PESARO', 'URBINO'],
        // Lazio
        'FR' => ['FROSINONE'], 'LT' => ['LATINA'], 'RI' => ['RIETI'], 'RM' => ['ROMA'], 'VT' => ['VITERBO'],
        // Abruzzo
        'AQ' => ["L'AQUILA", 'AQUILA'], 'CH' => ['CHIETI'], 'PE' => ['PESCARA'], 'TE' => ['TERAMO'],
        // Molise
        'CB' => ['CAMPOBASSO'], 'IS' => ['ISERNIA'],
        // Campania
        'AV' => ['AVELLINO'], 'BN' => ['BENEVENTO'], 'CE' => ['CASERTA'], 'NA' => ['NAPOLI'], 'SA' => ['SALERNO'],
        // Puglia
        'BA' => ['BARI'], 'BT' => ['BARLETTA', 'ANDRIA', 'TRANI'], 'BR' => ['BRINDISI'], 'FG' => ['FOGGIA'],
        'LE' => ['LECCE'], 'TA' => ['TARANTO'],
        // Basilicata
        'MT' => ['MATERA'], 'PZ' => ['POTENZA'],
        // Calabria
        'CZ' => ['CATANZARO'], 'CS' => ['COSENZA'], 'KR' => ['CROTONE'],
        'RC' => ['REGGIO CALABRIA', 'REGGIO DI CALABRIA'], 'VV' => ['VIBO VALENTIA'],
        // Sicilia
        'AG' => ['AGRIGENTO'], 'CL' => ['CALTANISSETTA'], 'CT' => ['CATANIA'], 'EN' => ['ENNA'],
        'ME' => ['MESSINA'], 'PA' => ['PALERMO'], 'RG' => ['RAGUSA'], 'SR' => ['SIRACUSA'], 'TP' => ['TRAPANI'],
        // Sardegna
        'CA' => ['CAGLIARI'], 'NU' => ['NUORO'], 'OR' => ['ORISTANO'], 'SS' => ['SASSARI'],
        'SU' => ['CARBONIA', 'IGLESIAS'], 'OT' => ['OLBIA', 'TEMPIO PAUSANIA'],
        'OG' => ['TORTOLÌ', 'TORTOLI', 'LANUSEI'], 'VS' => ['SANLURI', 'VILLACIDRO'],
        'CI' => ['CARBONIA', 'IGLESIAS'],
    ];

    /**
     * Alias normalizzati delle regioni: gestisce le abbreviazioni usate nei listini
     * (es. "TRENTINO A.A." -> TRENTINOALTOADIGE).
     */
    protected const REGIONE_ALIAS = [
        'TRENTINOAA'       => 'TRENTINOALTOADIGE',
        'TRENTINOAADIGE'   => 'TRENTINOALTOADIGE',
        'ALTOADIGE'        => 'TRENTINOALTOADIGE',
        'SUDTIROL'         => 'TRENTINOALTOADIGE',
        'FRIULIVG'         => 'FRIULIVENEZIAGIULIA',
        'FRIULIVENEZIAG'   => 'FRIULIVENEZIAGIULIA',
        'EMILIA'           => 'EMILIAROMAGNA',
        'EMILIAR'          => 'EMILIAROMAGNA',
        'EMILIAROM'        => 'EMILIAROMAGNA',
        'VALLEAOSTA'       => 'VALLEDAOSTA',
        'VALDAOSTA'        => 'VALLEDAOSTA',
    ];

    /**
     * Nazione (ISO alpha-2) -> varianti testuali riconoscibili nell'indirizzo
     * (codice alpha-2/alpha-3 e nomi in inglese/italiano/lingua locale).
     * Il codice alpha-2 e' incluso tra gli alias per semplificare i controlli.
     */
    public const NAZIONI_ISO = [
        'IT' => ['IT', 'ITA', 'ITALIA', 'ITALY'],
        'DE' => ['DE', 'DEU', 'GERMANY', 'GERMANIA', 'DEUTSCHLAND'],
        'FR' => ['FR', 'FRA', 'FRANCE', 'FRANCIA'],
        'ES' => ['ES', 'ESP', 'SPAIN', 'SPAGNA', 'ESPANA', 'ESPAGNE'],
        'PT' => ['PT', 'PRT', 'PORTUGAL', 'PORTOGALLO'],
        'AT' => ['AT', 'AUT', 'AUSTRIA', 'OSTERREICH', 'OESTERREICH'],
        'CH' => ['CH', 'CHE', 'SWITZERLAND', 'SVIZZERA', 'SCHWEIZ', 'SUISSE'],
        'BE' => ['BE', 'BEL', 'BELGIUM', 'BELGIO', 'BELGIQUE'],
        'NL' => ['NL', 'NLD', 'NETHERLANDS', 'OLANDA', 'PAESI BASSI', 'HOLLAND', 'PAYS-BAS'],
        'GB' => ['GB', 'GBR', 'UK', 'UNITED KINGDOM', 'GREAT BRITAIN', 'GRAN BRETAGNA', 'INGHILTERRA', 'ENGLAND'],
        'IE' => ['IE', 'IRL', 'IRELAND', 'IRLANDA', 'EIRE'],
        'LU' => ['LU', 'LUX', 'LUXEMBOURG', 'LUSSEMBURGO'],
        'LI' => ['LI', 'LIE', 'LIECHTENSTEIN'],
        'MC' => ['MC', 'MCO', 'MONACO'],
        'SM' => ['SM', 'SMR', 'SAN MARINO'],
        'AD' => ['AD', 'AND', 'ANDORRA'],
        'DK' => ['DK', 'DNK', 'DENMARK', 'DANIMARCA', 'DANMARK'],
        'SE' => ['SE', 'SWE', 'SWEDEN', 'SVEZIA', 'SVERIGE'],
        'NO' => ['NO', 'NOR', 'NORWAY', 'NORVEGIA', 'NORGE'],
        'FI' => ['FI', 'FIN', 'FINLAND', 'FINLANDIA', 'SUOMI'],
        'PL' => ['PL', 'POL', 'POLAND', 'POLONIA', 'POLSKA'],
        'CZ' => ['CZ', 'CZE', 'CZECHIA', 'CZECH REPUBLIC', 'REPUBBLICA CECA'],
        'SK' => ['SK', 'SVK', 'SLOVAKIA', 'SLOVACCHIA'],
        'SI' => ['SI', 'SVN', 'SLOVENIA'],
        'HU' => ['HU', 'HUN', 'HUNGARY', 'UNGHERIA', 'MAGYARORSZAG'],
        'HR' => ['HR', 'HRV', 'CROATIA', 'CROAZIA', 'HRVATSKA'],
        'RO' => ['RO', 'ROU', 'ROMANIA'],
        'BG' => ['BG', 'BGR', 'BULGARIA'],
        'GR' => ['GR', 'GRC', 'GREECE', 'GRECIA', 'ELLAS'],
        'EE' => ['EE', 'EST', 'ESTONIA'],
        'LV' => ['LV', 'LVA', 'LATVIA', 'LETTONIA'],
        'LT' => ['LT', 'LTU', 'LITHUANIA', 'LITUANIA'],
        'MT' => ['MT', 'MLT', 'MALTA'],
        'CY' => ['CY', 'CYP', 'CYPRUS', 'CIPRO'],
        'RS' => ['RS', 'SRB', 'SERBIA'],
        'BA' => ['BA', 'BIH', 'BOSNIA', 'BOSNIA AND HERZEGOVINA', 'BOSNIA ERZEGOVINA'],
        'ME' => ['ME', 'MNE', 'MONTENEGRO'],
        'AL' => ['AL', 'ALB', 'ALBANIA'],
        'MK' => ['MK', 'MKD', 'MACEDONIA', 'NORTH MACEDONIA', 'MACEDONIA DEL NORD'],
        'TR' => ['TR', 'TUR', 'TURKEY', 'TURCHIA', 'TURKIYE'],
        'UA' => ['UA', 'UKR', 'UKRAINE', 'UCRAINA'],
        'RU' => ['RU', 'RUS', 'RUSSIA', 'RUSSIAN FEDERATION'],
        'US' => ['US', 'USA', 'UNITED STATES', 'UNITED STATES OF AMERICA', 'STATI UNITI', 'AMERICA'],
        'CA' => ['CA', 'CAN', 'CANADA'],
        'MX' => ['MX', 'MEX', 'MEXICO', 'MESSICO'],
        'BR' => ['BR', 'BRA', 'BRAZIL', 'BRASILE'],
        'AR' => ['AR', 'ARG', 'ARGENTINA'],
        'CN' => ['CN', 'CHN', 'CHINA', 'CINA'],
        'JP' => ['JP', 'JPN', 'JAPAN', 'GIAPPONE'],
        'IN' => ['IN', 'IND', 'INDIA'],
        'KR' => ['KR', 'KOR', 'KOREA', 'SOUTH KOREA', 'COREA', 'COREA DEL SUD'],
        'AU' => ['AU', 'AUS', 'AUSTRALIA'],
        'AE' => ['AE', 'ARE', 'UAE', 'UNITED ARAB EMIRATES', 'EMIRATI ARABI'],
        'SA' => ['SA', 'SAU', 'SAUDI ARABIA', 'ARABIA SAUDITA'],
        'IL' => ['IL', 'ISR', 'ISRAEL', 'ISRAELE'],
        'EG' => ['EG', 'EGY', 'EGYPT', 'EGITTO'],
        'MA' => ['MA', 'MAR', 'MOROCCO', 'MAROCCO'],
        'TN' => ['TN', 'TUN', 'TUNISIA'],
        'DZ' => ['DZ', 'DZA', 'ALGERIA'],
        'ZA' => ['ZA', 'ZAF', 'SOUTH AFRICA', 'SUDAFRICA'],
    ];

    /**
     * Nome inglese della nazione per le query di geocoding (Nominatim).
     */
    public const NAZIONE_NOME_EN = [
        'IT' => 'Italy', 'DE' => 'Germany', 'FR' => 'France', 'ES' => 'Spain',
        'PT' => 'Portugal', 'AT' => 'Austria', 'CH' => 'Switzerland', 'BE' => 'Belgium',
        'NL' => 'Netherlands', 'GB' => 'United Kingdom', 'IE' => 'Ireland',
        'LU' => 'Luxembourg', 'LI' => 'Liechtenstein', 'MC' => 'Monaco',
        'SM' => 'San Marino', 'AD' => 'Andorra', 'DK' => 'Denmark', 'SE' => 'Sweden',
        'NO' => 'Norway', 'FI' => 'Finland', 'PL' => 'Poland', 'CZ' => 'Czech Republic',
        'SK' => 'Slovakia', 'SI' => 'Slovenia', 'HU' => 'Hungary', 'HR' => 'Croatia',
        'RO' => 'Romania', 'BG' => 'Bulgaria', 'GR' => 'Greece', 'EE' => 'Estonia',
        'LV' => 'Latvia', 'LT' => 'Lithuania', 'MT' => 'Malta', 'CY' => 'Cyprus',
        'RS' => 'Serbia', 'BA' => 'Bosnia and Herzegovina', 'ME' => 'Montenegro',
        'AL' => 'Albania', 'MK' => 'North Macedonia', 'TR' => 'Turkey', 'UA' => 'Ukraine',
        'RU' => 'Russia', 'US' => 'United States', 'CA' => 'Canada', 'MX' => 'Mexico',
        'BR' => 'Brazil', 'AR' => 'Argentina', 'CN' => 'China', 'JP' => 'Japan',
        'IN' => 'India', 'KR' => 'South Korea', 'AU' => 'Australia',
        'AE' => 'United Arab Emirates', 'SA' => 'Saudi Arabia', 'IL' => 'Israel',
        'EG' => 'Egypt', 'MA' => 'Morocco', 'TN' => 'Tunisia', 'DZ' => 'Algeria',
        'ZA' => 'South Africa',
    ];

    /**
     * Calcola il costo della spedizione dato il vettore, l'indirizzo di destinazione e il peso lordo.
     *
     * @param string|null $vettore Nome vettore estratto dal DDT
     * @param string|null $indirizzo Indirizzo completo estratto dal DDT
     * @param float|null $pesoLordoKg Peso lordo in KG
     * @param int|null $anno Anno del DDT (opzionale per filtro listino)
     * @param int|null $colli Numero colli (opzionale)
     * @param string|null $paese Nazione di destinazione (codice ISO o nome; dedotta dall'indirizzo se null)
     * @return array
     */
    public static function calcola(?string $vettore, ?string $indirizzo, ?float $pesoLordoKg, ?int $anno = null, ?int $colli = null, ?string $paese = null): array
    {
        $risultato = [
            'provincia' => null,
            'regione' => null,
            'listino_id' => null,
            'costo' => null,
            'tipo_calcolo' => null,
            'note' => null,
            'dettaglio' => null,
        ];

        // 1. Determina la nazione di destinazione (esplicita o dedotta dall'indirizzo).
        //    Provincia/Regione si estraggono solo per destinazioni italiane.
        $paeseIso = self::normalizzaNazione($paese) ?? self::estraiNazione($indirizzo);
        $isEstero = $paeseIso !== null && $paeseIso !== 'IT';

        $provincia = $isEstero ? null : self::estraiProvincia($indirizzo);
        $comune = self::estraiComune($indirizzo);
        $risultato['provincia'] = $provincia;

        if ($provincia) {
            $risultato['regione'] = self::PROVINCIA_REGIONE[$provincia] ?? null;
        } elseif ($isEstero) {
            $risultato['regione'] = 'ESTERO';
        }

        // 2. Validazioni minime per calcolo
        if (empty($vettore)) {
            $risultato['note'] = 'Vettore mancante';
            return $risultato;
        }

        if (empty($pesoLordoKg) || $pesoLordoKg <= 0) {
            $risultato['note'] = 'Peso lordo non valido o assente';
            return $risultato;
        }

        // Destinazione estera: i listini nazionali non si applicano
        if ($isEstero) {
            $risultato['note'] = "Destinazione estera ({$paeseIso}): listino non applicabile";
            return $risultato;
        }

        if (empty($provincia) && empty($risultato['regione'])) {
            $risultato['note'] = "Impossibile identificare la provincia/regione dall'indirizzo: {$indirizzo}";
            return $risultato;
        }

        // 3. Trova il listino attivo per il vettore
        $listino = self::trovaListino($vettore, $anno);
        if (!$listino) {
            $risultato['note'] = "Nessun listino attivo trovato per il vettore '{$vettore}'" . ($anno ? " (anno {$anno})" : '');
            return $risultato;
        }

        $risultato['listino_id'] = $listino->id;
        $risultato['tipo_calcolo'] = $listino->tipo;

        // 4. Calcola in base alla tipologia di listino
        if ($listino->tipo === ListinoSpedizione::TIPO_PALLET) {
            return self::calcolaTariffaPallet($listino, $provincia, $risultato['regione'], $pesoLordoKg, $colli, $risultato);
        }

        return self::calcolaTariffaPeso($listino, $provincia, $comune, $risultato['regione'], $pesoLordoKg, $risultato);
    }

    /**
     * Estrae la sigla della provincia da una stringa indirizzo italiana.
     * Cerca pattern tipici: "(TV)", " TV ", "- TV -", "31010 MARENO DI PIAVE (TV)".
     */
    public static function estraiProvincia(?string $indirizzo): ?string
    {
        if (empty($indirizzo)) {
            return null;
        }

        $indirizzoUpper = mb_strtoupper($indirizzo);

        // Pattern 1: tra parentesi (TV), (MI), (BS)
        if (preg_match('/\(([A-Z]{2})\)/', $indirizzoUpper, $m)) {
            $sigla = $m[1];
            if (isset(self::PROVINCIA_REGIONE[$sigla])) {
                return $sigla;
            }
        }

        // Pattern 2: dopo CAP a 5 cifre cerca TUTTE le sigle di 2 lettere e usa la prima valida
        // (es. "25035 OSPITALETTO (BS)" -> BS, ignora token non validi come IT, OS, ...)
        if (preg_match('/\b(\d{5})\b/u', $indirizzoUpper, $capM, PREG_OFFSET_CAPTURE)) {
            $dopoCap = substr($indirizzoUpper, $capM[0][1]);
            preg_match_all('/\b([A-Z]{2})\b/u', $dopoCap, $sigle);
            foreach ($sigle[1] ?? [] as $sigla) {
                if (isset(self::PROVINCIA_REGIONE[$sigla])) {
                    return $sigla;
                }
            }
        }

        // Pattern 3: separata da trattini/spazi/virgole prima della nazione: ", TV , IT" o "- TV - ITALIA"
        if (preg_match('/[\s\-,]([A-Z]{2})[\s\-,]+(?:IT|ITA|ITALIA)\b/u', $indirizzoUpper, $m)) {
            $sigla = $m[1];
            if (isset(self::PROVINCIA_REGIONE[$sigla])) {
                return $sigla;
            }
        }

        // Pattern 4: cerca tutte le parole di 2 lettere maiuscole e controlla se corrispondono a una provincia valida
        // Partendo dal fondo dell'indirizzo (dove solitamente sta la provincia)
        preg_match_all('/\b([A-Z]{2})\b/u', $indirizzoUpper, $matches);
        if (!empty($matches[1])) {
            $possibili = array_reverse($matches[1]);
            foreach ($possibili as $token) {
                // Esclude acronimi comuni che non sono la provincia cercata
                if (in_array($token, ['IT', 'SR', 'SN', 'SP', 'CS', 'VIA', 'PI'])) {
                    // Alcune sigle coincidono con abbreviazioni (SP=strada prov, CS=corso, SR=strada reg)
                    // Le consideriamo valide solo se nessun'altra provincia è trovata
                    continue;
                }
                if (isset(self::PROVINCIA_REGIONE[$token])) {
                    return $token;
                }
            }
        }

        return null;
    }

    /**
     * Estrae il nome del comune/citta dall'indirizzo: il testo tra il codice
     * postale (4-6 cifre: IT 5, DE/FR/ES 5, CH/AT 4, US 5) e il primo separatore.
     * Es. "VIA ROMA 1 89861 TROPEA (VV) - IT" -> "TROPEA",
     *     "LEIMGRUBE, 74613 OEHRINGEN (08) - DE" -> "OEHRINGEN".
     */
    public static function estraiComune(?string $indirizzo): ?string
    {
        if (empty($indirizzo)) {
            return null;
        }

        $indirizzoUpper = mb_strtoupper($indirizzo);

        if (!preg_match('/\b\d{4,6}\b/u', $indirizzoUpper, $capM, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $dopoCap = substr($indirizzoUpper, $capM[0][1] + strlen($capM[0][0]));

        // Toglie token tra parentesi "(VV)", "(08)" e quanto segue - , /
        $dopoCap = preg_replace('/\s*\([^)]*\)\s*/', ' ', $dopoCap);
        $dopoCap = preg_replace('/[\-,\/].*$/u', '', $dopoCap);
        $dopoCap = trim($dopoCap);

        // Rimuove eventuale sigla provincia/codice nazione a fine stringa
        // ("OSPITALETTO BS", "OEHRINGEN DE", "ZUERICH CH")
        $dopoCap = trim(preg_replace('/\s+[A-Z]{2,3}$/u', '', $dopoCap));

        // Rimuove eventuale nome nazione esteso in coda ("PARIS FRANCE")
        static $alternanzaNomi = null;
        if ($alternanzaNomi === null) {
            $nomi = [];
            foreach (self::NAZIONI_ISO as $alias) {
                foreach ($alias as $nome) {
                    if (strlen($nome) > 3) {
                        $nomi[] = preg_quote($nome, '/');
                    }
                }
            }
            $alternanzaNomi = implode('|', $nomi);
        }
        $dopoCap = trim(preg_replace('/\s+(?:' . $alternanzaNomi . ')$/u', '', $dopoCap));

        return $dopoCap !== '' ? $dopoCap : null;
    }

    /**
     * Normalizza una nazione (codice ISO o nome EN/IT/locale) nel codice ISO alpha-2.
     */
    public static function normalizzaNazione(?string $nazione): ?string
    {
        if ($nazione === null || trim($nazione) === '') {
            return null;
        }

        $upper = mb_strtoupper(trim($nazione));

        if (isset(self::NAZIONI_ISO[$upper])) {
            return $upper;
        }

        foreach (self::NAZIONI_ISO as $iso => $nomi) {
            if (in_array($upper, $nomi, true)) {
                return $iso;
            }
        }

        return null;
    }

    /**
     * Deduce la nazione di destinazione dal suffisso finale dell'indirizzo.
     * Nei DDT la nazione e' scritta come ultimo token, tipicamente dopo
     * un separatore ("... 74613 OEHRINGEN - DE", "... - IT", "... GERMANY").
     * Restituisce il codice ISO alpha-2 oppure null se non determinabile.
     */
    public static function estraiNazione(?string $indirizzo): ?string
    {
        if (empty($indirizzo)) {
            return null;
        }

        $upper = mb_strtoupper(trim($indirizzo));

        // 1) Nome completo o codice esteso (>2 char) in coda: GERMANY, DEU, GERMANIA, FRANCE...
        foreach (self::NAZIONI_ISO as $iso => $nomi) {
            foreach ($nomi as $nome) {
                if (strlen($nome) > 2 && preg_match('/[\s\-\/,]' . preg_quote($nome, '/') . '\s*$/u', $upper)) {
                    return $iso;
                }
            }
        }

        // 2) Codice alpha-2 in coda dopo separatore forte (- , /):
        //    e' la convenzione dei DDT per la nazione ("... - DE", "... - IT")
        if (preg_match('/[\-\/,]\s*([A-Z]{2})\s*$/u', $upper, $m)) {
            foreach (self::NAZIONI_ISO as $iso => $nomi) {
                if (in_array($m[1], $nomi, true)) {
                    return $iso;
                }
            }
        }

        // 3) Codice alpha-2 in coda separato solo da spazio: ambiguo con le sigle
        //    provincia italiane (FI, CZ, GR...). Se coincide con una sigla provincia
        //    e c'e' un CAP, lo lasciamo alla logica italiana (null = non estero).
        if (preg_match('/\s([A-Z]{2})\s*$/u', $upper, $m)) {
            $siglaProvincia = isset(self::PROVINCIA_REGIONE[$m[1]])
                && preg_match('/\b\d{4,6}\b/u', $upper);
            if (!$siglaProvincia) {
                foreach (self::NAZIONI_ISO as $iso => $nomi) {
                    if (in_array($m[1], $nomi, true)) {
                        return $iso;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Verifica se il comune di destinazione e un capoluogo della provincia indicata.
     */
    protected static function isCapoluogo(?string $provincia, ?string $comune): bool
    {
        if (empty($provincia) || empty($comune)) {
            return false;
        }

        $comuneNorm = self::normalizzaTesto($comune);
        foreach (self::PROVINCIA_CAPOLUOGO[$provincia] ?? [] as $capoluogo) {
            $capNorm = self::normalizzaTesto($capoluogo);
            if ($capNorm !== '' && ($comuneNorm === $capNorm || str_contains($comuneNorm, $capNorm))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cerca il listino attivo per il vettore indicato.
     */
    public static function trovaListino(string $vettore, ?int $anno = null): ?ListinoSpedizione
    {
        $vettoreNormalizzato = self::normalizzaTesto($vettore);
        if ($vettoreNormalizzato === '') return null;

        $listini = ListinoSpedizione::where('attivo', true)
            ->where('status', ListinoSpedizione::STATUS_PROCESSED)
            ->orderByDesc('anno')->orderByDesc('id')->get();

        $candidati = $listini->filter(fn($l) =>
            str_contains($vettoreNormalizzato, self::normalizzaTesto($l->vettore))
            || str_contains(self::normalizzaTesto($l->vettore), $vettoreNormalizzato)
        );

        if ($candidati->isEmpty()) return null;

        return $anno ? ($candidati->firstWhere('anno', $anno) ?? $candidati->first())
            : $candidati->first();
    }

    /**
     * Normalizza una stringa per confronti fuzzy: maiuscolo, solo lettere/cifre.
     */
    protected static function normalizzaTesto(?string $testo): string
    {
        if ($testo === null) {
            return '';
        }

        // Traslittera accenti/diacritici in ASCII per confronti fuzzy (sbalo' -> SBALO)
        $testo = strtr($testo, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c', 'ß' => 'ss',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Ã' => 'A', 'Å' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Õ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ñ' => 'N', 'Ç' => 'C',
        ]);

        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($testo)) ?? '';
    }

    /**
     * Confronta il nome regione di una voce di listino con la regione cercata
     * (gestisce varianti tipo "EMILIA ROMAGNA" / "EMILIA-ROMAGNA").
     */
    protected static function matchRegione(?string $regioneVoce, string $regioneNormalizzata): bool
    {
        $voce = self::normalizzaTesto($regioneVoce);

        if ($voce === '' || $regioneNormalizzata === '') {
            return false;
        }

        // Risolve le abbreviazioni in nome canonico su entrambi i lati
        $voceCanon = self::REGIONE_ALIAS[$voce] ?? $voce;
        $cercataCanon = self::REGIONE_ALIAS[$regioneNormalizzata] ?? $regioneNormalizzata;

        return $voce === $regioneNormalizzata
            || $voceCanon === $cercataCanon
            || str_contains($voce, $regioneNormalizzata)
            || str_contains($regioneNormalizzata, $voce)
            || str_contains($voceCanon, $cercataCanon)
            || str_contains($cercataCanon, $voceCanon);
    }

    /**
     * Calcolo tariffa per listino a PALLET (es. Palletways).
     * Mappa il peso lordo sul formato pallet più appropriato:
     * - fino a 150 kg: MQP / MQ (Mini Quarter Pallet)
     * - fino a 300 kg: QP (Quarter Pallet)
     * - fino a 600 kg: HP (Half Pallet)
     * - fino a 900 kg: LP (Light Pallet)
     * - fino a 1200 kg o oltre: FP (Full Pallet)
     */
    protected static function calcolaTariffaPallet(
        ListinoSpedizione $listino,
        ?string $provincia,
        ?string $regione,
        float $pesoLordoKg,
        ?int $colli,
        array $risultato
    ): array {
        if (empty($provincia)) {
            $risultato['note'] = "Provincia non identificata per calcolo palletways (listino {$listino->id})";
            return $risultato;
        }

        // Determina la fascia in base al peso (se multipli colli, usa il peso per collo medio)
        $numColli = max(1, (int) ($colli ?? 1));
        $pesoPerCollo = $pesoLordoKg / $numColli;

        $fasciaScelta = self::determinaFasciaPallet($pesoPerCollo);

        // Cerca la voce con servizio ECONOMY (standard) per la provincia
        $voce = ListinoSpedizioneVoce::where('listino_id', $listino->id)
            ->where('provincia', $provincia)
            ->where('servizio', 'ECONOMY')
            ->where('fascia', $fasciaScelta)
            ->first();

        // Fallback: prova PREMIUM se ECONOMY non esiste
        if (!$voce) {
            $voce = ListinoSpedizioneVoce::where('listino_id', $listino->id)
                ->where('provincia', $provincia)
                ->where('servizio', 'PREMIUM')
                ->where('fascia', $fasciaScelta)
                ->first();
        }

        // Fallback: fascia FP (Full Pallet) se la fascia specifica non è definita
        if (!$voce) {
            $voce = ListinoSpedizioneVoce::where('listino_id', $listino->id)
                ->where('provincia', $provincia)
                ->whereIn('fascia', ['FP', 'FULL', 'LP'])
                ->first();
        }

        if (!$voce) {
            $risultato['note'] = "Nessuna tariffa trovata nel listino {$listino->id} per provincia '{$provincia}' fascia '{$fasciaScelta}'";
            return $risultato;
        }

        // Il prezzo unitario è a pallet -> moltiplica per i colli (pallet)
        $costoTotale = round(((float) $voce->prezzo) * $numColli, 2);

        $risultato['costo'] = $costoTotale;
        $risultato['note'] = "Calcolato con {$listino->vettore}: {$numColli} collo/i fascia {$voce->fascia} ({$voce->servizio}) @ €" . number_format($voce->prezzo, 2);
        $risultato['dettaglio'] = [
            'listino' => $listino->vettore,
            'provincia' => $provincia,
            'hub' => $voce->hub,
            'servizio' => $voce->servizio,
            'fascia' => $voce->fascia,
            'prezzo_unitario' => (float) $voce->prezzo,
            'colli' => $numColli,
            'peso_lordo_kg' => $pesoLordoKg,
            'peso_per_collo' => round($pesoPerCollo, 2),
            'costo_totale' => $costoTotale,
        ];

        return $risultato;
    }

    /**
     * Determina il formato pallet standard in base al peso (in KG).
     */
    public static function determinaFasciaPallet(float $pesoKg): string
    {
        if ($pesoKg <= 150) {
            return 'MQP'; // Mini Quarter Pallet (o MQ)
        }
        if ($pesoKg <= 300) {
            return 'QP'; // Quarter Pallet
        }
        if ($pesoKg <= 450) {
            return 'ELP'; // Extra Light Pallet
        }
        if ($pesoKg <= 600) {
            return 'HP'; // Half Pallet
        }
        if ($pesoKg <= 900) {
            return 'LP'; // Light Pallet
        }
        if ($pesoKg <= 1000) {
            return 'ULP'; // Ultra Light Pallet (o FP a seconda del listino)
        }
        return 'FP'; // Full Pallet (fino a 1200+ kg)
    }

    /**
     * Calcolo tariffa per listino a PESO (es. SUSA).
     * Formula: (peso_lordo_kg / 100) * tariffa_100kg + quota_inoltro.
     */
    protected static function calcolaTariffaPeso(
        ListinoSpedizione $listino,
        ?string $provincia,
        ?string $comune,
        ?string $regione,
        float $pesoLordoKg,
        array $risultato
    ): array {
        if (empty($regione)) {
            $risultato['note'] = "Regione non identificata per calcolo listino a peso (listino {$listino->id})";
            return $risultato;
        }

        $regioneNormalizzata = self::normalizzaTesto($regione);

        // Peso tassato per eccesso al quintale intero (es. 619 kg -> 700 kg)
        $pesoTassatoKg = ceil($pesoLordoKg / 100.0) * 100;

        $vociTariffa = ListinoSpedizioneVoce::where('listino_id', $listino->id)
            ->where('tipo_voce', ListinoSpedizioneVoce::TIPO_VOCE_TARIFFA)
            ->get()
            ->filter(fn($v) => self::matchRegione($v->regione, $regioneNormalizzata))
            ->values();

        $voce = $vociTariffa->first(fn($v) =>
            $v->peso_da !== null && $v->peso_a !== null
            && (float) $v->peso_da <= $pesoTassatoKg
            && (float) $v->peso_a >= $pesoTassatoKg
        );

        // Fallback: fascia più alta (kg_over)
        if (!$voce) {
            $voce = $vociTariffa->sortByDesc('peso_a')->first();
        }

        if (!$voce) {
            $risultato['note'] = "Nessuna tariffa a peso trovata nel listino {$listino->id} per regione '{$regione}' peso {$pesoLordoKg}kg";
            return $risultato;
        }

        // Quota fissa di inoltro: si applica a TUTTE le destinazioni TRANNE
        // i capoluoghi di provincia (es. Vibo Valentia -> no inoltro, Tropea -> si)
        $escludiInoltro = self::isCapoluogo($provincia, $comune);

        $voceInoltro = null;
        if (!$escludiInoltro) {
            $voceInoltro = ListinoSpedizioneVoce::where('listino_id', $listino->id)
                ->where('tipo_voce', ListinoSpedizioneVoce::TIPO_VOCE_INOLTRO)
                ->get()
                ->first(fn($v) => self::matchRegione($v->regione, $regioneNormalizzata));

            // Fallback: voce inoltro generica senza regione specifica
            if (!$voceInoltro) {
                $voceInoltro = ListinoSpedizioneVoce::where('listino_id', $listino->id)
                    ->where('tipo_voce', ListinoSpedizioneVoce::TIPO_VOCE_INOLTRO)
                    ->whereNull('regione')
                    ->first();
            }
        }

        $costoInoltro = $voceInoltro ? (float) $voceInoltro->prezzo : 0.0;

        // Tariffa in €/quintale applicata al peso tassato
        $quintali = $pesoTassatoKg / 100.0;
        $quotaPeso = $quintali * (float) $voce->prezzo;
        $costoTotale = round($quotaPeso + $costoInoltro, 2);

        $risultato['costo'] = $costoTotale;
        $risultato['note'] = "Calcolato con {$listino->vettore}: {$pesoLordoKg}kg -> tassati {$pesoTassatoKg}kg ({$voce->fascia}) @ €"
            . number_format((float) $voce->prezzo, 3) . "/100kg"
            . ($costoInoltro > 0 ? " + €" . number_format($costoInoltro, 2) . " inoltro" : '')
            . ($escludiInoltro ? ' (inoltro escluso: capoluogo di provincia)' : '');
        $risultato['dettaglio'] = [
            'listino' => $listino->vettore,
            'listino_anno' => $listino->anno,
            'regione' => $regione,
            'provincia' => $provincia,
            'comune' => $comune,
            'fascia' => $voce->fascia,
            'peso_da' => (float) $voce->peso_da,
            'peso_a' => (float) $voce->peso_a,
            'prezzo_quintale' => (float) $voce->prezzo,
            'peso_lordo_kg' => $pesoLordoKg,
            'peso_tassato_kg' => $pesoTassatoKg,
            'quota_peso' => round($quotaPeso, 2),
            'quota_inoltro' => $costoInoltro,
            'inoltro_escluso' => $escludiInoltro,
            'costo_totale' => $costoTotale,
        ];

        return $risultato;
    }
}
