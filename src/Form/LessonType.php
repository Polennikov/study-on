<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class LessonType extends AbstractType
{
    private $eM;

    public function __construct(EntityManagerInterface $entityManagerInterface)
    {
        $this->eM = $entityManagerInterface;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('content', TextareaType::class)
            ->add('number', NumberType::class, [
                'constraints' => [
                    new Regex([
                        'pattern' => '#^[0-9]+$#',
                    ]),
                ],
            ])
            ->add('course', HiddenType::class);
        $builder->get('course')
            ->addModelTransformer(new CallbackTransformer(
                function (Course $course) {
                    return $course->getId();
                },
                function (int $courseId) {
                    return $this->eM->getRepository(Course::class)->find($courseId);
                }
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
