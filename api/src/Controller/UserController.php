<?php

// src/Controller/DefaultController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\ApplicationService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
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

    public function __construct(FlashBagInterface $flash)
    {
        $this->flash = $flash;
    }

    /**
     * @Route("/login")
     * @Route("/login/{loggedOut}", name="loggedOut")
     * @Template
     */
    public function login(
        Session $session,
        Request $request,
        AuthorizationCheckerInterface $authChecker,
        CommonGroundService $commonGroundService,
        ParameterBagInterface $params,
        EventDispatcherInterface $dispatcher,
        $loggedOut = false
    ) {
        $application = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $params->get('app_id')]);

        if ($loggedOut == 'loggedOut') {
            $text = 'U bent uitgelogd omdat de sessie is verlopen.';
            $this->flash->add('error', $text);

            $session->set('loggedOut', null);
        }
        if ($this->getUser()) {
            $this->flash->add('success', 'Welcome '.ucwords($this->getUser()->getName()));

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
     * @Route("/auth/digispoof")
     * @Template
     */
    public function DigispoofAction(Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        $redirect = $commonGroundService->cleanUrl(['component' => 'ds']);

        return $this->redirect($redirect.'?responceUrl='.$request->query->get('response').'&backUrl='.$request->query->get('back_url'));
    }

    /**
     * @Route("/auth/eherkenning")
     * @Template
     */
    public function EherkenningAction(Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        $redirect = $commonGroundService->cleanUrl(['component' => 'eh']);

        return $this->redirect($redirect.'?responceUrl='.$request->query->get('response').'&backUrl='.$request->query->get('back_url'));
    }

    /**
     * @Route("/auth/idin/login")
     * @Template
     */
    public function IdinLoginAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        $session->set('backUrl', $request->query->get('backUrl'));

        $redirect = str_replace('http:', 'https:', $request->getUri());
        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        $provider = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'idin', 'application' => $params->get('app_id')])['hydra:member'];
        $provider = $provider[0];

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret']) && isset($provider['configuration']['endpoint'])) {
            $clientId = $provider['configuration']['app_id'];

            if ($params->get('app_env') == 'prod') {
                return $this->redirect('https://eu01.signicat.com/oidc/authorize?response_type=code&scope=openid+signicat.idin&client_id='.$clientId.'&redirect_uri='.$redirect.'&acr_values=urn:signicat:oidc:method:idin-login&state=123');
            } else {
                return $this->redirect('https://eu01.preprod.signicat.com/oidc/authorize?response_type=code&scope=openid+signicat.idin&client_id='.$clientId.'&redirect_uri='.$redirect.'&acr_values=urn:signicat:oidc:method:idin-login&state=123');
            }
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/auth/idin/ident")
     * @Template
     */
    public function IdinAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        $session->set('backUrl', $request->query->get('backUrl'));

        $redirect = str_replace('http:', 'https:', $request->getUri());
        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        $provider = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'idin', 'application' => $params->get('app_id')])['hydra:member'];
        $provider = $provider[0];

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret']) && isset($provider['configuration']['endpoint'])) {
            $clientId = $provider['configuration']['app_id'];

            if ($params->get('app_env') == 'prod') {
                return $this->redirect('https://eu01.signicat.com/oidc/authorize?response_type=code&scope=openid+signicat.idin&client_id='.$clientId.'&redirect_uri='.$redirect.'&acr_values=urn:signicat:oidc:method:idin-ident&state=123');
            } else {
                return $this->redirect('https://eu01.preprod.signicat.com/oidc/authorize?response_type=code&scope=openid+signicat.idin&client_id='.$clientId.'&redirect_uri='.$redirect.'&acr_values=urn:signicat:oidc:method:idin-ident&state=123');
            }
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/auth/irma")
     * @Template
     */
    public function IrmaAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @Route("/auth/facebook")
     * @Template
     */
    public function FacebookAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        $session->set('backUrl', $request->query->get('backUrl'));

        $provider = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'facebook', 'application' => $params->get('app_id')])['hydra:member'];
        $provider = $provider[0];

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
    public function githubAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        $session->set('backUrl', $request->query->get('backUrl'));

        $providers = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'github', 'application' => $params->get('app_id')])['hydra:member'];
        $provider = $providers[0];

        $redirect = $request->getUri();

        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret'])) {
            return $this->redirect('https://github.com/login/oauth/authorize?state='.$params->get('app_id').'&redirect_uri='.$redirect.'&client_id='.$provider['configuration']['app_id']);
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/auth/gmail")
     * @Template
     */
    public function gmailAction(Session $session, Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        $session->set('backUrl', $request->query->get('backUrl'));

        $providers = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'gmail', 'application' => $params->get('app_id')])['hydra:member'];
        $provider = $providers[0];

        $redirect = $request->getUri();

        if (strpos($redirect, '?') == true) {
            $redirect = substr($redirect, 0, strpos($redirect, '?'));
        }

        if (isset($provider['configuration']['app_id']) && isset($provider['configuration']['secret'])) {
            return $this->redirect('https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id='.$provider['configuration']['app_id'].'&scope=openid%20email%20profile%20https://www.googleapis.com/auth/user.phonenumbers.read&redirect_uri='.$redirect);
        } else {
            return $this->render('500.html.twig');
        }
    }

    /**
     * @Route("/logout")
     * @Template
     */
    public function logoutAction(Session $session, Request $request)
    {
        $session->set('requestType', null);
        $session->set('request', null);
        $session->set('contact', null);
        $session->set('organisation', null);

        $text = $this->translator->trans('U bent uitgelogd');

        // Throw te actual flash
        $this->flash->add('error', $text);

        return $this->redirect($this->generateUrl('app_default_index'));
    }

    /**
     * @Route("/register")
     * @Template
     */
    public function registerAction(Session $session, Request $request, ApplicationService $applicationService, CommonGroundService $commonGroundService, ParameterBagInterface $params)
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

                $this->flash->add('success', 'Account created');

                return $this->redirect($backUrl);
            }
        }
    }

    /**
     * @Route("/userinfo")
     * @Template
     */
    public function userInfoAction(Session $session, Request $request, ApplicationService $applicationService, CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $variables = [];

        $variables['person'] = $commonGroundService->getResource($this->getUser()->getPerson());

        if ($request->isMethod('POST') && $request->get('info')) {
            $resource = $request->request->all();
            $person = [];
            $person['@id'] = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $variables['person']['id']]);
            $person['id'] = $variables['person']['id'];

            if (isset($resource['firstName'])) {
                $person['givenName'] = $resource['firstName'];
            }
            if (isset($resource['lastName'])) {
                $person['familyName'] = $resource['lastName'];
            }
            if (isset($resource['birthday']) && $resource['birthday'] !== '') {
                $person['birthday'] = $resource['birthday'];
            }
            if (isset($resource['email'])) {
                $person['emails'][0]['email'] = $resource['email'];
            }
            if (isset($resource['telephone'])) {
                $person['telephones'][0]['telephone'] = $resource['telephone'];
            }
            if (isset($resource['street'])) {
                $person['adresses'][0]['street'] = $resource['street'];
            }
            if (isset($resource['houseNumber'])) {
                $person['adresses'][0]['houseNumber'] = $resource['houseNumber'];
            }
            if (isset($resource['houseNumberSuffix'])) {
                $person['adresses'][0]['houseNumberSuffix'] = $resource['houseNumberSuffix'];
            }
            if (isset($resource['postalCode'])) {
                $person['adresses'][0]['postalCode'] = $resource['postalCode'];
            }
            if (isset($resource['locality'])) {
                $person['adresses'][0]['locality'] = $resource['locality'];
            }

            $variables['person'] = $commonGroundService->saveResource($person, ['component' => 'cc', 'type' => 'people']);
        } elseif ($request->isMethod('POST') && $request->get('password')) {
            $newPassword = $request->get('newPassword');
            $repeatPassword = $request->get('repeatPassword');

            if ($newPassword !== $repeatPassword) {
                $variables['error'] = true;

                return $variables;
            } else {
                $credentials = [
                    'username'   => $this->getUser()->getUsername(),
                    'password'   => $request->request->get('currentPassword'),
                    'csrf_token' => $request->request->get('_csrf_token'),
                ];

                $user = $commonGroundService->createResource($credentials, ['component'=>'uc', 'type'=>'login'], false, true, false, false);

                if (!$user) {
                    $variables['wrongPassword'] = true;

                    return $variables;
                }

                $users = $commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $this->getUser()->getUsername()], true, false, true, false, false)['hydra:member'];
                $user = $users[0];

                $user['password'] = $newPassword;

                $this->addFlash('success', 'wachtwoord aangepast');
                $commonGroundService->updateResource($user);

                $message = [];

                if ($params->get('app_env') == 'prod') {
                    $message['service'] = '/services/eb7ffa01-4803-44ce-91dc-d4e3da7917da';
                } else {
                    $message['service'] = '/services/1541d15b-7de3-4a1a-a437-80079e4a14e0';
                }
                $message['status'] = 'queued';
                $message['data'] = ['receiver' => $variables['person']['name']];
                $message['content'] = $commonGroundService->cleanUrl(['component'=>'wrc', 'type'=>'templates', 'id'=>'4125221c-74e0-46f9-97c9-3825a2011012']);
                $message['reciever'] = $user['username'];
                $message['sender'] = 'no-reply@conduction.nl';

                $commonGroundService->createResource($message, ['component'=>'bs', 'type'=>'messages']);
            }
        }

        return $variables;
    }
}
