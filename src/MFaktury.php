<?php

namespace ADT\MFaktury;


use ADT\MFaktury\Entity\Invoice;
use ADT\MFaktury\Entity\InvoiceItem;
use DateTime;
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
			// API bere price bez DPH a sazbu v % v tax (libovolná sazba, výchozí 0) -
			// tax_custom dokumentace API nezná
			$item = [
				'description' => $_item->getDescription(),
				'price' => $_item->getPrice(),
				'quantity' => $_item->getQuantity(),
				'tax' => $_item->getVatRate() ?? 0,
			];

			if ($_item->getUnit()) {
				$item['mj'] = $_item->getUnit();
			}

			if ($_item->getCode()) {
				$item['code'] = $_item->getCode();
			}

			$items[] = $item;
		}

		$invoiceData = [
			'queue_id' => $invoice-> getQueueId() ?: $this->queueId,
			'type' => $invoice->getType(),
			'contact' => $customerID,
			'send_email' => $invoice->isEmailToCustomerEnabled(),
			// starší název parametru, ponechaný kvůli zpětné kompatibilitě
			'sendEmail' => $invoice->isEmailToCustomerEnabled(),
			'send_proforma_to_invoice_email' => $invoice->isProformaToInvoiceEmailToCustomerEnabled(),
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

		if ($invoice->getCorrectiveReason() !== null) {
			$invoiceData['corrective_reason'] = $invoice->getCorrectiveReason();
		}

		if ($invoice->getOriginalInvoiceId() !== null) {
			$invoiceData['original_invoice_id'] = $invoice->getOriginalInvoiceId();
		}

		if ($invoice->getIssuedAt() !== null) {
			$invoiceData['date_iss'] = $invoice->getIssuedAt()->format('j.n.Y');
		}

		if ($invoice->getTaxableAt() !== null) {
			$invoiceData['date_tax'] = $invoice->getTaxableAt()->format('j.n.Y');
		}

		// Vlastní datum pro splatnost
		if (!$this->checkIsValidDueInDays($invoice->getDueInDays())) {
			$invoiceData['interval_exp'] = 'c';
			$invoiceData['interval_exp_custom'] = (new DateTime())->modify('+' . $invoice->getDueInDays() . ' days')->format('d.m.Y');
		}

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

	public function updateInvoiceIsPaid(int $invoiceID, ?bool $sendEmail = null)
	{
		return $this->request(
			$this->invoiceUrl,
			[
				'id' => $invoiceID,
				'is_paid' => true,
				'send_email' => $sendEmail
			]
		);
	}

	private function checkIsValidDueInDays(int $dueInDays): bool
	{
		return in_array($dueInDays, [
			-1, // uhrazeno
			0, // dnes
			1, // zítra
			7, // týden
			14, // 14 dní
			21, // 21 dní
			31, // měsíc
		]);
	}
}

class InvoiceNotCreatedException extends \Exception {}

