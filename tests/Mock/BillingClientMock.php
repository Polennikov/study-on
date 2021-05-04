<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
//use App\Entity\User;
use App\Security\User;
use App\Service\BillingClient;

class BillingClientMock extends BillingClient
{
    public function auth(string $request): array
    {
        $data = json_decode($request, true);
        if ('artem@mail.ru' === $data['username'] && 'Artem48' === $data['password']) {
            return [
                'token' => $this->generateToken('ROLE_USER', 'artem@mail.ru'),
                'username' => 'artem@mail.ru',
                'roles' => ['ROLE_USER'],
            ];
        }
        if ('admin@mail.ru' === $data['username'] && 'Admin48' === $data['password']) {
            return [
                'token' => $this->generateToken('ROLE_SUPER_ADMIN', 'admin@mail.ru'),
                'username' => 'admin@mail.ru',
                'roles' => ['ROLE_SUPER_ADMIN'],
            ];
        }
        throw new BillingUnavailableException('Проверьте правильность введёного логина и пароля');
    }

    public function register(string $request): array
    {
        $dataUser = json_decode($request, true);
        // Симуляция обработки уже существующих пользователей
        if ('artem@mail.ru' === $dataUser['email'] | 'admin@mail.ru' === $dataUser['email']) {
            throw new BillingUnavailableException('Данный пользователь уже существует');
        }

        return [
            'code' => 200,
            'token' => $this->generateToken('ROLE_USER', $dataUser['email']),
            'username' => $dataUser['email'],
            'roles' => ['ROLE_USER'],
        ];
    }

    public function getCurrentUser(User $user): array
    {
        return [
            'code' => 200,
            'token' => $this->generateToken('ROLE_USER', $user->getEmail()),
            'username' => $user->getEmail(),
            'roles' => ['ROLE_USER'],
            'balance' => 100,
        ];
    }

    private function generateToken(string $role, string $username): string
    {
        $roles = null;
        if ('ROLE_USER' === $role) {
            $roles = ['ROLE_USER'];
        } elseif ('ROLE_SUPER_ADMIN' === $role) {
            $roles = ['ROLE_SUPER_ADMIN'];
        }
        $data = [
            'username' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));

        return 'header.' . $query . '.signature';
    }
}
