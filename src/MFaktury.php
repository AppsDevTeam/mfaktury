<?php

namespace ADT\MFaktury;


use ADT\MFaktury\Entity\Invoice;

class MFaktury
{

	protected string $apiToken;
	protected ?int $queueId;
	protected string $contactUrl = 'https://ac.mfaktury.cz/api/contacts';
	protected string $contactListUrl = 'https://ac.mfaktury.cz/api/contactslist';
	protected string $invoiceUrl = 'https://ac.mfaktury.cz/api/invoices';
	protected ?int $businessPremise; // číslo provozovny EET
	protected ?string $cashRegister; // označení pokladny EET

	public function __construct(
		$apiKey,
		$queueId = null,
		$businessPremise = null,
		$cashRegister = null
	)
	{
		$this->apiToken = $apiKey;
		$this->queueId = $queueId;
		$this->businessPremise = $businessPremise;
		$this->cashRegister = $cashRegister;
	}

	protected function request(string $url, array $data = [])
	{
		$options = [
			'http' => [
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query(array_merge($data, ['api_token' => $this->apiToken])),
			],
		];

		return json_decode(file_get_contents($url, false, stream_context_create($options)));
	}

	public function listContact()
	{
		return $this->request($this->contactListUrl);
	}

	/**
	 * Vytvorí faktúru z proformy podľa ID
	 */
	public function createInvoiceFromProform(int $proformId): object
	{
		return $this->request(
			$this->invoiceUrl,
			[
				'id' => $proformId,
				'issue_invoice' => true,
			],
		);
	}

	/**
	 * @param Invoice $invoice
	 *
	 * @return object
	 * @throws InvoiceNotCreatedException
	 * @throws \ADT\Mfaktury\Entity\UnrecognizedPaymentMethodType
	 */
	public function createInvoice(Invoice $invoice): object
	{
		$customerData = [
			'auto_generate' => 0,
			'company' => $invoice->getCustomer()->getName(),
			'street' => $invoice->getCustomer()->getAddress(),
			'city' => $invoice->getCustomer()->getCity(),
			'zip' => $invoice->getCustomer()->getPostalCode(),
			'country' => $invoice->getCustomer()->getCountry(),
			'ic' => $invoice->getCustomer()->getCompanyID(),
			'dic' => $invoice->getCustomer()->getVatID(),
			'comp_email' => $invoice->getCustomer()->getEmail(),
		];

		$customerID = (int) trim($this->request($this->contactUrl, $customerData), '"');

		$items = [];
		foreach ($invoice->getItems() as $_item) {
			$items[] = [
				'description' => $_item->getDescription(),
				'price' => $_item->getPrice(),
				'tax' => $_item->getVatRate(),
				'quantity' => $_item->getQuantity(),
			];
		}

		$invoiceData = [
			'queue_id' => $this->queueId,
			'type' => $invoice->getType(),
			'contact' => $customerID,
			'sendEmail' => $invoice->isEmailToCustomerEnabled(),
			'lang' => $invoice->getLang(),
			'payment_method' => $invoice->getPaymentMethod(),
			'interval_exp' => $invoice->getDueInDays(),
			'issue_invoice' => $invoice->getIssueInvoice(),
			'vs' => $invoice->getVs(),
			'currency' => $invoice->getCurrency(),
			'qr_code' => $invoice->isQrCodeEnabled(),
			'internal_note' => $invoice->getNote(),
			'items' => $items,
		];

		$response = $this->request($this->invoiceUrl, $invoiceData);

		if (empty($response->link)) {
			throw new InvoiceNotCreatedException("\nDECODED:\n" . print_r($response, true) . "\n");
		}

		return $response;
	}

	public function getInvoice(int $invoiceID)
	{
		$data = [
			'id' => $invoiceID
		];

		return $this->request($this->invoiceUrl, $data);
	}

	public function searchInvoice(string $string)
	{
		$data = [
			'search' => $string
		];

		$invoice = $this->request($this->invoiceUrl, $data);

		if (empty((array) $invoice)) {
			return null;
		}

		return $invoice;
	}
}

class InvoiceNotCreatedException extends \Exception {}

