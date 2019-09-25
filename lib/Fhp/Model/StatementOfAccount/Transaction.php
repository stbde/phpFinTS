<?php

namespace Fhp\Model\StatementOfAccount;

/**
 * Class Transaction
 * @package Fhp\Model\StatementOfAccount
 */
class Transaction
{
    const CD_CREDIT = 'credit';
    const CD_DEBIT = 'debit';

    /**
     * @var \DateTime|null
     */
    protected $bookingDate;

    /**
     * @var \DateTime|null
     */
    protected $valutaDate;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var string
     */
    protected $creditDebit;

    /**
     * @var string
     */
    protected $bookingCode;

    /**
     * @var string
     */
    protected $bookingText;

    /**
     * @var string
     */
    protected $description1;

    /**
     * @var string
     */
    protected $description2;

    /**
     * Array keys are identifiers like "SVWZ" for the main description.
     * @var string[]
     */
    protected $structuredDescription;

    /**
     * @var string
     */
    protected $bankCode;

    /**
     * @var string
     */
    protected $accountNumber;

    /**
     * @var string
     */
    protected $name;
	
    /**
     * @var boolean
     */
	protected $booked;

    /**
     * @var string
     */
	protected $primanoten_nr;

	/**
     * @var string
     */
	protected $text_key_addition;

	/**
     * @var string
     */
	protected $customerref;

	/**
     * @var string
     */
	protected $instref;

    /**
     * Get booking date.
     *
     * @deprecated Use getBookingDate() instead
     * @codeCoverageIgnore
     * @return \DateTime|null
     */
    public function getDate()
    {
        return $this->getBookingDate();
    }

    /**
     * Get booking date
     *
     * @return \DateTime|null
     */
    public function getBookingDate()
    {
        return $this->bookingDate;
    }

    /**
     * Get date
     *
     * @return \DateTime|null
     */
    public function getValutaDate()
    {
        return $this->valutaDate;
    }

    /**
     * Set booking date
     *
     * @param \DateTime|null $date
     *
     * @return $this
     */
    public function setBookingDate(\DateTime $date = null)
    {
        $this->bookingDate = $date;

        return $this;
    }

    /**
     * Set valuta date
     *
     * @param \DateTime|null $date
     *
     * @return $this
     */
    public function setValutaDate(\DateTime $date = null)
    {
        $this->valutaDate = $date;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

	
	
    /**
     * Set booked status
     *
     * @param boolean $booked
     *
     * @return $this
     */
    public function setBooked($booked)
    {
        $this->booked = $booked;

        return $this;
    }
	
    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = (float) $amount;

        return $this;
    }

    /**
     * Get creditDebit
     *
     * @return string
     */
    public function getCreditDebit()
    {
        return $this->creditDebit;
    }

    /**
     * Set creditDebit
     *
     * @param string $creditDebit
     *
     * @return $this
     */
    public function setCreditDebit($creditDebit)
    {
        $this->creditDebit = $creditDebit;

        return $this;
    }

    /**
     * Get bookingCode
     *
     * @return string
     */
    public function getBookingCode()
    {
        return $this->bookingCode;
    }

    /**
     * Set bookingCode
     *
     * @param string $bookingCode
     *
     * @return $this
     */
    public function setBookingCode($bookingCode)
    {
        $this->bookingCode = (string) $bookingCode;

        return $this;
    }

    /**
     * Get bookingText
     *
     * @return string
     */
    public function getBookingText()
    {
        return $this->bookingText;
    }

    /**
     * Set bookingText
     *
     * @param string $bookingText
     *
     * @return $this
     */
    public function setBookingText($bookingText)
    {
        $this->bookingText = (string) $bookingText;

        return $this;
    }

    /**
     * Get description1
     *
     * @return string
     */
    public function getDescription1()
    {
        return $this->description1;
    }

    /**
     * Set description1
     *
     * @param string $description1
     *
     * @return $this
     */
    public function setDescription1($description1)
    {
        $this->description1 = (string) $description1;

        return $this;
    }

    /**
     * Get description2
     *
     * @return string
     */
    public function getDescription2()
    {
        return $this->description2;
    }

    /**
     * Set description2
     *
     * @param string $description2
     *
     * @return $this
     */
    public function setDescription2($description2)
    {
        $this->description2 = (string) $description2;

        return $this;
    }

    /**
     * Get structuredDescription
     *
     * @return string[]
     */
    public function getStructuredDescription()
    {
        return $this->structuredDescription;
    }

    /**
     * Set structuredDescription
     *
     * @param string[] $structuredDescription
     *
     * @return $this
     */
    public function setStructuredDescription($structuredDescription)
    {
        $this->structuredDescription = $structuredDescription;

        return $this;
    }

    /**
     * Get the main description (SVWZ)
     *
     * @return string
     */
    public function getMainDescription()
    {
        if (array_key_exists('SVWZ', $this->structuredDescription)) {
            return $this->structuredDescription['SVWZ'];
        } else {
            return "";
        }
    }

    /**
     * Get the end to end id (EREF)
     *
     * @return string
     */
    public function getEndToEndID()
    {
        if (array_key_exists('EREF', $this->structuredDescription)) {
            return $this->structuredDescription['EREF'];
        } else {
            return "";
        }
    }

    /**
     * Get bankCode
     *
     * @return string
     */
    public function getBankCode()
    {
        return $this->bankCode;
    }

    /**
     * Set bankCode
     *
     * @param string $bankCode
     *
     * @return $this
     */
    public function setBankCode($bankCode)
    {
        $this->bankCode = (string) $bankCode;

        return $this;
    }

    /**
     * Get accountNumber
     *
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * Set accountNumber
     *
     * @param string $accountNumber
     *
     * @return $this
     */
    public function setAccountNumber($accountNumber)
    {
        $this->accountNumber = (string) $accountNumber;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get booked status
     *
     * @return boolean
     */
	public function getBooked()
	{
		return $this->booked;
	}
	
    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

	/**
     * Get primanoten_nr
     *
     * @return string
     */
	public function getPrimanotenNr()
	{
		return $this->primanoten_nr;
	}

	/**
     * Set primanoten_nr
     *
     * @param string $primanoten_nr
     *
     * @return $this
     */
	public function setPrimanotenNr($primanoten_nr)
	{
		$this->primanoten_nr = (string) $primanoten_nr;

        return $this;
	}

	/**
     * Get text_key_addition
     *
     * @return string
     */
	public function getTextKeyAddition()
	{
		return $this->text_key_addition;
	}

	/**
     * Set text_key_addition
     *
     * @param string $text_key_addition
     *
     * @return $this
     */
	public function setTextKeyAddition($text_key_addition)
	{
		$this->text_key_addition = (string) $text_key_addition;

        return $this;
	}

	/**
     * Get customerref
     *
     * @return string
     */
	public function getCustomerref()
	{
		return $this->customerref;
	}

	/**
     * Set customerref
     *
     * @param string $customerref
     *
     * @return $this
     */
	public function setCustomerref($customerref)
	{
		$this->customerref = (string) $customerref;

        return $this;
	}

	/**
     * Get instref
     *
     * @return string
     */
	public function getInstref()
	{
		return $this->instref;
	}

	/**
     * Set instref
     *
     * @param string $instref
     *
     * @return $this
     */
	public function setInstref($instref)
	{
		$this->instref = (string) $instref;

        return $this;
	}
}
