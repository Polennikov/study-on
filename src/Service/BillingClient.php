<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;
use App\Security\User;

class BillingClient
{
    private $baseUri;

    public function __construct()
    {
        $this->baseUri = 'billing.study-on.local';
    }

    /**
     * @throws BillingUnavailableException
     */
    public function auth(string $request): array
    {
        // Формирование запроса в сервис Billing
        $curl = curl_init($this->baseUri.'/api/v1/auth');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($request),
        ]);
        $response = curl_exec($curl);
        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен. Попробуйте авторизоваться позднее.');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getCurrentUser(User $user): array
    {
        // Формирование запроса в сервис Billing
        $curl = curl_init($this->baseUri.'/api/v1/current');
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$user->getApiToken(),
        ]);

        $response = curl_exec($curl);

        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен.
            Попробуйте авторизоваться позднее');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function register(string $request): array
    {
        // Формирование запроса в сервис Billing
        $curl = curl_init($this->baseUri.'/api/v1/register');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($request),
        ]);
        $response = curl_exec($curl);

        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен. Попробуйте зарегистироваться позднее.');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @param   string  $refresh_token
     *
     * @return mixed
     * @throws \App\Exception\BillingUnavailableException
     */
    public function refresh(string $refresh_token): array
    {
        // Формирование запроса в сервис Billing
        $curl = curl_init($this->baseUri.'/api/v1/token/refresh');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $refresh_token);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($curl);

        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен. Попробуйте зарегистироваться позднее.');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getAllCourse(): array
    {
        // Формирование запроса в сервис Billing
        $curl = curl_init($this->baseUri.'/api/v1/courses/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($curl);

        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен. Попробуйте зарегистироваться позднее.');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);

        return $result;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function getTransactionUserPayment(User $user, string $request): array
    {
        $request = '?'.$request;
        // Формирование запроса в сервис Billing
        $curl = curl_init($this->baseUri.'/api/v1/transactions/'.$request);
        curl_setopt($curl, CURLOPT_HTTPGET, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$user->getApiToken(),
        ]);
        $response = curl_exec($curl);

        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен. Попробуйте зарегистироваться позднее.');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);


        return $result;

    }

    /**
     * @throws BillingUnavailableException
     */
    public function getCourse(string $courseCode): array
    {
        // Запрос в сервис биллинг, получение данных
        $curl = curl_init($this->baseUri.'/api/v1/courses/'.$courseCode);
        curl_setopt($curl, CURLOPT_HTTPGET, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $response = curl_exec($curl);

        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен. Попробуйте зарегистироваться позднее.');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);


        return $result;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function payCourse(User $user,string $courseCode): array
    {
        // Запрос в сервис биллинг, получение данных
        $curl = curl_init($this->baseUri . '/api/v1/courses/' . $courseCode . '/pay');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $user->getApiToken()
        ]);
        $response = curl_exec($curl);

        // Ошибка биллинга
        if (!$response) {
            throw new BillingUnavailableException('Сервис временно недоступен. Попробуйте зарегистироваться позднее.');
        }

        curl_close($curl);

        // Ответ от сервиса
        $result = json_decode($response, true);


        return $result;
    }
}
