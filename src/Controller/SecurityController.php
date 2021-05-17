<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Form\RegisterType;
use App\Security\User;
use App\Security\UserAuthenticator;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Serializer\SerializerInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     *
     * @param   AuthenticationUtils  $authenticationUtils
     *
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('course_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/register", name="register")
     *
     * @param   Request                                                      $request
     * @param   SerializerInterface                                          $serializer
     * @param   BillingClient                                                $billingClient
     * @param   \Symfony\Component\Security\Guard\GuardAuthenticatorHandler  $guardAuthenticatorHandler
     * @param   \App\Security\UserAuthenticator                              $UserAuthenticator
     *
     * @return Response
     */
    public function register(
        Request $request,
        SerializerInterface $serializer,
        BillingClient $billingClient,
        GuardAuthenticatorHandler $guardAuthenticatorHandler,
        UserAuthenticator $UserAuthenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('profile');
        }

        $error = null;
        $form = $this->createForm(RegisterType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Формируем данные для запроса
                $data = $serializer->serialize($form->getData(), 'json');
                // Запрос к сервису для регистрации пользователя
                $response = $billingClient->register($data);
                if (isset($response['token'])) {
                    // Создаем пользователя
                    $user = new User();
                    $user->setEmail($form->getData()['email']);
                    $user->setPassword($form->getData()['password']);
                    $user->setApiToken($response['token']);
                    $user->setRefreshToken($response['refresh_token']);
                    // Авторизация пользователя
                    $guardAuthenticatorHandler->authenticateUserAndHandleSuccess(
                        $user,
                        $request,
                        $UserAuthenticator,
                        'main'
                    );

                    // Переход на страницу курсов
                    return $this->redirectToRoute('course_index');
                } else {
                    $error = $response['message'];
                }
            } catch (BillingUnavailableException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
