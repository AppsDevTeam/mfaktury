<?php

namespace ADT\MFaktury;


use ADT\MFaktury\Entity\Invoice;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class MFaktury
{
	protected string $apiToken;
	protected ?int $queueId;
	protected string $baseUri = 'https://ac.mfaktury.cz/api/';
	protected string $contactUrl = 'contacts';
	protected string $contactListUrl = 'contactslist';
	protected string $invoiceUrl = 'invoices';
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
		$client = new Client([
			'base_uri' => $this->baseUri,
		]);

		$data = [
			'form_params' => array_merge($data, ['api_token' => $this->apiToken]),
		];

		return json_decode((string)$client->post($url, $data)->getBody());
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
			'year' => $invoice->getYear(),
			'number' => $invoice->getNumber(),
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

	public function deleteInvoice(int $invoiceID)
	{
		return $this->request(
			$this->invoiceUrl,
			[
				'id' => $invoiceID,
				'delete_invoice' => true,
			]
		);
	}

	public function updateInvoiceIsPaid(int $invoiceID)
	{
		return $this->request(
			$this->invoiceUrl,
			[
				'id' => $invoiceID,
				'is_paid' => true,
			]
		);
	}
}

class InvoiceNotCreatedException extends \Exception {}

