<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to store vacation setting and to generate Sieve code for vacations
 *
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Vacation
{
    /**
     * period in which addresses are kept and are not responded to
     *
     * @var integer
     */
    protected int $_days = 7;

    /**
     * email addresses which belong to the recipient
     *
     * @var array
     */
    protected array $_addresses = [];

    /**
     * vacation message
     *
     * @var ?string
     */
    protected ?string $_reason = null;

    /**
     * status of vacation (enabled or disabled)
     *
     * @var boolean
     */
    protected bool $_enabled = false;

    /**
     * the from address for the generated message
     *
     * @var ?string
     */
    protected ?string $_from = null;

    /**
     * the start date
     *
     * @var ?string
     */
    protected ?string $_startdate = null;

    /**
     * the end date
     *
     * @var ?string
     */
    protected ?string $_enddate = null;

    /**
     * the mime type of the generated message
     *
     * @var ?string
     */
    protected ?string $_mime = null;

    protected bool $_dateEnabled = false;

    /**
     * content type multipart alternative
     */
    protected const MIME_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';

    /**
     * content type text plain
     */
    protected const MIME_TYPE_TEXT_PLAIN = 'text/plain';

    /**
     * the subject for the vacation message
     *
     * @var ?string
     */
    protected ?string $_subject = null;

    /**
     * set from
     *
     * @param   string  $from   the from address
     * @return  Felamimail_Sieve_Vacation
     */
    public function setFrom($from)
    {
        $this->_from = $from;

        return $this;
    }

    /**
     * set subject
     *
     * @param   string  $subject    the subject
     * @return  Felamimail_Sieve_Vacation
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;

        return $this;
    }

    /**
     * set the days
     *
     * @param   integer $days   the days
     * @throws  InvalidArgumentException
     * @return  Felamimail_Sieve_Vacation
     */
    public function setDays($days)
    {
        if (! ctype_digit("$days")) {
            throw new InvalidArgumentException('$days must be numbers only' . $days);
        }

        $this->_days = $days;

        return $this;
    }

    /**
     * add address
     *
     * @param   string  $address    the address
     * @return  Felamimail_Sieve_Vacation
     */
    public function addAddress($address)
    {
        $this->_addresses[] = $address;

        return $this;
    }

    /**
     * set reason
     *
     * @param   string  $reason     the reason
     * @return  Felamimail_Sieve_Vacation
     */
    public function setReason($reason)
    {
        $this->_reason = $reason;

        return $this;
    }

    /**
     * set mime type
     *
     * @param   string  $reason     the mime type of the message
     * @return  Felamimail_Sieve_Vacation
     */
    public function setMime($mime)
    {
        $this->_mime = $mime;

        return $this;
    }

    /**
     * set status
     *
     * @param   boolean $status     the status
     * @return  Felamimail_Sieve_Vacation
     */
    public function setEnabled($status)
    {
        $this->_enabled = (bool) $status;

        return $this;
    }

    /**
     * set date enabled (use start + end date if sieve server has date/relational capability)
     *
     * @param   boolean $dateEnabled
     * @return  Felamimail_Sieve_Vacation
     */
    public function setDateEnabled($dateEnabled)
    {
        $this->_dateEnabled = (bool) $dateEnabled;

        return $this;
    }

    /**
    * set start date
    *
    * @param   string  $startdate
    * @return  Felamimail_Sieve_Vacation
    */
    public function setStartdate($startdate)
    {
        $this->_startdate = $startdate;
        return $this;
    }

    /**
    * set end date
    *
    * @param   string  $enddate
    * @return  Felamimail_Sieve_Vacation
    */
    public function setEnddate($enddate)
    {
        $this->_enddate = $enddate;
        return $this;
    }

    /**
     * return if vacation is enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }

    /**
     * returns true if start/end date should be added to vacation
     *
     * @return boolean
     */
    public function useDates()
    {
        return $this->_dateEnabled && ($this->_startdate !== null || $this->_enddate !== null);
    }

    /**
     * return the vacation Sieve code
     *
     * @return string
     * @throws DOMException
     */
    public function __toString(): string
    {
        $days      = !empty($this->_days) ? ":days $this->_days " : null;
        $from      = !empty($this->_from) ? ":from {$this->_quoteString($this->_from)} " : null;
        $addresses = count($this->_addresses) > 0 ? ":addresses {$this->_quoteString($this->_addresses)} " : null;

        if (!empty($this->_subject)) {
            $subject = iconv_mime_encode('', $this->_subject, array(
                'scheme'        => 'Q',
                'line-length'   => 500,
            ));
            $subject = ':subject ' . $this->_quoteString(substr($subject, 2), false) . ' ';
        } else {
            $subject = null;
        }

        $reason = $this->_reason;
        $plaintextReason = $this->_getPlaintext($reason);
        $formattedHTMLBody = $this->_getFormattedHTMLBody($reason);

        if (! empty($this->_mime)) {
            $mime = ':mime ';
            $contentType = 'Content-Type: ' . $this->_mime;
            if ($this->_mime == self::MIME_TYPE_MULTIPART_ALTERNATIVE) {
                // @todo use Zend_Mime ?
                $contentType .= "; boundary=foo\r\n\r\n";
                $reason = sprintf(
                    "--foo\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n%s\r\n\r\n"
                    . "--foo\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n%s\r\n\r\n"
                    . "--foo--",
                    $plaintextReason,
                    $formattedHTMLBody
                );
            } else {
                $contentType .= "; charset=UTF-8\r\n\r\n";
            }

            if ($this->_mime == self::MIME_TYPE_TEXT_PLAIN) {
                $reason = $plaintextReason;
            }
        } else {
            $mime = null;
            $contentType = null;
            $reason = $plaintextReason;
        }

        $vacation = sprintf(
            "vacation %s%s%s%s%stext:\r\n%s%s\r\n.\r\n;",
            $days,
            $subject,
            $from,
            $addresses,
            $mime,
            $contentType,
            $reason
        );

        if ($this->useDates()) {
            $conditions = array();
            if ($this->_enddate !== null) {
                $conditions[] = 'currentdate :value "le" "date" "' . $this->_enddate . '"';
            }
            if ($this->_startdate !== null) {
                $conditions[] = 'currentdate :value "ge" "date" "' . $this->_startdate . '"';
            }
            if (count($conditions) > 0) {
                $vacation = 'if allof(' . implode(",\r\n", $conditions) . ")\r\n{" . $vacation . "}\r\n";
            }
        }

        return $vacation;
    }

    /**
     * @param string|null $reason
     * @return string
     */
    protected function _getFormattedHTMLBody(?string $reason): string
    {
        if (empty($reason)) {
            $reason = '';
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = true;
        $doc->loadHTML(
            '<?xml version="1.0" encoding="utf-8"?>' . $reason,
            LIBXML_NOERROR | // Suppress any errors
            LIBXML_NOWARNING        // or warnings about prefixes.
        );
        $doc->saveXML();
        $xpath = new DOMXPath($doc);
        $body = $xpath->query('/html/body')->item(0);
        $bodyNodes = is_object($body) ? $body->childNodes : [];
        $outputFragments = [];
        foreach ($bodyNodes as $bodyNode) {
            $outputFragments[] = $doc->saveXML($bodyNode);
        }
        return implode(PHP_EOL, $outputFragments);
    }

    /**
     * quote string for usage in Sieve script
     *
     * @todo generalize this
     * @param string|array $_string
     * @param bool $_replaceComma
     * @return string
     */
    protected function _quoteString($_string, bool $_replaceComma = true): string
    {
        if (is_array($_string)) {
            $string = array_map(array($this, '_quoteString'), $_string);
            $string = '[' . implode(',', $string) . ']';
        } else {
            $string = '"' . str_replace('"', '\"', $_string) . '"';
            if ($_replaceComma) {
                $string = str_replace(',', '', $string);
            }
        }

        return $string;
    }

    /**
     * convert to plaintext
     *
     * @param string $_string
     */
    protected function _getPlaintext($_string)
    {
        return Felamimail_Message::convertContentType(Zend_Mime::TYPE_HTML, Zend_Mime::TYPE_TEXT, $_string);
    }

    /**
     * return values as array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'addresses'             => $this->_addresses,
            'subject'               => $this->_subject,
            'from'                  => $this->_from,
            'days'                  => $this->_days,
            'enabled'               => $this->_enabled,
            'reason'                => (empty($this->_mime) || $this->_mime == self::MIME_TYPE_TEXT_PLAIN)
                ? $this->_getPlaintext($this->_reason) : $this->_reason,
            'mime'                  => $this->_mime,
            'start_date'            => $this->_startdate,
            'end_date'              => $this->_enddate,
            'date_enabled'          => $this->_dateEnabled
        );
    }
}
