<?php

namespace ADT\MFaktury\Entity;


class Invoice
{
	const PAYMENT_METHOD_CASH = 'hotovost';
	const PAYMENT_METHOD_TRANSFER = 'převod';
	const PAYMENT_METHOD_PAYMENT_CARD = 'platba kartou';
	const PAYMENT_METHOD_CASH_ON_DELIVERY = 'dobírka';
	const PAYMENT_METHOD_ONLINE = 'on-line platební brána';

	const TYPE_INVOICE = 0;
	const TYPE_PROFORMA = 1;

	const LANG_CS = 'cs';
	const LANG_SK = 'sk';
	const LANG_EN = 'en';

	const CURRENCY_CZK = 'CZK';
	const CURRENCY_EUR = 'EUR';

	const DUE_IN_DAYS_ALREADY_PAID = -1;
	const DUE_IN_DAYS_IMMEDIATELY = 0;

	protected Customer $customer;
	protected string $paymentMethod;
	protected int $type;
	protected bool $emailToCustomerEnabled;
	protected int $dueInDays;
	protected string $currency;
	protected string $lang;
	protected ?string $note;
	protected ?string $vs;
	/** @var InvoiceItem[] $items */
	protected array $items;
	protected ?string $link;
	protected bool $issueInvoice = true;
	protected bool $qrCodeEnabled;
	protected ?string $year;
	protected ?int $number;
	protected ?int $queueId;

	public function __construct(
		array $items,
		Customer $customer,
		int $dueInDays = self::DUE_IN_DAYS_ALREADY_PAID,
		string $paymentMethod = self::PAYMENT_METHOD_TRANSFER,
		int $type = self::TYPE_INVOICE,
		string $lang = self::LANG_CS,
		string $currency = self::CURRENCY_CZK,
		bool $emailToCustomerEnabled = false,
		string $note = null,
		string $vs = null,
		bool $qrCodeEnabled = true,
		string $year = null,
		int $number = null
	)
	{
		$this->items = $items;
		$this->customer = $customer;
		$this->dueInDays = $dueInDays;
		$this->paymentMethod = $paymentMethod;
		$this->type = $type;
		$this->lang = $lang;
		$this->currency = $currency;
		$this->emailToCustomerEnabled = $emailToCustomerEnabled;
		$this->note = $note;
		$this->vs = $vs;
		$this->qrCodeEnabled = $qrCodeEnabled;
		$this->year = $year;
		$this->number = $number;
	}
	
	public function getQueueId()
	{
		return $this->queueId;
	}
	
	public function setQueueId(int $queueId): Invoice
	{
		$this->queueId = $queueId;
		return $this;
	}

	/**
	 * @return Customer
	 */
	public function getCustomer(): Customer
	{
		return $this->customer;
	}

	/**
	 * @param Customer $customer
	 * @return Invoice
	 */
	public function setCustomer(Customer $customer): Invoice
	{
		$this->customer = $customer;
		return $this;
	}

	/**
	 * @return string
	 * @throws UnrecognizedPaymentMethodType
	 */
	public function getPaymentMethod(): string
	{
		switch ($this->paymentMethod) {
			case self::PAYMENT_METHOD_TRANSFER:
				return 'převod';
			case self::PAYMENT_METHOD_ONLINE:
				return 'on-line platební brána';
			case self::PAYMENT_METHOD_CASH:
				return 'hotovost';
			case self::PAYMENT_METHOD_CASH_ON_DELIVERY:
				return 'dobírka';
			case self::PAYMENT_METHOD_PAYMENT_CARD:
				return 'platba kartou';

			default:
				throw new UnrecognizedPaymentMethodType('Unrecognized payment method type.');
		}
	}

	/**
	 * @param int|string $paymentMethod
	 * @return Invoice
	 */
	public function setPaymentMethod($paymentMethod): Invoice
	{
		$this->paymentMethod = $paymentMethod;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @param int $type
	 * @return Invoice
	 */
	public function setType(int $type): Invoice
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isEmailToCustomerEnabled(): bool
	{
		return $this->emailToCustomerEnabled;
	}

	/**
	 * @param bool $emailToCustomerEnabled
	 * @return Invoice
	 */
	public function setEmailToCustomerEnabled(bool $emailToCustomerEnabled): Invoice
	{
		$this->emailToCustomerEnabled = $emailToCustomerEnabled;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getDueInDays(): int
	{
		return $this->dueInDays;
	}

	/**
	 * @param int $dueInDays
	 * @return Invoice
	 */
	public function setDueInDays(int $dueInDays): Invoice
	{
		$this->dueInDays = $dueInDays;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrency(): string
	{
		return $this->currency;
	}

	/**
	 * @param string $currency
	 * @return Invoice
	 */
	public function setCurrency(string $currency): Invoice
	{
		$this->currency = $currency;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLang(): string
	{
		return $this->lang;
	}

	/**
	 * @param string $lang
	 * @return Invoice
	 */
	public function setLang(string $lang): Invoice
	{
		$this->lang = $lang;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getNote(): ?string
	{
		return $this->note;
	}

	/**
	 * @param ?string $note
	 * @return Invoice
	 */
	public function setNote(?string $note): Invoice
	{
		$this->note = $note;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getVs(): ?string
	{
		return $this->vs;
	}

	/**
	 * @param ?string $vs
	 * @return Invoice
	 */
	public function setVs(?string $vs): Invoice
	{
		$this->vs = $vs;
		return $this;
	}

	/**
	 * @return InvoiceItem[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	/**
	 * @param InvoiceItem[] $items
	 * @return Invoice
	 */
	public function setItems(array $items): Invoice
	{
		$this->items = $items;
		return $this;
	}

	public function getIssueInvoice(): bool
	{
		return $this->issueInvoice;
	}

	public function setIssueInvoice(bool $bool): Invoice
	{
		$this->issueInvoice = $bool;
		return $this;
	}

	public function isQrCodeEnabled(): bool
	{
		return $this->qrCodeEnabled;
	}

	public function setQrCodeEnabled(bool $qrCodeEnabled): Invoice
	{
		$this->qrCodeEnabled = $qrCodeEnabled;
		return $this;
	}

	public function getYear(): ?string
	{
		return $this->year;
	}

	public function setYear(?string $year): Invoice
	{
		$this->year = $year;
		return $this;
	}

	public function getNumber(): ?int
	{
		return $this->number;
	}

	public function setNumber(?int $number): Invoice
	{
		$this->number = $number;
		return $this;
	}

}

class UnrecognizedPaymentMethodType extends \Exception {}
