<?php

namespace App\Tests;

use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class SecurityControllerTest extends AbstractTest
{
    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }

    // Метод для замены сервиса билинга на Mock версию для тестов
    public function getBillingClient(): void
    {
        // запрещаем перезагрузку ядра, чтобы не сбросилась подмена сервиса при запросе
        self::getClient()->disableReboot();

        self::getClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock()
        );
    }

    public function authorization(string $data)
    {
        $requestData = json_decode($data, true);

        // Замена сервиса
        $this->getBillingClient();
        $client = self::getClient();

        // Переход на страницу с формой авторизации
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        // Заполнение формы
        $form = $crawler->selectButton('войти')->form();
        $form['email'] = $requestData['email'];
        $form['password'] = $requestData['password'];
        $client->submit($form);
        // Проверка перехода на страницу курсов
        $crawler = $client->followRedirect();
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());
        self::assertEquals('/course/', $client->getRequest()->getPathInfo());

        return $crawler;
    }

    // Тесты авторизации пользователя в системе
    public function testAuth(): void
    {
        // Успешная авторизация
        // Формируем данные для авторизации
        $data = [
            'email' => 'artem@mail.ru',
            'password' => 'Artem48',
        ];
        $requestData = $this->serializer->serialize($data, 'json');

        // Авторизация пользователя и редирект на страницу курсов
        $crawler = $this->authorization($requestData);

        // Неудачная авторизация
        $client = self::getClient();
        // Выйдем из аккаунта
        $link = $crawler->selectLink('выход')->link();
        $client->click($link);
        $this->assertResponseRedirect();
        self::assertEquals('/logout', $client->getRequest()->getPathInfo());

        // Редиректит на страницу /
        $crawler = $client->followRedirect();
        self::assertEquals('/', $client->getRequest()->getPathInfo());

        // Формируем невалидные данные для авторизации
        $data = [
            'email' => 'arte',
            'password' => 'rrr',
        ];
        $client = self::getClient();
        // Переход на страницу с формой для авторизации
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        // Заполнение формы
        $form = $crawler->selectButton('войти')->form();
        $form['email'] = $data['email'];
        $form['password'] = $data['password'];
        $client->submit($form);

        // Проверка перехода на страницу авторизации
        $crawler = $client->followRedirect();
        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());
        self::assertEquals('/login', $client->getRequest()->getPathInfo());
    }

    // Тесты регистрации пользователя в системе
    public function testRegister(): void
    {
        $this->getBillingClient();
        $client = static::getClient();

        // Переход на страницу с формой авторизации
        $crawler = $client->request('GET', '/register');

        // Проверка статуса ответа
        $this->assertResponseOk();

        // Формируем невалидные данные для авторизации (существующий пользователь)
        $form = $crawler->selectButton('Регистрация')->form();
        $form['register[email]'] = 'artem@mail.ru';
        $form['register[password][first]'] = 'Artem48';
        $form['register[password][second]'] = 'Artem48';
        $crawler = $client->submit($form);

        // Проверяем ошибки
        $errors = $crawler->filter('li');
        self::assertCount(1, $errors);

        // Формируем невалидные данные для авторизации (короткий и невалидный пароль)
        $form = $crawler->selectButton('Регистрация')->form();
        $form['register[email]'] = 'artem1@mail.ru';
        $form['register[password][first]'] = 'Arte';
        $form['register[password][second]'] = 'Arte';
        $crawler = $client->submit($form);

        // Проверяем ошибки
        $errors = $crawler->filter('li');
        self::assertCount(2, $errors);

        // Формируем валидные данные для авторизации
        $form = $crawler->selectButton('Регистрация')->form();
        $form['register[email]'] = 'artem1@mail.ru';
        $form['register[password][first]'] = 'Artem48';
        $form['register[password][second]'] = 'Artem48';
        $crawler = $client->submit($form);

        // Проверяем ошибки
        $errors = $crawler->filter('li');
        //var_dump($errors);
        self::assertCount(0, $errors);

        // Редирект на главную страницу course/
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        self::assertEquals('/course/', $client->getRequest()->getPathInfo());
    }
}
