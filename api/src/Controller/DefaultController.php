<?php

namespace App\Controller;

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
    private $session;
    private $request;
    private $commonGroundService;
    private $mailingService;

    public function __construct(Session $session, Request $request, CommonGroundService $commonGroundService, MailingService $mailingService)
    {
        $this->session = $session;
        $this->request = $request;
        $this->commonGroundService = $commonGroundService;
        $this->mailingService = $mailingService;
    }

    /**
     * @Route("/")
     * @Template
     */
    public function indexAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/register")
     * @Template
     */
    public function registerAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/login")
     * @Template
     */
    public function loginAction()
    {
        $variables = [];

        if ($this->request->query->get('backUrl')) {
            $variables['backUrl'] = $this->request->query->get('backUrl');
        }

        return $variables;
    }

    /**
     * @Route("/reset/{token}")
     * @Template
     */
    public function resetAction(ParameterBagInterface $params, $token = null)
    {
        $variables = [];

        if ($token) {
            $application = $this->commonGroundService->getResource(['component'=>'wrc', 'type'=>'applications', 'id' => $params->get('app_id')]);
            $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'token', 'application' => $params->get('app_id')])['hydra:member'];
            $tokens = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $token, 'provider.name' => $providers[0]['name']])['hydra:member'];
            if (count($tokens) > 0) {
                $variables['token'] = $tokens[0];
                $userUlr = $this->commonGroundService->cleanUrl(['component'=>'uc', 'type'=>'users', 'id'=>$tokens[0]['user']['id']]);
                $variables['selectedUser'] = $userUlr;
            }
        }

        if ($this->request->isMethod('POST') && $this->request->get('password')) {
            $user = $this->commonGroundService->getResource($this->request->get('selectedUser'));
            $password = $this->request->get('password');

            $user['password'] = $password;

            foreach ($user['userGroups'] as &$group) {
                $group = '/groups/'.$group['id'];
            }

            $this->commonGroundService->updateResource($user);

            $variables['reset'] = true;
        } elseif ($this->request->isMethod('POST')) {
            $variables['message'] = true;
            $username = $this->request->get('email');
            $users = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $username], true, false, true, false, false);
            $users = $users['hydra:member'];

            $application = $this->commonGroundService->getResource(['component'=>'wrc', 'type'=>'applications', 'id' => $this->params->get('app_id')]);
            $organization = $application['organization']['@id'];
            $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'token', 'application' => $this->params->get('app_id')])['hydra:member'];

            if (count($users) > 0) {
                $user = $users[0];
                $person = $this->commonGroundService->getResource($user['person']);

                $validChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $code = substr(str_shuffle(str_repeat($validChars, ceil(3 / strlen($validChars)))), 1, 5);

                $token = [];
                $token['token'] = $code;
                $token['user'] = 'users/'.$user['id'];
                $token['provider'] = 'providers/'.$providers[0]['id'];
                $token = $this->commonGroundService->createResource($token, ['component' => 'uc', 'type' => 'tokens']);

                $url = $this->request->getUri();
                $link = $url.'/'.$token['token'];

                $data = [];
                $data['resource'] = $link;
                $data['sender'] = 'no-reply@conduction.nl';

                $this->mailingService->sendMail('mails/password_reset.html.twig', 'no-reply@conduction.nl', $user['username'], 'Password reset', $data);
            }
        }

        return $variables;
    }

    /**
     * @Route("/error")
     * @Template
     */
    public function errorAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/users")
     * @Template
     */
    public function usersAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/organizations")
     * @Template
     */
    public function organizationsAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/developers")
     * @Template
     */
    public function developersAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/pricing")
     * @Template
     */
    public function pricingAction()
    {
        $variables = [];

        return $variables;
    }

}
