<?php

namespace NW\WebService\Entity;

/**
* @property Seller $Seller
*/
abstract class AbstractUser
{
	public const TYPE = -1;
	protected int $id;
	protected $type;
	protected ?string $name = null;

	protected function __construct(int $id)
	{
		$this->id = $id;
		$this->type = static::TYPE;
	}

	public static function getById(int $id): self
	{
		return new static($id); // fakes the getById method
	}

	public function getFullName(): string
	{
		return trim($this->name . ' ' . $this->id);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * @param string|null $name
	 * @return $this
	 */
	public function setName(?string $name)
	{
		$this->name = $name;

		return $this;
	}

	public function getType()
	{
		return $this->type;
	}

	public function isType(int $expectedType): bool
	{
		return $this->type === $expectedType;
	}

}