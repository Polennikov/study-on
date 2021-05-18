<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
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
                'refresh_token' => $this->generateToken('ROLE_USER', 'artem@mail.ru'),
            ];
        }
        if ('admin@mail.ru' === $data['username'] && 'Admin48' === $data['password']) {
            return [
                'token' => $this->generateToken('ROLE_SUPER_ADMIN', 'admin@mail.ru'),
                'refresh_token' => $this->generateToken('ROLE_SUPER_ADMIN', 'admin@mail.ru'),
/*                'username'      => 'admin@mail.ru',
                'roles'         => ['ROLE_SUPER_ADMIN'],*/
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
            'token' => $this->generateToken('ROLE_USER', $dataUser['email']),
            'refresh_token' => $this->generateToken('ROLE_SUPER_ADMIN', $dataUser['email']),
        ];
    }

    public function getCurrentUser(User $user): array
    {
        return [
            'code' => 200,
            'token' => $this->generateToken('ROLE_USER', $user->getEmail()),
            'username' => $user->getEmail(),
            'roles' => ['ROLE_USER'],
            'balance' => 1000,
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

    public function getAllCourse(): array
    {
        return [
            [
                'code' => '1111',
                'type' => 'free',
                'cost' => 0,
            ],
            [
                'code' => '1112',
                'type' => 'rent',
                'cost' => 150,
            ],
            [
                'code' => '1113',
                'type' => 'buy',
                'cost' => 5000,
            ],
            [
                'code' => '1114',
                'type' => 'rent',
                'cost' => 300,
            ],
        ];
    }

    public function getTransactionUserPayment(User $user, string $request): array
    {
        return [];
    }

    public function getCourse(string $courseCode): array
    {
        return [
            'code' => '1114',
            'type' => 'rent',
            'cost' => 150,
            'name' => 'Защита информации',
        ];
    }

    public function payCourse(User $user, string $courseCode): array
    {
        return [
                'success' => true,
                'course_type' => 'rent',
                'expires_at' => '2021-05-23T19:50:49+00:00',
            ];
    }

    public function newCourse(User $user, string $request): array
    {
        return ['success' => true];
    }

    public function editCourse(User $user, string $code, string $request): array
    {
        return ['success' => true];
    }
}
