<?php

namespace ADT\MFaktury\Entity;


class Customer
{
	protected string $name;
	protected ?string $address;
	protected ?string $city;
	protected ?string $postalCode;
	protected ?string $country;
	protected ?string $companyID; // IČO
	protected ?string $vatID;
	protected ?string $email;

	public function __construct(
		string $name,
		?string $address = null,
		?string $city = null,
		?string $postalCode = null,
		?string $country = null,
		?string $companyID = null,
		?string $vatID = null,
		?string $email = null
	)
	{
		$this->name = $name;
		$this->address = $address;
		$this->city = $city;
		$this->postalCode = $postalCode;
		$this->country = $country;
		$this->companyID = $companyID;
		$this->vatID = $vatID;
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return Customer
	 */
	public function setName(string $name): Customer
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getAddress(): ?string
	{
		return $this->address;
	}

	/**
	 * @param ?string $address
	 * @return Customer
	 */
	public function setAddress(?string $address): Customer
	{
		$this->address = $address;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getCity(): ?string
	{
		return $this->city;
	}

	/**
	 * @param ?string $city
	 * @return Customer
	 */
	public function setCity(?string $city): Customer
	{
		$this->city = $city;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getPostalCode(): ?string
	{
		return $this->postalCode;
	}

	/**
	 * @param ?string $postalCode
	 * @return Customer
	 */
	public function setPostalCode(?string $postalCode): Customer
	{
		$this->postalCode = $postalCode;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getCompanyID(): ?string
	{
		return $this->companyID;
	}

	/**
	 * @param ?string $companyID
	 * @return Customer
	 */
	public function setCompanyID(?string $companyID): Customer
	{
		$this->companyID = $companyID;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getEmail(): ?string
	{
		return $this->email;
	}

	/**
	 * @param ?string $email
	 * @return Customer
	 */
	public function setEmail(?string $email): Customer
	{
		$this->email = $email;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getCountry(): ?string
	{
		return $this->country;
	}

	/**
	 * @param ?string $country
	 * @return Customer
	 */
	public function setCountry(?string $country): Customer
	{
		$this->country = $country;
		return $this;
	}

	/**
	 * @return ?string
	 */
	public function getVatID(): ?string
	{
		return $this->vatID;
	}

	/**
	 * @param ?string $vatID
	 * @return Customer
	 */
	public function setVatID(?string $vatID): Customer
	{
		$this->vatID = $vatID;
		return $this;
	}

}
