<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $typeDocument = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 15)]
    private ?string $telephone = null;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Address::class)]
    private Collection $clientAddress;

    public function __construct()
    {
        $this->clientAddress = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTypeDocument(): ?string
    {
        return $this->typeDocument;
    }

    public function setTypeDocument(string $typeDocument): static
    {
        $this->typeDocument = $typeDocument;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getClientAddress(): Collection
    {
        return $this->clientAddress;
    }

    public function addClientAddress(Address $clientAddress): static
    {
        if (!$this->clientAddress->contains($clientAddress)) {
            $this->clientAddress->add($clientAddress);
            $clientAddress->setClient($this);
        }

        return $this;
    }

    public function removeClientAddress(Address $clientAddress): static
    {
        if ($this->clientAddress->removeElement($clientAddress)) {
            // set the owning side to null (unless already changed)
            if ($clientAddress->getClient() === $this) {
                $clientAddress->setClient(null);
            }
        }

        return $this;
    }
}
