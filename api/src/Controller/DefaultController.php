<?php

// src/Controller/DefaultController.php

namespace App\Controller;

//use App\Service\RequestService;
use App\Service\MailingService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The DefaultController test handles any calls that have not been picked up by another test, and wel try to handle the slug based against the wrc.
 *
 * Class DefaultController
 *
 * @Route("/")
 */
class DefaultController extends AbstractController
{
    /**
     * @Route("/")
     * @Template
     */
    public function indexAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/register")
     * @Template
     */
    public function registerAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/login")
     * @Template
     */
    public function loginAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if ($request->query->get('backUrl')) {
            $variables['backUrl'] = $request->query->get('backUrl');
        }

        return $variables;
    }

    /**
     * @Route("/reset/{token}")
     * @Template
     */
    public function resetAction(Session $session, Request $request, CommonGroundService $commonGroundService, MailingService $mailingService, ParameterBagInterface $params, $token = null)
    {
        $variables = [];

        if ($token) {
            $application = $commonGroundService->getResource(['component'=>'wrc', 'type'=>'applications', 'id' => $params->get('app_id')]);
            $providers = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'token', 'application' => $params->get('app_id')])['hydra:member'];
            $tokens = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $token, 'provider.name' => $providers[0]['name']])['hydra:member'];
            if (count($tokens) > 0) {
                $variables['token'] = $tokens[0];
                $userUlr = $commonGroundService->cleanUrl(['component'=>'uc', 'type'=>'users', 'id'=>$tokens[0]['user']['id']]);
                $variables['selectedUser'] = $userUlr;
            }
        }

        if ($request->isMethod('POST') && $request->get('password')) {
            $user = $commonGroundService->getResource($request->get('selectedUser'));
            $password = $request->get('password');

            $user['password'] = $password;

            foreach ($user['userGroups'] as &$group) {
                $group = '/groups/'.$group['id'];
            }

            $commonGroundService->updateResource($user);

            $variables['reset'] = true;
        } elseif ($request->isMethod('POST')) {
            $variables['message'] = true;
            $username = $request->get('email');
            $users = $commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $username], true, false, true, false, false);
            $users = $users['hydra:member'];

            $application = $commonGroundService->getResource(['component'=>'wrc', 'type'=>'applications', 'id' => $params->get('app_id')]);
            $organization = $application['organization']['@id'];
            $providers = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'token', 'application' => $params->get('app_id')])['hydra:member'];

            if (count($users) > 0) {
                $user = $users[0];
                $person = $commonGroundService->getResource($user['person']);

                $validChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $code = substr(str_shuffle(str_repeat($validChars, ceil(3 / strlen($validChars)))), 1, 5);

                $token = [];
                $token['token'] = $code;
                $token['user'] = 'users/'.$user['id'];
                $token['provider'] = 'providers/'.$providers[0]['id'];
                $token = $commonGroundService->createResource($token, ['component' => 'uc', 'type' => 'tokens']);

                $url = $request->getUri();
                $link = $url.'/'.$token['token'];

                $data = [];
                $data['resource'] = $link;
                $data['sender'] = 'no-reply@conduction.nl';

                $mailingService->sendMail('mails/password_reset.html.twig', 'no-reply@conduction.nl', $user['username'], 'Password reset', $data);
            }
        }

        return $variables;
    }

    /**
     * @Route("/error")
     * @Template
     */
    public function errorAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/users")
     * @Template
     */
    public function usersAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/organizations")
     * @Template
     */
    public function organizationsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/developers")
     * @Template
     */
    public function developersAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/pricing")
     * @Template
     */
    public function pricingAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/oauth")
     * @Template
     */
    public function oauthAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if (!$request->query->get('client_id')) {
            $this->addFlash('error', 'no client id provided');
        } else {
            try {
                $variables['application'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $request->query->get('client_id')]);
            } catch (\Throwable $e) {
                $this->addFlash('error', 'invalid client id');
            }
        }

        if (!$request->query->get('response_type') || $request->query->get('response_type') !== 'code') {
            $this->addFlash('error', 'invalid response type');
        }

        if (!$request->query->get('scopes')) {
            $this->addFlash('error', 'no scopes provided');
        } else {
            $variables['scopes'] = explode(' ', $request->query->get('scopes'));
        }

        if (!$request->query->get('state')) {
            $variables['state'] = $request->query->get('state');
        }

        $session->set('backUrl', $request->getUri());

        if ($request->isMethod('POST') && $request->get('grantAccess')) {
            if ($request->get('grantAccess') == 'true') {
                // TD: create token & send back to authorization url defined in application
            } else {
                // TD:send message back that access was denied.
            }
        }

        return $variables;
    }
}
