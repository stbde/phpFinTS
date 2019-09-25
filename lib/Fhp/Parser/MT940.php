<?php

namespace Fhp\Parser;

use Fhp\Parser\Exception\MT940Exception;

/**
 * Class MT940
 * @package Fhp\Parser
 */
class MT940
{
    const TARGET_ARRAY = 0;

    const CD_CREDIT = 'credit';
    const CD_DEBIT = 'debit';

    /** @var string */
    protected $rawData;
    /** @var string */
    protected $soaDate;

    /**
     * MT940 constructor.
     *
     * @param string $rawData
     */
    public function __construct($rawData)
    {
        $this->rawData = (string) $rawData;
    }

    /**
     * @param string $target
     * @return array
     * @throws MT940Exception
     */
    public function parse($target)
    {
        switch ($target) {
            case static::TARGET_ARRAY:
                return $this->parseToArray();
                break;
            default:
                throw new MT940Exception('Invalid parse type provided');
        }
    }

    /**
     * @return array
     * @throws MT940Exception
     */
    protected function parseToArray()
    {
        // The divider can be either \r\n or @@
        $divider = substr_count($this->rawData, "\r\n-") > substr_count($this->rawData, '@@-') ? "\r\n" : '@@';

        $cleanedRawData = preg_replace('#' . $divider . '([^:])#ms', '$1', $this->rawData);

        $booked = true;
        $result = array();
        $days = explode($divider . '-', $cleanedRawData);
        foreach ($days as &$day) {

            $day = explode($divider . ':', $day);

            for ($i = 0, $cnt = count($day); $i < $cnt; $i++) {
                if (preg_match("/\+\@[0-9]+\@$/", trim($day[$i]))) {
                    $booked = false;
                }

                // handle start balance
                // 60F:C160401EUR1234,56
                if (preg_match('/^60(F|M):/', $day[$i])) {
                    // remove 60(F|M): for better parsing
                    $day[$i] = substr($day[$i], 4);
                    $this->soaDate = $this->getDate(substr($day[$i], 1, 6));

                    if (!isset($result[$this->soaDate])) {
                        $result[$this->soaDate] = array('start_balance' => array());
                    }

                    $cdMark = substr($day[$i], 0, 1);
                    if ($cdMark == 'C') {
                        $result[$this->soaDate]['start_balance']['credit_debit'] = static::CD_CREDIT;
                    } elseif ($cdMark == 'D') {
                        $result[$this->soaDate]['start_balance']['credit_debit'] = static::CD_DEBIT;
                    }

                    $amount = str_replace(',', '.', substr($day[$i], 10));
                    $result[$this->soaDate]['start_balance']['amount'] = $amount;
                } elseif (
                    // found transaction
                    // trx:61:1603310331DR637,39N033NONREF
                    0 === strpos($day[$i], '61:')
                    && isset($day[$i + 1])
                    && 0 === strpos($day[$i + 1], '86:')
                ) {
                    $transaction = substr($day[$i], 3);
                    $description = substr($day[$i + 1], 3);

                    if (!isset($result[$this->soaDate]['transactions'])) {
                        $result[$this->soaDate]['transactions'] = array();
                    }

                    // short form for better handling
                    $trx = &$result[$this->soaDate]['transactions'];

                    preg_match('/^\d{6}(\d{4})?(C|D|RC|RD)([A-Z]{1})?([^N]+)N/', $transaction, $trxMatch);
                    if ($trxMatch[2] == 'C' OR $trxMatch[2] == 'RC') {
                        $trx[count($trx)]['credit_debit'] = static::CD_CREDIT;
                    } elseif ($trxMatch[2] == 'D' OR $trxMatch[2] == 'RD') {
                        $trx[count($trx)]['credit_debit'] = static::CD_DEBIT;
                    } else {
                        throw new MT940Exception('cd mark not found in: ' . $transaction);
                    }

                    $amount = $trxMatch[4];
                    $amount = str_replace(',', '.', $amount);
                    $trx[count($trx) - 1]['amount'] = $amount;

                    $description = $this->parseDescription($description);
                    $trx[count($trx) - 1]['description'] = $description;

                    // :61:1605110509D198,02NMSCNONREF
                    // 16 = year
                    // 0511 = valuta date
                    // 0509 = booking date
                    $year = substr($transaction, 0, 2);
                    $valutaDate = $this->getDate($year . substr($transaction, 2, 4));

                    $bookingDate = substr($transaction, 6, 4);
                    if (preg_match('/^\d{4}$/', $bookingDate)) {
                        // if valuta date is earlier than booking date, then it must be in the new year.
                        if (substr($transaction, 2, 2) == '12' && substr($transaction, 6, 2) == '01') {
                            $year++;
                        } elseif (substr($transaction, 2, 2) == '01' && substr($transaction, 6, 2) == '12') {
                            $year--;
                        }
                        $bookingDate = $this->getDate($year . $bookingDate);
                    } else {
                        // if booking date not set in :61, then we have to take it from :60F
                        $bookingDate = $this->soaDate;
                    }

                    $trx[count($trx) - 1]['booking_date'] = $bookingDate;
                    $trx[count($trx) - 1]['valuta_date'] = $valutaDate;
                    $trx[count($trx) - 1]['booked'] = $booked;

                    $parsedTag61 = $this->parseTag61($transaction);

                    $trx[count($trx) - 1]['customerref'] = $parsedTag61['customerref'];
                    $trx[count($trx) - 1]['instref'] = (isset($parsedTag61['instref']) ? $parsedTag61['instref'] : '');
                }
            }
        }

        return $result;
    }

    protected function parseDescription($descr)
    {
        // Geschäftsvorfall-Code
        $gvc = substr($descr, 0, 3);

        $prepared = array();
        $result = array();

        // prefill with empty values
        for ($i = 0; $i <= 63; $i++) {
            $prepared[$i] = null;
        }

        $descr = str_replace('? ', '?', $descr);

        preg_match_all('/\?(\d{2})([^\?]+)/', $descr, $matches, PREG_SET_ORDER);

        $descriptionLines = array();
        $description1 = ''; // Legacy, could be removed.
        $description2 = ''; // Legacy, could be removed.
        foreach ($matches as $m) {
            $index = (int) $m[1];

            if ((20 <= $index && $index <= 29) || (60 <= $index && $index <= 63)) {
                if (20 <= $index && $index <= 29) {
                    $description1 .= $m[2];
                } else {
                    $description2 .= $m[2];
                }
                if ($m[2] != '') {
                    $descriptionLines[] = $m[2];
                }
            }
            $prepared[$index] = $m[2];
        }

        $description = $this->extractStructuredDataFromRemittanceLines($descriptionLines, $gvc, $prepared);

        $result['booking_code']      = $gvc;
        $result['booking_text']      = trim($prepared[0]);
        $result['description']       = $description;
        $result['primanoten_nr']     = trim($prepared[10]);
        $result['description_1']     = trim($description1);
        $result['bank_code']         = trim($prepared[30]);
        $result['account_number']    = trim($prepared[31]);
        $result['name']              = trim($prepared[32] . $prepared[33]);
        $result['text_key_addition'] = trim($prepared[34]);
        $result['description_2']     = $description2;
        $result['desc_lines']        = $descriptionLines;

        return $result;
    }

    /**
     * @param string[] $lines that contain the remittance information
     * @param string $gvc Geschätsvorfallcode; Out-Parameter, might be changed from information in remittance info
     * @param string $rawLines All the lines in the Multi-Purpose-Field 86; Out-Parameter, might be changed from information in remittance info
     * @return array
     */
    protected function extractStructuredDataFromRemittanceLines($descriptionLines, &$gvc, &$rawLines)
    {
        $description = array();
        if (empty($descriptionLines) || strlen($descriptionLines[0]) < 5 || $descriptionLines[0][4] !== '+') {
            $description['SVWZ'] = implode('', $descriptionLines);
        } else {
            $lastType = null;
            foreach ($descriptionLines as $line) {
                if (strlen($line) >= 5 && $line[4] === '+') {
                    if ($lastType != null) {
                        $description[$lastType] = trim($description[$lastType]);
                    }
                    $lastType = substr($line, 0, 4);
                    $description[$lastType] = substr($line, 5);
                } else {
                    $description[$lastType] .= $line;
                }
                if (strlen($line) < 27) {
                    // Usually, lines are 27 characters long. In case characters are missing, then it's either the end
                    // of the current type or spaces have been trimmed from the end. We want to collapse multiple spaces
                    // into one and we don't want to leave trailing spaces behind. So add a single space here to make up
                    // for possibly missing spaces, and if it's the end of the type, it will be trimmed off later.
                    $description[$lastType] .= ' ';
                }
            }
            $description[$lastType] = trim($description[$lastType]);
        }

        return $description;
    }

    /**
     * @param string $val
     * @return string
     */
    protected function getDate($val)
    {
        $val = '20' . $val;
        preg_match('/(\d{4})(\d{2})(\d{2})/', $val, $m);
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }

    /**
     * Adaptiert von HBCI4Java
     *
     * @param string $st_ums
     * @return array
     */
    protected function parseTag61(string $st_ums) {

        // 1905150515DR44,87NDDTNONREF
        // 1906070607CR8000,00N060NONREF//063000110706

        $result = [];

        // extract bdate
        $next = 0;
        if ($st_ums[6] > '9') {
            //line.bdate = line.valuta;
            $next = 6;
        } else {
            //line.bdate=dateFormat.parse(st_ums.substring(0,2) . st_ums.substring(6,10));

            // wenn bdate und valuta um mehr als einen monat voneinander
            // abweichen, dann ist das jahr des bdate falsch (1.1.2005 vs. 31.12.2004)
            // korrektur des bdate-jahres in die richtige richtung notwendig
            /*if (Math.abs(line.bdate.getTime()-line.valuta.getTime()) > 30L*24*3600*1000) {
                int diff;

                if (line.bdate.before(line.valuta)) {
                    diff=+1;
                } else {
                    diff=-1;
                }
                Calendar cal=Calendar.getInstance();
                cal.setTime(line.bdate);
                cal.set(Calendar.YEAR,cal.get(Calendar.YEAR)+diff);
                line.bdate=cal.getTime();
            }*/

            $next = 10;
        }

        // extract credit/debit
        //$cd;
        if ($st_ums[$next] == 'C' || $st_ums[$next] == 'D') {
            //line.isStorno=false;
            //cd=st_ums.substring(next,next+1);
            $next++;
        } else {
            //line.isStorno=true;
            //cd=st_ums.substring(next+1,next+2);
            $next += 2;
        }

        // skip part of currency
        $currpart = $st_ums[$next];
        if ($currpart > '9')
            $next++;

        //line.value = new Value();

        // TODO: bei einem MT942 wird die waehrung hier automatisch auf EUR
        // gesetzt, weil die auto-erkennung (anhand des anfangssaldos) hier nicht
        // funktioniert, weil es im MT942 keinen anfangssaldo gibt
        //line.value.setCurr((btag.start!=null)?btag.start.value.getCurr():"EUR");

        // extract value and skip code
        $npos = stripos($st_ums, 'N', $next);
        // welcher Code (C/D) zeigt einen negativen Buchungsbetrag
        // an? Bei einer "normalen" Buchung ist das D(ebit). Bei
        // einer Storno-Buchung ist der Betrag allerdings negativ,
        // wenn eine ehemalige Gutschrift (Credit) storniert wird,
        // in dem Fall wäre als "C" der Indikator für den negativen
        // Buchungsbetrag
        /*$negValueIndikator = line.isStorno ? "C" : "D";
        line.value.setValue(
            HBCIUtilsInternal.string2Long(
                (cd.equals(negValueIndikator)?"-":"") + st_ums.substring(next,npos).replace(',','.'),
                100));*/
        $next = $npos + 4;

        // update saldo
        /*saldo+=line.value.getLongValue();

        line.saldo=new Saldo();
        line.saldo.timestamp=line.bdate;*/
        // TODO: bei einem MT942 wird die waehrung hier automatisch auf EUR
        // gesetzt, weil die auto-erkennung (anhand des anfangssaldos) hier nicht
        // funktioniert, weil es im MT942 keinen anfangssaldo gibt
        //line.saldo.value=new Value(saldo, (btag.start!=null)?btag.start.value.getCurr():"EUR");

        // extract customerref
        $npos = stripos($st_ums, '//', $next);
        if ($npos === false)
            $npos = stripos($st_ums, "\r\n", $next);
        if ($npos === false)
            $npos = strlen($st_ums);
        $result['customerref'] = substr($st_ums, $next, $npos - $next);
        $next = $npos;

        // check for instref
        if (($next < strlen($st_ums)) && (substr($st_ums, $next, 2) == '//')) {
            // extract instref
            $next += 2;
            $npos = stripos($st_ums, "\r\n", $next);
            if ($npos === false)
                $npos = strlen($st_ums);
            $result['instref'] = substr($st_ums, $next, $npos - $next);
            $next = $npos + 2;
        }
        if (!isset($result['instref']) || ($result['instref'] == null))
            $result['instref'] = '';

        // check for additional information
        if ($next < strlen($st_ums) && $st_ums[$next] == "\r") {
            $next += 2;

            // extract orig Value
            $pos = stripos($st_ums, '/OCMT/', $next);
            if ($pos !== false) {
                $slashpos = stripos($st_ums, '/', $pos + 9);
                if ($slashpos === false)
                    $slashpos = strlen($st_ums);

                /*line.orig_value = new Value(
                    st_ums.substring($pos + 9, $slashpos).replace(',','.'),
                    st_ums.substring($pos + 6, $pos + 9));
                $result['orig_value'] = ;*/
            }

            // extract charge Value
            $pos = stripos($st_ums, '/CHGS/', $next);
            if ($pos !== false) {
                $slashpos = stripos($st_ums, '/', $pos + 9);
                if ($slashpos === false)
                    $slashpos = strlen($st_ums);

                /*line.charge_value=new Value(
                    st_ums.substring($pos + 9, $slashpos).replace(',','.'),
                    st_ums.substring($pos + 6, $pos + 9));
                $result['charge_value'] = ;*/
            }
        }

        return $result;
    }
}
