<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            'Основы БЖД',
            'Архитектура ПС',
            'Администрирование вмногопользовательских системах',
            'Защита информации',
        ];
        foreach ($courses as $name) {
            $course = new Course();
            $course->setName($name);
            $course->setDescription('Курс ' . $name . ' поможет получить вам необходимые навыки в данной области');
            $course->setCode(random_int(400, 1000));
            $manager->persist($course);

            $count = random_int(3, 5);
            for ($i = 0; $i < $count; ++$i) {
                $lesson = new Lesson();
                $lesson->setName('Какой-то предмет по курсу ' . $name . ' номер ' . $i);
                $lesson->setContent('Описание предмета по курсу ' . $name . ' номер ' . $i);
                $lesson->setNumber($i);
                $lesson->setCourse($course);
                $manager->persist($lesson);
            }
        }
        $manager->flush();
    }
}
