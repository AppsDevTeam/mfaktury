<?php

namespace ADT\Mfaktury;

use GuzzleHttp\Client;

class Mfaktury
{
	const TYPE_PAID = 0;
	const TYPE_PROFORMA = 1;

	const INTERVAL_EXP_PAID = -1;

	const PAYMENT_METHOD_CASH = 'hotovost';
	const PAYMENT_METHOD_TRANSFER = 'převod';
	const PAYMENT_METHOD_PAYMENT_CARD = 'platba kartou';
	const PAYMENT_METHOD_CASH_ON_DELIVERY = 'dobírka';
	const PAYMENT_METHOD_ONLINE = 'on-line platební brána';

	protected $apiToken;

	protected $queueId;

	protected string $contactUrl = 'https://ac.mfaktury.cz/api/contacts';

	protected string $contactListUrl = 'https://ac.mfaktury.cz/api/contactslist';

	protected string $invoiceUrl = 'https://ac.mfaktury.cz/api/invoices';

	/** @var int číslo provozovny EET */
	protected $businessPremise;

	/** @var string označení pokladny EET */
	protected $cashRegister;

	public function __construct($params)
	{
		$this->apiToken = $params['api_key'];
		$this->queueId = $params['queue_id'] ?? null;
		$this->businessPremise = $params['businessPremise'] ?? null;
		$this->cashRegister = $params['cashRegister'] ?? null;
	}

	protected function request(array $data, string $url)
	{
		return json_decode((new Client())->post($url, ['form_params' => $data])->getBody(), true);
	}

	/**
	 * @param string $company
	 * @param string $street
	 * @param string $city
	 * @param string $zip
	 * @param string $email
	 *
	 * @return int|false
	 */
	public function createContactByCredentials(
		string $company,
		string $street,
		string $city,
		string $zip,
		string $email
	): int
	{
		$data = [
			'api_token' => $this->apiToken,
			'auto_generate' => 0,
			'company' => $company,
			'street' => $street,
			'city' => $city,
			'zip' => $zip,
			'comp_email' => $email,
		];

		return (int) trim($this->request($data, $this->contactUrl), '"');
	}

	/**
	 *
	 * Plati pouze pro ČR, vyhledáva podle IČ v DB ARES
	 *
	 *	"ic": "1821849",
	 *	"comp_email": "test@example.com",
	 *
	 * @param string $ic
	 * @param string $email
	 *
	 * @return int|false
	 */
	public function createContactByIc(
		string $ic,
		string $email
	)
	{
		$data = [
			'auto_generate' => 1,
			'api_token' => $this->apiToken,
			'ic' => $ic,
			'comp_email' => $email,
		];

		return (int) trim($this->request($data, $this->contactUrl), '"');
	}

	public function listContact()
	{
		return $this->request(['api_token' => $this->apiToken], $this->contactListUrl);
	}

	/**
	 * @param int $contactId
	 * @param string $lang
	 * @param string $description
	 * @param float $price
	 * @param int $vatRate
	 * @param string $currencyCode
	 * @param string $paymentMethod
	 * @param int $intervalExp
	 * @param string|null $internalNote
	 * @param int $type
	 * @param string|null $vs
	 * @param string|null $eet_fik
	 * @param string|null $eet_bkp
	 * @param bool $sendEmail
	 *
	 * @return string
	 * @throws InvoiceNotCreatedException
	 */
	public function createInvoice(
		int $contactId,
		string $lang,
		string $description,
		float $price,
		int $vatRate,
		string $currencyCode,
		string $paymentMethod,
		int $intervalExp = self::INTERVAL_EXP_PAID,
		?string $internalNote = null,
		int $type = self::TYPE_PROFORMA,
		?string $vs = null,
		?string $eet_fik = null,
		?string $eet_bkp = null,
		bool $sendEmail = false
	): string
	{
		$data = [
			'api_token' => $this->apiToken,
			'type' => $type,
			'contact' => $contactId,
			'sendEmail' => (int)$sendEmail,
			'lang' => $lang,
			'payment_method' => $paymentMethod,
			'interval_exp' => $intervalExp,
			'vs' => $vs,
			'currency' => $currencyCode,
			'internal_note' => $internalNote,
			'items' => [
				[
					'description' => $description,
					'price' => $price,
					'tax' => $vatRate,
				],
			],
		];

		if ($eet_fik !== null && $eet_bkp !== null) {
			$data = array_merge($data, [
				'fik' => $eet_fik,
				'bkp' => $eet_bkp,
				'business_premise' => $this->businessPremise,
				'cash_register' => $this->cashRegister,
			]);
		}

		if ($this->queueId) {
			$data['queue_id'] = $this->queueId;
		}

		$response = $this->request($data, $this->invoiceUrl);

		if (empty($response->link)) {
			throw new InvoiceNotCreatedException("\nDECODED:\n" . print_r($response, true) . "\n");
		}

		return $response->link;
	}
}

class InvoiceNotCreatedException extends \Exception {}

