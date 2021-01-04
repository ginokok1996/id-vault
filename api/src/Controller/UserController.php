<?php

// src/Controller/DefaultController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Security\User\CommongroundUser;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class UserController.
 *
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @var FlashBagInterface
     */
    private $flash;
    private $translator;
    private $session;
    private $request;
    private $commonGroundService;
    private $params;

    public function __construct(FlashBagInterface $flash, Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $this->flash = $flash;
        $this->session = $session;
        $this->request = $request;
        $this->commonGroundService = $commonGroundService;
        $this->params = $params;
    }

    /**
     * @Route("/login")
     * @Route("/login/{loggedOut}", name="loggedOut")
     * @Template
     */
    public function login() {
        $application = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $this->params->get('app_id')]);

        if ($this->getUser()) {
            $this->flash->add('success', 'Welcome '.ucwords($this->getUser()->getName()));

            return $this->redirect($this->generateUrl('app_dashboard_index'));
        }

        // Dealing with backUrls
        if ($backUrl = $this->request->query->get('backUrl')) {
        } else {
            $backUrl = '/dashboard';
        }
        $this->session->set('backUrl', $backUrl);

        return $this->redirect($this->generateUrl('app_default_index'));
    }

    /**
     * @Route("/auth/facebook")
     * @Template
     */
    public function FacebookAction()
    {
        $this->session->set('backUrl', $this->request->query->get('backUrl'));

        $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'facebook', 'application' => $this->params->get('app_id')])['hydra:member'];
        $provider = $provider[0];

        $redirect = $this->request->getUri();
        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret'])) {
            return $this->redirect('https://www.facebook.com/v8.0/dialog/oauth?client_id='.str_replace('"', '', $provider['configuration']['app_id']).'&scope=email&redirect_uri='.$redirect.'&state={st=state123abc,ds=123456789}');
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/auth/github")
     * @Template
     */
    public function githubAction()
    {
        $this->session->set('backUrl', $this->request->query->get('backUrl'));

        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'github', 'application' => $this->params->get('app_id')])['hydra:member'];
        $provider = $providers[0];

        $redirect = $this->request->getUri();

        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret'])) {
            return $this->redirect('https://github.com/login/oauth/authorize?state='.$this->params->get('app_id').'&redirect_uri='.$redirect.'&client_id='.$provider['configuration']['app_id']);
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/auth/gmail")
     * @Template
     */
    public function gmailAction()
    {
        $this->session->set('backUrl', $this->request->query->get('backUrl'));

        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'gmail', 'application' => $this->params->get('app_id')])['hydra:member'];
        $provider = $providers[0];

        $redirect = $this->request->getUri();

        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret'])) {
            return $this->redirect('https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id='.$provider['configuration']['app_id'].'&scope=openid%20email%20profile&redirect_uri='.$redirect);
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/auth/linkedin")
     * @Template
     */
    public function linkedinAction()
    {
        if ($this->request->query->get('error')) {
            $this->flash->add('warning', 'LinkedIn authorization has been cancelled');
            if ($this->session->get('backUrl')) {
                return $this->redirect($this->session->get('backUrl'));
            } else {
                return $this->redirect($this->generateUrl('app_default_index'));
            }
        }

        $this->session->set('backUrl', $this->request->query->get('backUrl'));

        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'linkedIn', 'application' => $this->params->get('app_id')])['hydra:member'];
        $provider = $providers[0];

        $redirect = $this->request->getUri();

        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret'])) {
            return $this->redirect("https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$provider['configuration']['app_id']}&redirect_uri={$redirect}&scope=r_emailaddress%20r_liteprofile");
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/logout")
     * @Template
     */
    public function logoutAction()
    {
        return $this->redirect($this->generateUrl('app_default_login'));
    }

    /**
     * @Route("/register")
     * @Template
     */
    public function registerAction(Request $request, CommonGroundService $commonGroundService)
    {
        if ($request->isMethod('POST')) {
            $backUrl = $request->query->get('backUrl');

            //lets check if there is already a user with this email
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $request->get('username')])['hydra:member'];
            if (count($users) > 0) {
                $this->flash->add('error', 'Email address is already registered with us');

                return $this->redirect($backUrl);
            } else {
                $user = [];
                $person = [];

                //create person
                $person['givenName'] = $request->get('firstName');
                $person['familyName'] = $request->get('lastName');
                $person['emails'][0]['email'] = $request->get('username');

                $person = $commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);
                $personUrl = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);

                //create user
                $user['username'] = $request->get('username');
                $user['password'] = $request->get('newPassword');
                $user['person'] = $personUrl;

                $user = $commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'users']);

                // given name claim
                $claimFirstName = [];
                $claimFirstName['person'] = $personUrl;
                $claimFirstName['property'] = 'schema.person.given_name';
                $claimFirstName['data']['given_name'] = $person['givenName'];

                $commonGroundService->saveResource($claimFirstName, ['component' => 'wac', 'type' => 'claims']);

                // family name claim
                $claimLastName = [];
                $claimLastName['person'] = $personUrl;
                $claimLastName['property'] = 'schema.person.family_name';
                $claimLastName['data']['family_name'] = $person['familyName'];

                $commonGroundService->saveResource($claimLastName, ['component' => 'wac', 'type' => 'claims']);

                // email claim
                $claimEmail = [];
                $claimEmail['person'] = $personUrl;
                $claimEmail['property'] = 'schema.person.email';
                $claimEmail['data']['email'] = $request->get('username');

                $commonGroundService->saveResource($claimEmail, ['component' => 'wac', 'type' => 'claims']);

                //calendar for the user
                $calendar = [];
                $calendar['name'] = 'calendar for '.$person['name'];
                $calendar['resource'] = $personUrl;
                $calendar['timeZone'] = 'CET';

                $commonGroundService->saveResource($calendar, ['component' => 'arc', 'type' => 'calendars']);

                $userObject = new CommongroundUser($user['username'], $request->get('newPassword'), $person['name'], null, $user['roles'], $user['person'], null, 'user');

                $token = new UsernamePasswordToken($userObject, null, 'main', $userObject->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                $this->container->get('session')->set('_security_main', serialize($token));

                $this->flash->add('success', 'Account created');

                return $this->redirect($backUrl);
            }
        }
    }
}
