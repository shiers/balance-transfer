<?php
/**
 * File:        Transfer.php
 *
 * Author:      Shawn Shiers
 */

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\TransferRepository;

/**
 * Transfer
 *
 * @ORM\Entity(repositoryClass=TransferRepository::class)
 * @ORM\Table(name="Transfer")
 */
class Transfer
{
    public function __toString(): string
    {
        return $this->getAmount() .' from ' . $this->getCustomerFrom(). ' to ' . $this->getCustomerTo();
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2)
     * @Assert\Type("float")
     */
    private $amount;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_from", type="string", length=255)
     */
    private $customerFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_to", type="string", length=255)
     */
    private $customerTo;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return DateTime|null
     */
    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string|null
     */
    public function getCustomerFrom(): ?string
    {
        return $this->customerFrom;
    }

    /**
     * @param string $customerFrom
     */
    public function setCustomerFrom(string $customerFrom): void
    {
        $this->customerFrom = $customerFrom;
    }

    /**
     * @return string|null
     */
    public function getCustomerTo(): ?string
    {
        return $this->customerTo;
    }

    /**
     * @param string $customerTo
     */
    public function setCustomerTo(string $customerTo): void
    {
        $this->customerTo = $customerTo;
    }
}
