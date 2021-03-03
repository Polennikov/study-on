<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
    	$courses =['Основы БЖД',
    	'Архитектура ПС',
    	'Администрирование вмногопользовательских системах',
    	'Защита информации'];
    	foreach($courses as $name){
    		$course = new Course();
    		$course->setNameCourse($name);
    		$course->setDescription('Курс '. $name.' поможет получить вам необходимые навыки в данной области');
    		$course->setCode(random_int(100,200));
    		$manager->persist($course);
		$manager->flush();
		
		$count = random_int(3,5);
		for($i = 0; $i<$count; $i++){
	    		$lesson = new Lesson();
	    		$lesson->setNameLesson('Какой-то предмет по курсу '.$name);
	    		$lesson->setContentLesson('Описание предмета по курсу '.$name);
	    		$lesson->setNumberLesson($i);
	    		$lesson->setCourse($course);
	    		$manager->persist($lesson);
			$manager->flush();		
    		}		
    	}
    }
}