<?php

namespace App\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserService
{
    private $commonGroundService;
    private $mailService;
    private $defaultService;
    private $router;

    public function __construct(CommonGroundService $commonGroundService, MailingService $mailingService, DefaultService $defaultService, UrlGeneratorInterface $router)
    {
        $this->commonGroundService = $commonGroundService;
        $this->mailService = $mailingService;
        $this->defaultService = $defaultService;
        $this->router = $router;
    }

    public function createUser($username)
    {
        $validChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $user = [];
        $user['person'] = $this->createPerson($username);
        $user['username'] = $username;
        $user['password'] = substr(str_shuffle(str_repeat($validChars, ceil(3 / strlen($validChars)))), 1, 5);

        try {
            $user = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'users']);
            $this->passwordMail($user);

            return $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function createPerson($name)
    {
        $names = explode('@', $name);

        $person = [];
        $person['givenName'] = $names[0];
        $person = $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);

        return $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
    }

    public function passwordMail($user)
    {
        $provider = $this->defaultService->getProvider('token');
        $validChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = substr(str_shuffle(str_repeat($validChars, ceil(3 / strlen($validChars)))), 1, 5);

        $token = [];
        $token['token'] = $code;
        $token['user'] = 'users/'.$user['id'];
        $token['provider'] = 'providers/'.$provider['id'];
        $token = $this->commonGroundService->createResource($token, ['component' => 'uc', 'type' => 'tokens']);

        $url = $this->router->generate('app_default_reset', [], UrlGenerator::ABSOLUTE_URL).'/'.$token['token'];
        $data['resource'] = $url;

        $this->mailService->sendMail('mails/newUserPassword.html.twig', 'no-reply@id-vault.com', $user['username'], 'Welcome', $data);
    }
}
