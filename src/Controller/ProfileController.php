<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use App\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CourseRepository;
use DateInterval;
use DateTime;
class ProfileController extends AbstractController
{
    /**
     * @Route("/profile", name="profile" )
     */
    public function index(BillingClient $billingClient): Response
    {
        $this->denyAccessUnlessGranted(
            'ROLE_USER',
            $this->getUser(),
            'У вас нет доступа к этой странице'
        );

        try {
            $result = $billingClient->getCurrentUser($this->getUser());
        } catch (BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }

        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'name'            => $result['username'],
            'balance'         => $result['balance'],
        ]);
    }

    /**
     * @Route("/transactions", name="transactions")
     */
    public function transactions(BillingClient $billingClient, CourseRepository $courseRepository): Response
    {
        $this->denyAccessUnlessGranted(
            'ROLE_USER',
            $this->getUser(),
            'У вас нет доступа к этой странице'
        );

        // Получаем транзакции пользователя
        try {
            $transactions = $billingClient->getTransactionUserPayment($this->getUser(), 'null');
        } catch ( BillingUnavailableException $e) {
            throw new \Exception($e->getMessage());
        }
        $coursesData=[];
        foreach ($transactions as $transaction) {

            if (isset($transaction['course_code'])) {
                $course        = $courseRepository->findOneBy(['code' => $transaction['course_code']]);
                $coursesData[] = $this->courseFilter(
                    $course->getCode(),
                    $course->getName(),
                    $transaction['type'],
                    $transaction['amount'],
                    $transaction['created_at'],
                    $course
                );
            } else {
                $coursesData[] = $this->courseFilter(
                    null,
                    null,
                    $transaction['type'],
                    $transaction['amount'],
                    $transaction['created_at'],
                    null
                );
            }

        }
        return $this->render('profile/Transactions.html.twig', [

            'transactions' => $coursesData,
        ]);
    }

    private function courseFilter(
        ?string $code,
        ?string $name,
        string $type,
        string $cost,
        string $created_at,
        ?Course $course
    ): array {
        return [
            'code'        => $code,
            'name'        => $name,
            'type'        => $type,
            'cost'        => $cost,
            'created_at'  => $created_at,
            'course'      => $course,
        ];
    }

}
