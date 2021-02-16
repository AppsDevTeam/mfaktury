<?php

namespace ADT\MFaktury\Entity;


class InvoiceItem
{

	protected string $description;
	protected float $price;
	protected int $quantity;
	protected ?int $vatRate;


	public function __construct(
		string $description,
		float $price,
		int $quantity = 1,
		?int $vatRate = null
	)
	{
		$this->description = $description;
		$this->price = $price;
		$this->quantity = $quantity;
		$this->vatRate = $vatRate;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 * @return InvoiceItem
	 */
	public function setDescription(string $description): InvoiceItem
	{
		$this->description = $description;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getPrice(): float
	{
		return $this->price;
	}

	/**
	 * @param float $price
	 * @return InvoiceItem
	 */
	public function setPrice(float $price): InvoiceItem
	{
		$this->price = $price;
		return $this;
	}

	/**
	 * @return int|null
	 */
	public function getVatRate(): ?int
	{
		return $this->vatRate;
	}

	/**
	 * @param ?int $vatRate
	 * @return InvoiceItem
	 */
	public function setVatRate(?int $vatRate): InvoiceItem
	{
		$this->vatRate = $vatRate;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getQuantity(): int
	{
		return $this->quantity;
	}

	/**
	 * @param int $quantity
	 * @return InvoiceItem
	 */
	public function setQuantity(int $quantity): InvoiceItem
	{
		$this->quantity = $quantity;
		return $this;
	}
}
