<?php

namespace App\Security;

use App\Service\BillingClient;
use App\Service\DecodeJWT;
use DateInterval;
use DateTime;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private $decodeJwt;
    private $billingClient;

    public function __construct(DecodeJWT $decodeJwt, BillingClient $billingClient)
    {
        $this->decodeJwt = $decodeJwt;
        $this->billingClient = $billingClient;
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        $user = new User();
        $user->setEmail($username);

        return $user;
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        /*        // Return a User object after making sure its data is "fresh".
                // Or throw a UsernameNotFoundException if the user no longer exists.
                // throw new \Exception('TODO: fill in refreshUser() inside '.__FILE__);*/
        $response = $this->decodeJwt->decodeJWT($user->getApiToken());
        $exp = (new DateTime())->setTimestamp($response['exp']);
        $time = (new DateTime())->add(new DateInterval('PT1M'));

        if ($time >= $exp) {
            try {
                $refreshToken = $user->getRefreshToken();
                $response = $this->billingClient->refresh($refreshToken);
                dump($response);
                $user->setApiToken($response['token']);
                $user->setRefreshToken($response['refresh_token']);
            } catch (BillingUnavailableException $e) {
                throw new \Exception($e->getMessage());
            }
        }

        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class)
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Upgrades the encoded password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        // TODO: when encoded passwords are in use, this method should:
        // 1. persist the new password in the user storage
        // 2. update the $user object with $user->setPassword($newEncodedPassword);
    }
}
