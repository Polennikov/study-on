<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Lesson;
use Symfony\Component\Serializer\SerializerInterface;

class LessonControllerTest extends AbstractTest
{
    // Стартовая страница курсов
    public $pageCourse = '/course';
    public $pageLesson = '/lesson';
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$container->get(SerializerInterface::class);
    }

    // Переопределение метода для фикстур
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    // Проверка запретов доступа к страницам
    public function testLessonPageNO(): void
    {
        $em = static::getEntityManager();
        $lesson = $em->getRepository(Lesson::class)->findByOneLesson();
        self::assertNotEmpty($lesson);
        // Проверим все GET/POST запросы
        self::getClient()->request('GET', $this->pageLesson . '/' . $lesson[0]->getId());
        $this->assertResponseCode(302);
        self::getClient()->request('GET', $this->pageLesson . '/' . $lesson[0]->getId() . '/edit');
        $this->assertResponseCode(302);
        self::getClient()->request('POST', $this->pageLesson . '/' . $lesson[0]->getId() . '/edit');
        $this->assertResponseCode(302);
    }

    // Проверка на корректный http-статус
    public function testLessonPageOk(): void
    {
        $auth = new SecurityControllerTest();
        // Авторизация под ролью ROLE_SUPER_ADMIN
        $data = [
            'email' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];

        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->authorization($requestData);

        $em = static::getEntityManager();
        $lesson = $em->getRepository(Lesson::class)->findByOneLesson();
        self::assertNotEmpty($lesson);
        // Проверим все GET/POST запросы
        self::getClient()->request('GET', $this->pageLesson . '/' . $lesson[0]->getId());
        $this->assertResponseOk();
        self::getClient()->request('GET', $this->pageLesson . '/' . $lesson[0]->getId() . '/edit');
        $this->assertResponseOk();
        self::getClient()->request('POST', $this->pageLesson . '/' . $lesson[0]->getId() . '/edit');
        $this->assertResponseOk();
    }

    // Проверка на ошибки перехода по страницам
    public function testLessonPageNotfound(): void
    {
        $auth = new SecurityControllerTest();
        // Авторизация под ролью ROLE_SUPER_ADMIN
        $data = [
            'email' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];

        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->authorization($requestData);

        $client = self::getClient();
        $crawler = $client->request('GET', $this->pageCourse . '/-25');
        $this->assertResponseNotFound();
        self::getClient()->request('GET', $this->pageLesson . '/new');
        $this->assertResponseRedirect();
    }

    // Проверка добавления урока и проверка валидации
    public function testLessonCreate(): void
    {
        $auth = new SecurityControllerTest();
        // Авторизация под ролью ROLE_SUPER_ADMIN
        $data = [
            'email' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];

        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->authorization($requestData);

        $client = self::getClient();

        // Перейдем на страницу первого курса
        $link = $crawler->filter('a.courseShow')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Посчитаем уроки до добавления нового
        $lessonsCountBefore = $crawler->filter('div.lessons')->count();

        $code = '00000';
        $content = 'Новый курс (редактирование)';
        $number = 'Описание курса (редактирование)';
        $overCharacters = 'sadjskadkasjdddddddasdkkkkkkkkksadjskadkasjdddddddasdkkkkkk
            kkkkkkkkkkasdkkkkkkkkkkkkkkkkkkasdllllllllllllllllllll
            llllllllllllllllllllllasdjjjjjjjjjjjjjjjjjjjjjjjjjjjjj
            jjjjjjjjjjjjjjjasdllllllllllllllllllllllllllllsadkasdk
            asdknqowhduiqbwdnoskznmdoasmpodpasmdpamsdsadddddddddda
            sssssssssssssssssssssssssssssssssssssssssssssssddddddd
            dddddddddddddddddddddddddddddddddddddddddddddddddddddd
            dddddddddddddddddddddddddddsssssssssssssssssssssssssss
            ssssssssssssssssssssssssssssssssssssssssssssssssssssss
            ssssssssssssssssssssssssssssssssssssssssssssssssssssss
            sssssadjskadkasjdddddddasdkkkkkkkkkkkkkkkkasdkkkkkkkkk';

        // Перейдём на страницу создания нового урока
        $link = $crawler->filter('a.LessonAdd')->link();
        $client->click($link);
        $this->assertResponseOk();

        // Выполним нажатие кнопки Сохранить
        // Проверка поля number на ввод текста
        $crawler = $client->submitForm('AddLesson', [
            'lesson[name]' => '289289',
            'lesson[content]' => $overCharacters,
            'lesson[number]' => 'jhjn',
        ]);
        $error0 = $crawler->filter('li');
        // Проверка поля number на ввод числа >1000
        $crawler = $client->submitForm('AddLesson', [
            'lesson[name]' => '289280',
            'lesson[content]' => $overCharacters,
            'lesson[number]' => 10001,
        ]);
        $error1 = $crawler->filter('li');
        // Проверка поля number на ввод числа <0
        $crawler = $client->submitForm('AddLesson', [
            'lesson[name]' => '289289',
            'lesson[content]' => $overCharacters,
            'lesson[number]' => -10001,
        ]);
        $error2 = $crawler->filter('li');
        // Проверка поля name на ввод больше 255 символов
        $crawler = $client->submitForm('AddLesson', [
            'lesson[name]' => $overCharacters,
            'lesson[content]' => $overCharacters,
            'lesson[number]' => 676,
        ]);
        $error3 = $crawler->filter('li');
        // Вывод списка ошибок
        self::assertSame(
            [
                'This value is not valid.',
                'This value should be less than or equal to 1000.',
                'This value is not valid.',
                'This value is too long. It should have 255 characters or less.',
            ],
            [$error0->text(), $error1->text(), $error2->text(), $error3->text()]);
        // Корректное добавление урока
        $crawler = $client->submitForm('AddLesson', [
            'lesson[name]' => '289289',
            'lesson[content]' => $overCharacters,
            'lesson[number]' => 676,
        ]);
        // Выберем последний урок для возврата на страницу
        $lesson = static::getEntityManager()->getRepository(Lesson::class)->findByLastLesson();
        // Проверка редиректа на страницу курса
        self::assertTrue($client->getResponse()->isRedirect($this->pageCourse . '/' . $lesson[0]->getCourse()->getId()));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        // Посчитаем уроки после добавления нового
        $lessonsCountAfter = $crawler->filter('div.lessons')->count();
        // Вывод списка ошибок
        self::assertSame($lessonsCountAfter, ($lessonsCountBefore + 1));
    }

    // Проверка редактирования урока
    public function testCourseEdit(): void
    {
        $auth = new SecurityControllerTest();
        // Авторизация под ролью ROLE_SUPER_ADMIN
        $data = [
            'email' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];

        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->authorization($requestData);

        $client = self::getClient();

        // Перейдем на страницу последнего добаленного курса
        $link = $crawler->filter('a.courseShow')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Перейдем на страницу последнего добаленного урока
        $linkLessons = $crawler->filter('a.lessonShow')->last()->link();
        $crawler = $client->click($linkLessons);
        $this->assertResponseOk();

        // Переходим на страницу редактирования этого урока
        $link = $crawler->filter('a.LessonEdit')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Нажимаем кнопку редактировать
        $crawler = $client->submitForm('AddLesson', [
            'lesson[name]' => '289289',
            'lesson[content]' => 'Тест редактирование',
            'lesson[number]' => 289,
        ]);
        // Выбираем из бд последний добавленный урок
        $lesson = self::getEntityManager()->getRepository(Lesson::class)->findByLastLesson();
        // Проверка редиректа на страницу урока
        self::assertTrue($client->getResponse()->isRedirect($this->pageLesson . '/' . $lesson[0]->getId()));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
    }

    // Проверка удаления урока
    public function testLessonDelete(): void
    {
        $auth = new SecurityControllerTest();
        // Авторизация под ролью ROLE_SUPER_ADMIN
        $data = [
            'email' => 'admin@mail.ru',
            'password' => 'Admin48',
        ];

        $requestData = $this->serializer->serialize($data, 'json');
        $crawler = $auth->authorization($requestData);

        $client = self::getClient();

        // Получим курсы из бд до удаления
        $lessonsBeforeDel = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        // Получим последний добавленный урок
        $lessonsLast = self::getEntityManager()->getRepository(Lesson::class)->findByLastLesson();

        // Перейдем на страницу последнего добавленного курса
        $linkCourses = $crawler->filter('a.courseShow')->last()->link();
        $crawler = $client->click($linkCourses);
        $this->assertResponseOk();

        // Перейдем на страницу последнего добавленного урока
        $linkLessons = $crawler->filter('a.lessonShow')->last()->link();
        $crawler = $client->click($linkLessons);
        $this->assertResponseOk();

        // нажимаем кнопку удалить
        $client->submitForm('DeleteLesson');
        // проверка редиректа на страницу курса
        self::assertTrue($client->getResponse()->isRedirect($this->pageCourse . '/' . $lessonsLast[0]->getCourse()->getId()));
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Проверка удаления элемента из бд
        $lessonsAfterDel = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        self::assertSame(count($lessonsBeforeDel), (count($lessonsAfterDel) + 1));
    }
}
