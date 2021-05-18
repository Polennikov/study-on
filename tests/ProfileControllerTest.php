<?php

namespace App\Tests;

use Symfony\Component\Serializer\SerializerInterface;

class ProfileControllerTest extends AbstractTest
{
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }

    public function testProfile(): void
    {
        $auth = new SecurityControllerTest();
        // Формируем данные для авторизации
        $data = [
            'email' => 'artem@mail.ru',
            'password' => 'Artem48',
        ];
        $requestData = $this->serializer->serialize($data, 'json');

        // Авторизация пользователя и редирект на страницу курсов
        $crawler = $auth->authorization($requestData);

        $client = self::getClient();
        // Проверка перехода на страницу курсов
        $crawler = $client->request('GET', '/course/');
        $this->assertResponseOk();

        // Переход на страницу профиля
        $link = $crawler->selectLink('профиль')->link();
        $client->click($link);

        // Проверка перехода на страницу профиля
        $crawler = $client->request('GET', '/profile');
        $this->assertResponseOk();

        // Провверка наличия полей на странице
        $username = $crawler->filter('#name');
        self::assertCount(1, $username);
        $username = $crawler->filter('#balance');
        self::assertCount(1, $username);
        $username = $crawler->filter('#users');
        self::assertCount(1, $username);
    }

    public function testAccessTransactionHistory(): void
    {
        $auth = new SecurityControllerTest();
        // Формируем данные для авторизации
        $data = [
            'email' => 'artem@mail.ru',
            'password' => 'Artem48',
        ];
        $requestData = $this->serializer->serialize($data, 'json');

        // Авторизация пользователя и редирект на страницу курсов
        $crawler = $auth->authorization($requestData);
        $client = self::getClient();

        // Переходим на страницу профиля
        $btn = $crawler->filter('a.profile')->link();
        $crawler = $client->click($btn);
        $this->assertResponseOk();

        // Выбираем ссылку на историю транзакций
        $transactionHistLink = $crawler->filter('a.transaction_history')->link();
        // Переходим на страницу истории транзакций
        $crawler = $client->click($transactionHistLink);
        $this->assertResponseOk();

        $table = $crawler->filter('.table')->first();
        // Должна быть одна таблица
        self::assertEquals(1, $table->count());
    }
}
