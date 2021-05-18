<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/course")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("/", name="course_index", methods={"GET"})
     */
    public function index(
        CourseRepository $courseRepository,
        BillingClient $billingClient,
        SerializerInterface $serializer
    ): Response {
        // Получаем все курсы из биллинга
        try {
            if ($this->getUser()) {
                $transaction = $billingClient->getTransactionUserPayment($this->getUser(),
                    'type=payment&skip_expired=1');
            } else {
                $transaction = [];
            }
            $coursesBilling = $billingClient->getAllCourse();
            $coursesData = [];
            foreach ($coursesBilling as $courseBilling) {
                // Ищем курс, который вернулся с сервиса оплаты, в репозитории
                $course = $courseRepository->findOneBy(['code' => $courseBilling['code']]);
                if ($course) {
                    //var_dump( $course->getId());
                    $cost = null;
                    $type = null;
                    $purchased = null;
                    $expires_at = null;
                    if ('null' != $this->courseFindBuy($course->getCode(), $transaction)) {
                        $type = $courseBilling['type'];
                        if ('free' == $type) {
                            $purchased = 'бесплатно';
                        }
                        if ('rent' == $type) {
                            $purchased = 'арендовано';
                            $cost = $courseBilling['cost'];
                            $expires_at = $this->courseFind($course->getCode(), $transaction);
                        }
                        if ('buy' == $type) {
                            $cost = $courseBilling['cost'];
                            $purchased = 'куплено';
                        }
                    } else {
                        $type = $courseBilling['type'];
                        if ('free' == $type) {
                            $purchased = 'бесплатно';
                        }
                        if ('rent' == $type) {
                            $purchased = 'аренда';
                            $cost = $courseBilling['cost'];
                        }
                        if ('buy' == $type) {
                            $purchased = 'покупка';
                            $cost = $courseBilling['cost'];
                        }
                    }
                    $coursesData[] = $this->courseFilter(
                        $course->getId(),
                        $course->getCode(),
                        $course->getName(),
                        $course->getDescription(),
                        $type,
                        $cost,
                        $purchased,
                        $expires_at);
                }
            }

            return $this->render('course/index.html.twig', [
                'courses' => $coursesData,
            ]);
        } catch (BillingUnavailableException $e) {
            throw new BillingUnavailableException($e->getMessage());
        }
    }

    private function courseFind(
        string $code,
        array $transaction
    ): string {
        foreach ($transaction as $item) {
            if ($item['course_code'] == $code && isset($item['validityPeriod'])) {
                return $item['validityPeriod'];
            }
        }

        return 'null';
    }

    private function courseFindBuy(
        string $code,
        array $transaction
    ): string {
        foreach ($transaction as $item) {
            if ($item['course_code'] == $code) {
                return 'true';
            }
        }

        return 'null';
    }

    private function courseFilter(
        int $id,
        string $code,
        string $name,
        string $description,
        ?string $type,
        ?float $cost,
        ?string $purchased,
        ?string $expires_at
    ): array {
        return [
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'cost' => $cost,
            'purchased' => $purchased,
            'expires_at' => $expires_at,
        ];
    }

    /**
     * @Route("/new", name="course_new", methods={"GET","POST"})
     */
    public function new(Request $request, BillingClient $billingClient, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted(
            'ROLE_SUPER_ADMIN',
            $this->getUser(),
            'У вас нет доступа к этой странице'
        );

        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($course);
                $entityManager->flush();

                // Запрос в сервис billing для создания курса
                $dataRequest = [
                'type' => $form->get('type')->getNormData(),
                'name' => $form->get('name')->getNormData(),
                'code' => $form->get('code')->getNormData(),
                'cost' => $form->get('cost')->getNormData(),
            ];
                $billingClient->newCourse($this->getUser(), $serializer->serialize($dataRequest, 'json'));
            } catch (BillingUnavailableException $e) {
                // flash message
                $this->addFlash('message', 'Возникла ошибка!');

                return $this->render('course/new.html.twig', [
                    'course' => $course,
                    'form' => $form->createView(),
                ]);
            }

            return $this->redirectToRoute('course_index');
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/pay", name="course_pay", methods={"GET"})
     */
    public function pay(Request $request, BillingClient $billingClient): Response
    {
        $this->denyAccessUnlessGranted(
            'ROLE_USER',
            $this->getUser(),
            'У вас нет доступа к этой странице'
        );
        // Откуда перешли на данную страницу для обратного редиректа
        $referer = $request->headers->get('referer');

        if (null === $referer) {
            return $this->redirectToRoute('course_index');
        }

        $courseCode = $request->get('id');
        try {
            $billingClient->payCourse($this->getUser(), $courseCode);
            // flash message
            $this->addFlash('message', 'Оплата прошла успешно!');
        } catch (BillingUnavailableException $e) {
            $this->addFlash('message', 'Оплата не прошла!');
            throw new \Exception($e->getMessage());
        }

        return $this->redirect($referer);
    }

    /**
     * @Route("/{id}", name="course_show", methods={"GET"})
     */
    public function show(Course $course, BillingClient $billingClient, LessonRepository $lessonRepository): Response
    {
        try {
            $lessons = $this->getDoctrine()
                ->getRepository(Lesson::class)
                ->findByIdLesson($course);

            $courseBilling = $billingClient->getCourse($course->getCode());

            if ($this->getUser()) {
                $transaction = $billingClient->getTransactionUserPayment($this->getUser(),
                    'type=payment&code=' . $course->getCode() . '&skip_expired=1');
                if (isset($transaction[0]) && 1 == $transaction[0]['type']) {
                    if ('rent' == $courseBilling['type'] && isset($transaction[0]['validityPeriod'])) {
                        $coursesData = $this->courseFilter(
                            $course->getId(),
                            $course->getCode(),
                            $course->getName(),
                            $course->getDescription(),
                            $courseBilling['type'],
                            $courseBilling['cost'],
                            'yes',
                            $transaction[0]['validityPeriod']
                        );

                        return $this->render('course/show.html.twig', [
                            'balance' => $this->getUser()->getBalance(),
                            'course' => $coursesData,
                            'lessons' => $lessons,
                        ]);
                    }
                    if ('buy' == $courseBilling['type']) {
                        $coursesData = $this->courseFilter(
                            $course->getId(),
                            $course->getCode(),
                            $course->getName(),
                            $course->getDescription(),
                            $courseBilling['type'],
                            $courseBilling['cost'],
                            'yes',
                            null
                        );

                        return $this->render('course/show.html.twig', [
                            'balance' => $this->getUser()->getBalance(),
                            'course' => $coursesData,
                            'lessons' => $lessons,
                        ]);
                    }
                    if ('free' == $courseBilling['type']) {
                        $coursesData = $this->courseFilter(
                            $course->getId(),
                            $course->getCode(),
                            $course->getName(),
                            $course->getDescription(),
                            $courseBilling['type'],
                            null,
                            'yes',
                            null
                        );

                        return $this->render('course/show.html.twig', [
                            'balance' => $this->getUser()->getBalance(),
                            'course' => $coursesData,
                            'lessons' => $lessons,
                        ]);
                    }
                } else {
                    $coursesData = $this->courseFilter(
                        $course->getId(),
                        $course->getCode(),
                        $course->getName(),
                        $course->getDescription(),
                        $courseBilling['type'],
                        $courseBilling['cost'],
                        'no',
                        null
                    );

                    return $this->render('course/show.html.twig', [
                        'balance' => $this->getUser()->getBalance(),
                        'course' => $coursesData,
                        'lessons' => $lessons,
                    ]);
                }
            } else {
                $coursesData = $this->courseFilter(
                    $course->getId(),
                    $course->getCode(),
                    $course->getName(),
                    $course->getDescription(),
                    $courseBilling['type'],
                    $courseBilling['cost'],
                    'no',
                    null
                );

                return $this->render('course/show.html.twig', [
                    'balance' => null,
                    'course' => $coursesData,
                    'lessons' => $lessons,
                ]);
            }
        } catch (AccessDeniedException $e) {
            throw new \Exception($e->getMessage());
        } catch (BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @Route("/{id}/edit", name="course_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, BillingClient $billingClient, Course $course, SerializerInterface $serializer): Response
    {
        $this->denyAccessUnlessGranted(
            'ROLE_SUPER_ADMIN',
            $this->getUser(),
            'У вас нет доступа к этой странице'
        );
        // Запрос с billing для получения информации о курсе
        $courseData = $billingClient->getCourse($course->getCode());
        $courseCode = $course->getCode();

        $form = $this->createForm(CourseType::class, $course);
        $form->get('type')->setData($courseData['type']);
        $form->get('cost')->setData($courseData['cost'] ?? 0);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dataRequest = [
                'type' => $form->get('type')->getNormData(),
                'name' => $form->get('name')->getNormData(),
                'code' => $form->get('code')->getNormData(),
                'cost' => $form->get('cost')->getNormData(),
            ];
            $responseData = $billingClient->editCourse(
                $this->getUser(),
                $courseCode,
                $serializer->serialize($dataRequest, 'json')
            );

            // Если в сервисе billing успешно изменился курс, то сохраняем изменения
            if (isset($responseData['success'])) {
                $this->getDoctrine()->getManager()->flush();

                return $this->redirect('/course/' . $course->getId());
            } else {
                // flash message
                $this->addFlash('message', $responseData['message']);
            }
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="course_delete", methods={"DELETE"})
     */
    public function delete(
        Request $request,
        Course $course
    ): Response {
        $this->denyAccessUnlessGranted(
            'ROLE_SUPER_ADMIN',
            $this->getUser(),
            'У вас нет доступа к этой странице'
        );

        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('course_index');
    }
}
