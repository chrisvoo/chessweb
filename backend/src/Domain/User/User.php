<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\BaseDomainEntity;
use DomainException;
use JsonSerializable;

class User extends BaseDomainEntity implements JsonSerializable
{
    public const TABLE_NAME = 'users';

    public int $id;

    public string $email;

    public string $password;

    public string $first_name;

    public string $last_name;

    public bool $is_admin = false;

    public bool $valid = true;

    public string $created_at;

    public ?string $updated_at;

    public function hashPassword(): self
    {
        if (empty($this->password)) {
            throw new DomainException('Password is required.');
        }
        $this->password = password_hash($this->password, PASSWORD_DEFAULT, ['cost' => 12]);
        return $this;
    }

    //     #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'is_admin' => $this->is_admin,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
