<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass=LessonRepository::class)
 */
class Lesson
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name_lesson;

    /**
     * @ORM\Column(type="text")
     */
    private $content_lesson;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\LessThanOrEqual(10000)
     */
    private $number_lesson;

    /**
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="lessons")
     */
    private $course;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameLesson(): ?string
    {
        return $this->name_lesson;
    }

    public function setNameLesson(string $name_lesson): self
    {
        $this->name_lesson = $name_lesson;

        return $this;
    }

    public function getContentLesson(): ?string
    {
        return $this->content_lesson;
    }

    public function setContentLesson(string $content_lesson): self
    {
        $this->content_lesson = $content_lesson;

        return $this;
    }

    public function getNumberLesson(): ?int
    {
        return $this->number_lesson;
    }

    public function setNumberLesson(?int $number_lesson): self
    {
        $this->number_lesson = $number_lesson;

        return $this;
    }

    public function getCourse(): ?course
    {
        return $this->course;
    }

    public function setCourse(?course $course): self
    {
        $this->course = $course;

        return $this;
    }
}
