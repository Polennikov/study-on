<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\Entity\Course;

class CourseControllerTest extends AbstractTest
{
    // Стартовая страница курсов
    public $pageCourse = '/course';

    // Переопределение метода для фикстур
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    // Проверка на корректный http-статус
    public function testCoursePageOk(): void
    {
        $em     = static::getEntityManager();
        $courses = $em->getRepository(Course::class)->findByOneCourse();
        self::assertNotEmpty($courses);
        // Проверим все GET/POST запросы
        self::getClient()->request('GET', $this->pageCourse . '/new');
        $this->assertResponseOk();
        self::getClient()->request('POST', $this->pageCourse . '/new');
        $this->assertResponseOk();
        self::getClient()->request('GET', $this->pageCourse . '/' . $courses[0]->getId());
        $this->assertResponseOk();
        self::getClient()->request('GET', $this->pageCourse . '/' . $courses[0]->getId().'/edit');
        $this->assertResponseOk();
        self::getClient()->request('POST', $this->pageCourse . '/' . $courses[0]->getId().'/edit');
        $this->assertResponseOk();
    }

    // Проверка на ошибки перехода по страницам
    public function testCoursePageNotfound(): void
    {
        $client  = self::getClient();
        $crawler = $client->request('GET', $this->pageCourse . '/-25');
        $this->assertResponseNotFound();
    }

    // Проверка главной страницы курсов
    public function testCourseIndex(): void
    {
        $client  = self::getClient();
        $crawler = $client->request('GET', $this->pageCourse . '/');
        $this->assertResponseOk();

        //  Получаем кол-во курсов из бд
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        self::assertNotEmpty($courses);
        $coursesCountFromBD = count($courses);

        // Получаем кол-во курсов со страницы
        $coursesCount = $crawler->filter('div.card')->count();
        // Проверка
        self::assertEquals($coursesCountFromBD, $coursesCount);
    }

    // Проверка отдельной страницы каждого курса
    public function testCourseShow(): void
    {
        $client = self::getClient();
        //  Получаем кол-во курсов из бд
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            $crawler = $client->request('GET', $this->pageCourse . '/' . $course->getId());
            $this->assertResponseOk();

            // Получаем кол-во предметов курса со страницы
            $lessonsCount = $crawler->filter('div.lessons')->count();
            static::assertEquals(count($course->getLessons()), $lessonsCount);
        }
    }

    // Проверка добавления курса и проверка валидации
    public function testCourseCreate(): void
    {
        $client = self::getClient();
        // Стартовая точка на главной странице с курсами
        $crawler = $client->request('GET', $this->pageCourse . '/');
        $this->assertResponseOk();

        // Перейдём на страницу создания нового курса
        $link = $crawler->filter('a.NewCourse')->link();
        $client->click($link);
        $this->assertResponseOk();
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
            sssssadjskadkasjdddddddasdkkkkkkkkkkkkkkkkasdkkkkkkkkk
            kkkkkkkkkasdllllllllllllllllllllllllllllllllllllllllll
            asdjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjasdllll
            llllllllllllllllllllllllsadkasdkasdknqowhduiqbwdnoskzn
            mdoasmpodpasmdpamsdsaddddddddddassssssssssssssssssssss
            ssssssssssssssssssssssssdddddddddddddddddddddddddddddd
            dddddddddddddddddddddddddddddddddddddddddddddddddddddd
            ddddssssssssssssssssssssssssssssssssssssssssssssssssss
            sssssssssssssssssssssssguyguygyugggggggggggggggggggggg
            kkkkkkkasdkkkkkkkkkkkkkkkkkkasdllllllllllllllllllllllllll
            llllllllllllllllasdjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjj
            jjjjasdllllllllllllllllllllllllllllsadkasdkasdknqowhduiqbwd
            noskznmdoasmpodpasmdpamsduiknmi7ymimyimyimkkkkkkkkkkkkkkkkkkkkkkk';

        // Проверка поля поля name на количество допустимых символов
        // Перейдём на страницу создания нового курса
        $link = $crawler->filter('a.NewCourse')->link();
        $client->click($link);
        $this->assertResponseOk();
        // Выполним нажатие кнопки Сохранить
        $crawler = $client->submitForm('AddCourse', [
            'course[code]'        => '289289',
            'course[name]'        => $overCharacters,
            'course[description]' => 'Тест',
        ]);
        // считывание ошибки с формы
        $error0 = $crawler->filter('li');

        // Проверка поля поля  на количество code на уникальность
        // Выберем из базы один курс
        $em     = static::getEntityManager();
        $course = $em->getRepository(Course::class)->findByOneCourse();
        // Выполним нажатие кнопки Сохранить
        $crawler = $client->submitForm('AddCourse', [
            'course[code]'        => $course[0]->getCode(),
            'course[name]'        => 'Тест',
            'course[description]' => 'Тест',
        ]);
        // считывание ошибки с формы
        $error1 = $crawler->filter('li');

        // Проверка поля поля  на количество description допустимых символов
        $crawler = $client->submitForm('AddCourse', [
            'course[code]'        => '289289',
            'course[name]'        => 'Тест',
            'course[description]' => $overCharacters,
        ]);
        $error2  = $crawler->filter('li');
        // Вывод списка ошибок
        self::assertSame(
            [
                'This value is too long. It should have 255 characters or less.',
                'Поле должно быть уникальным',
                'This value is too long. It should have 1000 characters or less.',
            ]
            ,
            [$error0->text(), $error1->text(), $error2->text()]);

        // Выполним нажатие кнопки Сохранить
        $client->submitForm('AddCourse',
            [
                'course[code]'        => '0887005',
                'course[name]'        => 'Новый курс',
                'course[description]' => 'Описание курса',
            ]);
        // Проверка перехода на главную страницу
        self::assertTrue($client->getResponse()->isRedirect($this->pageCourse . '/'));
        // Переход на галвную страницу
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Сравнение количества курсов после добавления
        // Получаем кол-во курсов на странице
        $coursesCount = $crawler->filter('div.card')->count();
        // Получаем кол-во курсов из бд
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        self::assertEquals(count($courses), $coursesCount);

    }

    // Проверка редактирования курса
    public function testCourseEdit(): void
    {
        $client  = self::getClient();
        $crawler = $client->request('GET', $this->pageCourse . '/');
        $this->assertResponseOk();

        // Перейдем на страницу курса
        $link    = $crawler->filter('a.courseShow')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // Нажимаем кнопку редактирования
        $link    = $crawler->filter('a.CourseEdit')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // поля для изменения
        $code        = '00000';
        $name        = 'Новый курс (редактирование)';
        $description = 'Описание курса (редактирование)';

        // Выполним нажатие кнопки редактировать
        $client->submitForm('AddCourse',
            [
                'course[code]'        => $code,
                'course[name]'        => $name,
                'course[description]' => $description,
            ]);

        // Получим измененный курс по полю code
        $em     = static::getEntityManager();
        $course = $em->getRepository(Course::class)->findOneBy(['code' => $code]);

        // Проверка перехода на страницу измененного курса
        self::assertTrue($client->getResponse()->isRedirect($this->pageCourse . '/' . $course->getId()));
        // Переход на страницу измененного курса
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // Проверка остальных полей
        self::assertEquals($course->getName(), $name);
        self::assertEquals($course->getDescription(), $description);
    }

    // Проверка удаления курса
    public function testCourseDelete(): void
    {

        $client = self::getClient();
        // Стартовая точка на главной странице с курсами
        $crawler = $client->request('GET', $this->pageCourse . '/');
        $this->assertResponseOk();

        do {
            // Перейдём на страницу последнего добавленного курса
            $link = $crawler->filter('a.courseShow')->last()->link();
            $client->click($link);
            $this->assertResponseOk();

            // Выполним нажатие кнопки удалить
            $client->submitForm('DeleteCourse');
            // Проверка перехода на главную страницу
            self::assertTrue($client->getResponse()->isRedirect($this->pageCourse . '/'));
            // Переход на галвную страницу
            $crawler = $client->followRedirect();
            $this->assertResponseOk();

            // Сравнение количества курсов после удаления
            // Получаем кол-во курсов на странице
            $coursesCount = $crawler->filter('div.card')->count();
            // Получаем кол-во курсов из бд
            $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
            self::assertEquals(count($courses), $coursesCount);
        } while (count($courses) > 0);
    }
}