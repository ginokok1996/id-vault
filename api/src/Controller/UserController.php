<?php

// src/Controller/DefaultController.php

namespace App\Controller;

use App\Service\DefaultService;
use Conduction\CommonGroundBundle\Security\User\CommongroundUser;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class UserController.
 *
 * @Route("/user")
 */
class UserController extends AbstractController
{
    private $commonGroundService;
    private $defaultService;

    public function __construct(CommonGroundService $commonGroundService, DefaultService $defaultService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->defaultService = $defaultService;
    }

    /**
     * @Route("/login")
     * @Route("/login/{loggedOut}", name="loggedOut")
     * @Template
     */
    public function login(Session $session, Request $request)
    {
        if ($this->getUser()) {
            $this->defaultService->throwFlash('success', 'Welcome '.ucwords($this->getUser()->getName()));

            return $this->redirect($this->generateUrl('app_dashboard_index'));
        }

        // Dealing with backUrls
        if ($backUrl = $request->query->get('backUrl')) {
        } else {
            $backUrl = '/dashboard';
        }
        $session->set('backUrl', $backUrl);

        return $this->redirect($this->generateUrl('app_default_index'));
    }

    /**
     * @Route("/auth/facebook")
     * @Template
     */
    public function FacebookAction(Session $session, Request $request)
    {
        if ($request->query->get('backUrl')) {
            $session->set('backUrl', $request->query->get('backUrl'));
        }        $provider = $this->defaultService->getProvider('facebook');
        $redirect = $request->getUri();

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
    public function githubAction(Session $session, Request $request)
    {
        if ($request->query->get('backUrl')) {
            $session->set('backUrl', $request->query->get('backUrl'));
        }        $provider = $this->defaultService->getProvider('github');
        $redirect = $request->getUri();

        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret'])) {
            return $this->redirect('https://github.com/login/oauth/authorize?redirect_uri='.$redirect.'&client_id='.$provider['configuration']['app_id']);
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/auth/gmail")
     * @Template
     */
    public function gmailAction(Session $session, Request $request)
    {
        if ($request->query->get('backUrl')) {
            $session->set('backUrl', $request->query->get('backUrl'));
        }        $provider = $this->defaultService->getProvider('gmail');
        $redirect = $request->getUri();

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
    public function linkedinAction(Session $session, Request $request)
    {
        if ($request->query->get('error')) {
            $this->defaultService->throwFlash('warning', 'LinkedIn authorization has been cancelled');
            if ($session->get('backUrl')) {
                return $this->redirect($session->get('backUrl'));
            } else {
                return $this->redirect($this->generateUrl('app_default_index'));
            }
        }

        if ($request->query->get('backUrl')) {
            $session->set('backUrl', $request->query->get('backUrl'));
        }

        $provider = $this->defaultService->getProvider('linkedIn');
        $redirect = $request->getUri();

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
    public function registerAction(Request $request, Session $session)
    {
        if ($request->isMethod('POST')) {
            if ($request->query->get('backUrl')) {
                $backUrl = $request->query->get('backUrl');
            } elseif ($session->get('backUrl')) {
                $backUrl = $session->get('backUrl');
            } else {
                $backUrl = $this->generateUrl('app_default_index');
            }

            //lets check if there is already a user with this email
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $request->get('username')])['hydra:member'];
            if (count($users) > 0) {
                $this->defaultService->throwFlash('error', 'Email address is already registered with us');

                return $this->redirect($backUrl);
            } else {
                $user = [];
                $person = [];

                //create person
                $person['givenName'] = $request->get('firstName');
                $person['familyName'] = $request->get('lastName');
                $person['emails'][0]['email'] = $request->get('username');

                $person = $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);
                $personUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);

                //create user
                $user['username'] = $request->get('username');
                $user['password'] = $request->get('newPassword');
                $user['person'] = $personUrl;

                $user = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'users']);

                // given name claim
                $claimFirstName = [];
                $claimFirstName['person'] = $personUrl;
                $claimFirstName['property'] = 'schema.person.given_name';
                $claimFirstName['data']['given_name'] = $person['givenName'];

                $this->commonGroundService->saveResource($claimFirstName, ['component' => 'wac', 'type' => 'claims']);

                // family name claim
                $claimLastName = [];
                $claimLastName['person'] = $personUrl;
                $claimLastName['property'] = 'schema.person.family_name';
                $claimLastName['data']['family_name'] = $person['familyName'];

                $this->commonGroundService->saveResource($claimLastName, ['component' => 'wac', 'type' => 'claims']);

                // email claim
                $claimEmail = [];
                $claimEmail['person'] = $personUrl;
                $claimEmail['property'] = 'schema.person.email';
                $claimEmail['data']['email'] = $request->get('username');

                $this->commonGroundService->saveResource($claimEmail, ['component' => 'wac', 'type' => 'claims']);

                //calendar for the user
                $calendar = [];
                $calendar['name'] = 'calendar for '.$person['name'];
                $calendar['resource'] = $personUrl;
                $calendar['timeZone'] = 'CET';

                $this->commonGroundService->saveResource($calendar, ['component' => 'arc', 'type' => 'calendars']);

                $userObject = new CommongroundUser($user['username'], $request->get('newPassword'), $person['name'], null, $user['roles'], $user['person'], null, 'user');

                $token = new UsernamePasswordToken($userObject, null, 'main', $userObject->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                $this->container->get('session')->set('_security_main', serialize($token));

                $this->defaultService->throwFlash('success', 'Account created');

                return $this->redirect($backUrl);
            }
        }
    }
}
