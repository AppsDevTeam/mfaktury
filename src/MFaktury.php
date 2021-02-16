<?php

namespace ADT\MFaktury;


use ADT\MFaktury\Entity\Invoice;

class MFaktury
{

	protected string $apiToken;
	protected ?string $queueId;
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

	protected function request(array $data, string $url)
	{
		$options = [
			'http' => [
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data),
			],
		];

		return json_decode(file_get_contents($url, false, stream_context_create($options)));
	}

	public function listContact()
	{
		return $this->request(['api_token' => $this->apiToken], $this->contactListUrl);
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
			'api_token' => $this->apiToken,
			'auto_generate' => 0,
			'company' => $invoice->getCustomer()->getName(),
			'street' => $invoice->getCustomer()->getAddress(),
			'city' => $invoice->getCustomer()->getCity(),
			'zip' => $invoice->getCustomer()->getPostalCode(),
			'country' => $invoice->getCustomer()->getCountry(),
			'ic' => $invoice->getCustomer()->getCompanyID(),
			'vatId' => $invoice->getCustomer()->getVatID(),
			'comp_email' => $invoice->getCustomer()->getEmail(),
		];

		$customerID = (int) trim($this->request($customerData, $this->contactUrl), '"');

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
			'api_token' => $this->apiToken,
			'type' => $invoice->getType(),
			'contact' => $customerID,
			'sendEmail' => $invoice->isEmailToCustomerEnabled(),
			'lang' => $invoice->getLang(),
			'payment_method' => $invoice->getPaymentMethod(),
			'interval_exp' => $invoice->getDueInDays(),
			'vs' => $invoice->getVs(),
			'currency' => $invoice->getCurrency(),
			'internal_note' => $invoice->getNote(),
			'items' => $items,
		];

		$response = $this->request($invoiceData, $this->invoiceUrl);

		if (empty($response->link)) {
			throw new InvoiceNotCreatedException("\nDECODED:\n" . print_r($response, true) . "\n");
		}

		return $response;
	}

	public function getInvoice(int $invoiceID)
	{
		$data = [
			'api_token' => $this->apiToken,
			'id' => $invoiceID
		];

		return $this->request($data, $this->invoiceUrl);
	}
}

class InvoiceNotCreatedException extends \Exception {}

