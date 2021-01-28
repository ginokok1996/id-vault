<?php

namespace App\Controller;

use App\Service\ClaimService;
use App\Service\DefaultService;
use App\Service\MailingService;
use Conduction\BalanceBundle\Service\BalanceService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Jose\Component\Core\Util\RSAKey;
use Jose\Component\KeyManagement\JWKFactory;
use Money\Money;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Description.
 *
 * Class DashboardController
 *
 * @Route("/dashboard")
 */
class DashboardController extends AbstractController
{
    private $defaultService;
    private $mailingService;
    private $session;
    private $commonGroundService;
    private $balanceService;

    public function __construct(DefaultService $defaultService, MailingService $mailingService, SessionInterface $session, CommonGroundService $commonGroundService, BalanceService $balanceService)
    {
        $this->defaultService = $defaultService;
        $this->mailingService = $mailingService;
        $this->session = $session;
        $this->commonGroundService = $commonGroundService;
        $this->balanceService = $balanceService;
    }

    public function provideCounterData($variables)
    {
        $userUrl = $this->defaultService->getUserUrl($this->getUser()->getUsername());

        //alerts
        $alerts = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'alerts'], ['link' => $userUrl])['hydra:member'];
        $variables['alertCount'] = (string) count($alerts);

        //tasks
        $tasks = $this->commonGroundService->getResourceList(['component' => 'arc', 'type' => 'todos'], ['calendar.resource' => $userUrl])['hydra:member'];
        $variables['taskCount'] = (string) count($tasks);

        return $variables;
    }

    /**
     * @Route("/")
     * @Template
     */
    public function indexAction()
    {
        $variables = [];
        $variables = $this->provideCounterData($variables);
        $userUrl = $this->defaultService->getUserUrl($this->getUser()->getUsername());
        $authorizations = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];
        $variables['authorizations'] = $this->defaultService->singleSignOn($authorizations);

        $application = $this->defaultService->getApplication();
        $organizations = [];
        $user = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
        foreach ($user['userGroups'] as $group) {
            $organization = $this->commonGroundService->getResource($group['organization']);
            if (!in_array($organization, $organizations) && $organization['id'] !== $application['organization']['id']) {
                $organizations[] = $organization;
            }
        }
        if (count($organizations) > 0) {
            foreach ($organizations as &$organization) {
                $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
                $account = $this->balanceService->getAcount($organizationUrl);
                if ($account !== false) {
                    $account['calculate'] = $account['balance'];
                    $account['balance'] = $this->balanceService->getBalance($organizationUrl);
                    $organization['account'] = $account;
                } else {
                    $this->balanceService->createAccount($organizationUrl, 1000);
                    $account = $this->balanceService->getAcount($organizationUrl);
                    $account['calculate'] = $account['balance'];
                    $account['balance'] = $this->balanceService->getBalance($organizationUrl);
                    $organization['account'] = $account;
                }
                $organization['margin'] = $this->defaultService->calculateMargin((int) $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'points_organization', 'id' => $organization['id']])['points'], $account['calculate']);
                $organization['cost'] = (int) $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'points_organization', 'id' => $organization['id']])['points'] / 100;
            }
            $variables['organizations'] = $organizations;
        }

        // getting graph info
        $date = new \DateTime('today');
        $variables['logs'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorization_logs'], ['authorization.userUrl' => $userUrl, 'order[dateCreated]' => 'desc', 'limit' => 200])['hydra:member'];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            $variables['days'][$day] = [];
        }

        if (count($variables['logs']) > 0) {
            foreach ($variables['logs'] as $log) {
                foreach ($days as $day) {
                    $date->modify(ucfirst($day).' this week');
                    if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                        $variables['days'][$day][] = $log;
                    }
                }
            }
        }

        return $variables;
    }

    /**
     * @Route("/alerts")
     * @Template
     */
    public function alertsAction()
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        $user = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
        $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

        $variables['alerts'] = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'alerts'], ['link' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];

        return $variables;
    }

    /**
     * @Route("/tasks")
     * @Template
     */
    public function tasksAction()
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);
        $userUrl = $this->defaultService->getUserUrl($this->getUser()->getUsername());
        $tasks = $this->commonGroundService->getResourceList(['component' => 'arc', 'type' => 'todos'], ['calendar.resource' => $userUrl])['hydra:member'];

        if (count($tasks) > 0) {
            $variables['tasks'] = $tasks;
        }

        return $variables;
    }

    /**
     * @Route("/claim-your-data/{type}")
     * @Template
     */
    public function claimYourDataAction(Request $request, ClaimService $claimService, $type = null)
    {
        $variables = [];

        //google
        $provider = $this->defaultService->getProvider('gmail');
        $variables['googleUrl'] = 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id='.$provider['configuration']['app_id'].'&scope=openid%20email%20profile&redirect_uri=http://id-vault.com/dashboard/claim-your-data/google';

        //facebook
        $provider = $this->defaultService->getProvider('facebook');
        $variables['facebookUrl'] = 'https://www.facebook.com/v8.0/dialog/oauth?client_id='.str_replace('"', '', $provider['configuration']['app_id']).'&scope=email&redirect_uri=https://id-vault.com/dashboard/claim-your-data/facebook&state={st=state123abc,ds=123456789}';

        //github
        $provider = $this->defaultService->getProvider('github claim');
        $variables['githubUrl'] = 'https://github.com/login/oauth/authorize?redirect_uri=https://id-vault.com/dashboard/claim-your-data/github&client_id='.$provider['configuration']['app_id'];

        //linkedIn
        $provider = $this->defaultService->getProvider('linkedIn');
        $variables['linkedInUrl'] = "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$provider['configuration']['app_id']}&redirect_uri=http://id-vault.com/dashboard/claim-your-data/linkedIn&scope=r_emailaddress%20r_liteprofile";

        if ($type == 'google' && $request->get('code')) {
            $completed = $claimService->googleClaim($request->get('code'), $this->getUser()->getPerson());
            if ($completed) {
                $variables['message'] = 'Google claim is aangemaakt';
            }
        } elseif ($type == 'facebook' && $request->get('code')) {
            $completed = $claimService->facebookClaim($request->get('code'), $this->getUser()->getPerson());
            if ($completed) {
                $variables['message'] = 'Facebook claim is aangemaakt';
            }
        } elseif ($type == 'github' && $request->get('code')) {
            $completed = $claimService->githubClaim($request->get('code'), $this->getUser()->getPerson());
            if ($completed) {
                $variables['message'] = 'Github claim is aangemaakt';
            }
        } elseif ($type == 'linkedIn' && $request->get('code')) {
            $completed = $claimService->linkedInClaim($request->get('code'), $this->getUser()->getPerson());
            if ($completed) {
                $variables['message'] = 'LinkedIn claim is aangemaakt';
            }
        }

        return $variables;
    }

    /**
     * @Route("/settings")
     * @Template
     */
    public function settingsAction(Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        if ($this->getUser()) {
            $variables['person'] = $this->commonGroundService->getResource($this->getUser()->getPerson());
            $variables['person'] = $this->commonGroundService->getResource(['component' => 'cc', 'type' => 'people', 'id' => $variables['person']['id']]);
        }

        if ($request->isMethod('POST') && $request->get('updateInfo')) {
            $name = $request->get('name');
            $email = $request->get('email');

            // Update (or create) the cc/person of this user
            $person = $variables['person'];
            $person['name'] = $name;
            $person['emails'][0] = [];
            $person['emails'][0]['name'] = 'email for '.$name;
            $person['emails'][0]['email'] = $email;
            $person['telephones'][0] = [];
            $person['telephones'][0]['name'] = 'telephone for '.$name;
            $person['telephones'][0]['telephone'] = $request->get('telephone');
            $address = [];
            $address['name'] = 'address for '.$name;
            $address['street'] = $request->get('street');
            $address['houseNumber'] = $request->get('houseNumber');
            $address['houseNumberSuffix'] = $request->get('houseNumberSuffix');
            $address['postalCode'] = $request->get('postalCode');
            $address['locality'] = $request->get('locality');
            $person['adresses'][0] = $address;

            $person = $this->commonGroundService->saveResource($person, ['component' => 'cc', 'type' => 'people']);

            // If this user has no person the user.person should be set to this $person?
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $user = $users[0];

                if (!isset($user['person'])) {
                    $user['person'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
                    $this->commonGroundService->updateResource($user);
                }
            }

            return $this->redirect($this->generateUrl('app_dashboard_settings'));
        } elseif ($request->isMethod('POST') && $request->get('twoFactorSwitchSubmit')) {
            // Add current user to userGroup developer.view if switch is on, else remove it instead.
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $user = $users[0];

                $userGroups = [];
                foreach ($user['userGroups'] as $userGroup) {
                    if ($userGroup['id'] != 'ff0a0468-3b92-4222-9bca-201df1ab0f42') {
                        array_push($userGroups, '/groups/'.$userGroup['id']);
                    }
                }

                $user['userGroups'] = $userGroups;
                if ($request->get('twoFactorSwitch')) {
                    $user['userGroups'][] = '/groups/ff0a0468-3b92-4222-9bca-201df1ab0f42';
                }
                $this->commonGroundService->updateResource($user);

                return $this->redirect($this->generateUrl('app_dashboard_settings'));
            }
        } elseif ($request->isMethod('POST') && $request->get('becomeDeveloper')) {
            // Add current user to userGroup developer
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $user = $users[0];

                $userGroups = [];
                foreach ($user['userGroups'] as $userGroup) {
                    if ($userGroup['id'] != 'c3c463b9-8d39-4cc0-b62c-826d8f5b7d8c') {
                        array_push($userGroups, '/groups/'.$userGroup['id']);
                    }
                }

                $user['userGroups'] = $userGroups;
                $user['userGroups'][] = '/groups/c3c463b9-8d39-4cc0-b62c-826d8f5b7d8c';
                $this->commonGroundService->updateResource($user);

                $data = [];
                $data['sender'] = 'no-reply@conduction.nl';

                $this->mailingService->sendMail('mails/developer.html.twig', 'no-reply@conduction.nl', $this->getUser()->getUsername(), 'Welcome developer', $data);

                return $this->redirect($this->generateUrl('app_dashboard_settings'));
            }
        }

        return $variables;
    }

    /**
     * @Route("/security")
     * @Template
     */
    public function securityAction()
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        return $variables;
    }

    /**
     * @Route("/notifications")
     * @Template
     */
    public function notificationsAction()
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        return $variables;
    }

    /**
     * @Route("/claims")
     * @Template
     */
    public function claimsAction(Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);
        $variables['claims'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], ['person' => $this->getUser()->getPerson(), 'order[dateCreated]' => 'desc'])['hydra:member'];

        // Set icon background colors and dossiers per claim
        foreach ($variables['claims'] as &$claim) {
            $claim['dossiers'] = [];

            // Set the organization background-color for the authorization icons shown with every claim
            if (isset($claim['authorizations'])) {
                foreach ($claim['authorizations'] as &$authorization) {
                    $application = $this->commonGroundService->isResource($authorization['application']['contact']);
                    if (isset($application['organization']['style']['css'])) {
                        preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                        $authorization['iconBackgroundColor'] = $matches;
                    }

                    // Put all dossiers connected to this claim in claim.dossiers
                    if (isset($authorization['dossiers'])) {
                        foreach ($authorization['dossiers'] as $dossier) {
                            array_push($claim['dossiers'], $dossier);
                        }
                    }
                }
            }

            // Set the organization background-color for the proof icons shown with every claim
            if (isset($claim['proofs'])) {
                foreach ($claim['proofs'] as &$proof) {
                    $application = $this->commonGroundService->isResource($proof['application']['contact']);
                    if (isset($application['organization']['style']['css'])) {
                        preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                        $proof['iconBackgroundColor'] = $matches;
                    }
                }
            }
        }
        if ($request->isMethod('POST')) {
            if ($_FILES['file']['type'] !== 'application/json') {
                $this->defaultService->throwFlash('error', 'File is not in JSON format');

                return $variables;
            }
            $json = json_decode(file_get_contents($_FILES['file']['tmp_name']), true);
            if (isset($json['@context']) && $json['@context'][0] = 'https://www.w3.org/2018/credentials/v1') {
                $claim = [];
                $claim['person'] = $this->getUser()->getPerson();
                $claim['property'] = $json['type'][1];
                $claim['data'] = $json;
                $claim['token'] = $json['proof']['jws'];

                $this->commonGroundService->createResource($claim, ['component' => 'wac', 'type' => 'claims']);

                return $this->redirect($this->generateUrl('app_dashboard_claims'));
            } else {
                $this->defaultService->throwFlash('error', 'Claim is not in w3c format');
            }
        }

        return $variables;
    }

    /**
     * @Route("/claims/{id}")
     * @Template
     */
    public function claimAction($id)
    {
        $variables = [];
        $variables = $this->provideCounterData($variables);
        $variables['resource'] = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'claims', 'id' => $id]);

        if (isset($variables['resource']['authorizations'])) {
            foreach ($variables['resource']['authorizations'] as &$authorization) {
                $application = $this->commonGroundService->isResource($authorization['application']['contact']);
                if (isset($application['organization']['style']['css'])) {
                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                    $authorization['backgroundColor'] = $matches;
                }
            }
        }

        return $variables;
    }

    /**
     * @Route("/authorizations")
     * @Template
     */
    public function authorizationsAction(Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        if ($this->getUser()) {
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
            $variables['authorizations'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];

            // Set more variables to show on the authorizations page
            foreach ($variables['authorizations'] as &$authorization) {

                // Set endDate of every authorization by adding the authorization.purposeLimitation.expiryPeriod to the authorization.startingDate
                if (key_exists('purposeLimitation', $authorization) and !empty($authorization['purposeLimitation']) and
                    key_exists('expiryPeriod', $authorization['purposeLimitation']) and !empty($authorization['purposeLimitation']['expiryPeriod'])) {
                    if (key_exists('startingDate', $authorization) and !empty($authorization['startingDate'])) {
                        $date = new \DateTime($authorization['startingDate']);
                        $date->add(new \DateInterval($authorization['purposeLimitation']['expiryPeriod']));
                        $authorization['endDate'] = $date;
                    } else {
                        $date = new \DateTime($authorization['dateCreated']);
                        $date->add(new \DateInterval($authorization['purposeLimitation']['expiryPeriod']));
                        $authorization['endDate'] = $date;
                    }
                }

                // Set the organization background-color for the icons shown with every authorization
                if (isset($authorization['application']['contact'])) {
                    $application = $this->commonGroundService->isResource($authorization['application']['contact']);
                    if ($application) {
                        if (isset($application['organization']['style']['css'])) {
                            preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                            $authorization['backgroundColor'] = $matches;
                        }
                    }
                }
            }
        }

        // Delete authorization if there is no dossier connected to it and redirect
        if ($request->isMethod('POST') && ($request->get('endAuthorization') || $request->get('endClaimAuthorization'))) {
            $authorization = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $request->get('authorizationID')]);
            // Delete authorization
            $this->commonGroundService->deleteResource($authorization);

            // Redirect correctly
            if ($request->get('endClaimAuthorization')) {
                return $this->redirect($this->generateUrl('app_dashboard_claims'));
            } else {
                return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
            }
        }

        return $variables;
    }

    /**
     * @Route("/authorizations/{id}")
     * @Template
     */
    public function authorizationAction($id)
    {
        if (empty($this->getUser())) {
            $this->defaultService->throwFlash('error', 'This page requires you to be logged in');

            return $this->redirect($this->generateUrl('app_default_login'));
        }
        if (!$id) {
            $this->defaultService->throwFlash('error', 'No id provided');

            return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
        }

        $variables = [];

        $variables = $this->provideCounterData($variables);

        $variables['resource'] = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $id]);

        // Set this resources as authorization for each authorizationLog and set icon background-color
        foreach ($variables['resource']['authorizationLogs'] as &$log) {
            // Set this resources as authorization for this Log
            $log['authorization'] = $variables['resource'];

            // Set the organization background-color for the icon shown with this log
            if (key_exists('contact', $log['authorization']['application']) && !empty($log['authorization']['application']['contact'])) {
                $application = $this->commonGroundService->isResource($log['authorization']['application']['contact']);
                if ($application) {
                    if (isset($application['organization']['style']['css'])) {
                        preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                        $log['backgroundColor'] = $matches;
                    }
                }
            }
        }

        // Set more variables to show on the authorizations page
        // Set endDate of this authorization by adding the authorization.purposeLimitation.expiryPeriod to the authorization.startingDate
        if (key_exists('purposeLimitation', $variables['resource']) and !empty($variables['resource']['purposeLimitation']) and
            key_exists('expiryPeriod', $variables['resource']['purposeLimitation']) and !empty($variables['resource']['purposeLimitation']['expiryPeriod'])) {
            if (key_exists('startingDate', $variables['resource']) and !empty($variables['resource']['startingDate'])) {
                $date = new \DateTime($variables['resource']['startingDate']);
                $date->add(new \DateInterval($variables['resource']['purposeLimitation']['expiryPeriod']));
                $variables['resource']['endDate'] = $date;
            } else {
                $date = new \DateTime($variables['resource']['dateCreated']);
                $date->add(new \DateInterval($variables['resource']['purposeLimitation']['expiryPeriod']));
                $variables['resource']['endDate'] = $date;
            }
        }

        // Set the organization background-color for the icon shown with this authorization
        if (key_exists('contact', $variables['resource']['application']) and !empty($variables['resource']['application']['contact'])) {
            $application = $this->commonGroundService->isResource($variables['resource']['application']['contact']);
            if ($application) {
                if (isset($application['organization']['style']['css'])) {
                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                    $variables['resource']['backgroundColor'] = $matches;
                }
            }
        }

        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
        $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
        if ($variables['resource']['userUrl'] != $userUrl) {
            $this->defaultService->throwFlash('error', 'You do not have access to this authorization');

            return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
        }

        return $variables;
    }

    /**
     * @Route("/dossiers")
     * @Template
     */
    public function dossiersAction(Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        if ($this->getUser()) {
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
            $variables['dossiers'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'dossiers'], ['authorization.userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        // Delete dossier and redirect
        if ($request->isMethod('POST') && ($request->get('deleteDossier') || $request->get('deleteAuthorizationDossier') || $request->get('deleteClaimAuthorizationDossier'))) {
            $dossier = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id' => $request->get('dossierID')]);
            // Delete dossier
            $this->commonGroundService->deleteResource($dossier);

            // Redirect correctly
            if ($request->get('deleteClaimAuthorizationDossier')) {
                return $this->redirect($this->generateUrl('app_dashboard_claims'));
            } elseif ($request->get('deleteAuthorizationDossier')) {
                return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
            } else {
                return $this->redirect($this->generateUrl('app_dashboard_dossiers'));
            }
        } elseif ($request->isMethod('POST') && ($request->get('dossierObjection'))) {
            $dossier = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id' => $request->get('dossierID')]);
            $this->defaultService->throwFlash('error', 'No objection submitted for: '.$dossier['name']);

            return $this->redirect($this->generateUrl('app_dashboard_dossiers'));
        }

        return $variables;
    }

    /**
     * @Route("/dossiers/{id}")
     * @Template
     */
    public function dossierAction($id)
    {
        $variables = [];
        $variables = $this->provideCounterData($variables);
        $variables['resource'] = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id' => $id]);
        $application = $this->commonGroundService->isResource($variables['resource']['authorization']['application']['contact']);

        if (isset($application['organization']['style']['css'])) {
            preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
            $variables['resource']['authorization']['backgroundColor'] = $matches;
        }

        return $variables;
    }

    /**
     * @Route("/applications")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function applicationsAction(Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        $applications = [];

        if ($this->getUser()) {
            $application = $this->defaultService->getApplication();
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $organizations = [];
                $user = $users[0];
                foreach ($user['userGroups'] as $group) {
                    $organization = $this->commonGroundService->getResource($group['organization']);
                    if (!in_array($organization, $organizations) && $organization['id'] !== $application['organization']['id']) {
                        $organizations[] = $organization;
                    }
                }

                foreach ($organizations as $organization) {
                    $cleanUrl = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
                    $newApplications = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['organization' => $cleanUrl])['hydra:member'];
                    if (count($newApplications) > 0) {
                        foreach ($newApplications as $newApplication) {
                            $applications[] = $newApplication;
                        }
                    }
                }

                $variables['organizations'] = $organizations;
                $variables['applications'] = $applications;

                // Set the application/organization background-color for the icons shown with every application
                foreach ($variables['applications'] as &$application) {
                    if (isset($application['contact'])) {
                        $applicationContact = $this->commonGroundService->getResource($application['contact']);
                    }
                    if (isset($applicationContact['style']['css'])) {
                        preg_match('/background-color: ([#A-Za-z0-9]+)/', $applicationContact['style']['css'], $matches);
                        $application['backgroundColor'] = $matches;
                    } elseif (isset($applicationContact['organization']['style']['css'])) {
                        preg_match('/background-color: ([#A-Za-z0-9]+)/', $applicationContact['organization']['style']['css'], $matches);
                        $application['backgroundColor'] = $matches;
                    }
                }
            }
        }

        if ($request->isMethod('POST') && $request->get('newApplication')) {
            $name = $request->get('name');
            $application['name'] = $name;
            $application['description'] = $request->get('description');

            // Create a wRc application
            $wrcApplication['name'] = $name;
            $wrcApplication['description'] = $request->get('description');
            $wrcApplication['organization'] = '/organizations/'.$request->get('organization');
            $wrcApplication['domain'] = $request->get('domain');

            $wrcApplication = $this->commonGroundService->createResource($wrcApplication, ['component' => 'wrc', 'type' => 'applications']);

            // Create a wAc application
            $user = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
            $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

            $application['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $request->get('organization')]);
            $application['authorizationUrl'] = $request->get('passthroughUrl');
            $application['contact'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'applications', 'id' => $wrcApplication['id']]);
            $application['gdprContact'] = $userUrl;
            $application['technicalContact'] = $userUrl;
            $application['privacyContact'] = $userUrl;
            $application['billingContact'] = $userUrl;
            $application['configuration']['fontColor'] = $request->get('fontColor');
            $application['configuration']['backgroundColor'] = $request->get('backgroundColor');
            if (isset($_FILES['applicationLogo']) && $_FILES['applicationLogo']['error'] !== 4) {
                $path = $_FILES['applicationLogo']['tmp_name'];
                $type = filetype($_FILES['applicationLogo']['tmp_name']);
                $data = file_get_contents($path);
                $application['configuration']['logo'] = 'data:image/'.$type.';base64,'.base64_encode($data);
            }
            $this->commonGroundService->createResource($application, ['component' => 'wac', 'type' => 'applications']);

            return $this->redirect($this->generateUrl('app_dashboard_applications'));
        }

        return $variables;
    }

    /**
     * @Route("/applications/{id}")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function applicationAction($id, Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        if ($request->isMethod('POST') && $request->get('updateInfo')) {
            $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            $wrcApplication = $this->commonGroundService->getResource($application['contact']);

            if (isset($application['userGroups'])) {
                foreach ($application['userGroups'] as &$userGroup) {
                    $userGroup = '/groups/'.$userGroup['id'];
                }
            }
            //application
            $application['name'] = $request->get('name');
            $application['description'] = $request->get('description');
            $application['authorizationUrl'] = $request->get('authorizationUrl');
            $application['webhookUrl'] = $request->get('webhookUrl');
            $application['singleSignOnUrl'] = $request->get('singleSignOnUrl');
            $application['mailgunApiKey'] = $request->get('mailgunApiKey');
            $application['mailgunDomain'] = $request->get('mailgunDomain');
            $application['messageBirdApiKey'] = $request->get('messageBirdApiKey');

            $application['gdprContact'] = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $request->get('gdprContact')]);
            $application['technicalContact'] = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $request->get('technicalContact')]);
            $application['privacyContact'] = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $request->get('privacyContact')]);
            $application['billingContact'] = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $request->get('billingContact')]);

            $application = $this->commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);

            //wrc application
            $wrcApplication['name'] = $request->get('name');
            $wrcApplication['domain'] = $request->get('domain');
            $wrcApplication['organization'] = '/organizations/'.$wrcApplication['organization']['id'];

            if (isset($wrcApplication['style'])) {
                $wrcApplication['style'] = '/styles/'.$wrcApplication['style']['id'];
            }

            $wrcApplication = $this->commonGroundService->saveResource($wrcApplication, ['component' => 'wrc', 'type' => 'applications']);
        } elseif ($request->isMethod('POST') && $request->get('updateStyle')) {
            $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            if (isset($application['userGroups'])) {
                foreach ($application['userGroups'] as &$userGroup) {
                    $userGroup = '/groups/'.$userGroup['id'];
                }
            }
            $application['configuration']['fontColor'] = $request->get('fontColor');
            $application['configuration']['backgroundColor'] = $request->get('backgroundColor');
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== 4) {
                $path = $_FILES['logo']['tmp_name'];
                $type = filetype($_FILES['logo']['tmp_name']);
                $data = file_get_contents($path);
                $application['configuration']['logo'] = 'data:image/'.$type.';base64,'.base64_encode($data);
            }
            $this->commonGroundService->updateResource($application);
        } elseif ($request->isMethod('POST') && $request->get('updateScopes')) {
            $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            $application['scopes'] = $request->get('scopes');

            $application = $this->commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);
        } elseif ($request->isMethod('POST') && $request->get('addGroup')) {
            $resource = $request->request->all();
            $resource['application'] = '/applications/'.$id;

            $group = $this->commonGroundService->createResource($resource, ['component' => 'wac', 'type' => 'groups']);
        } elseif ($request->isMethod('POST') && ($request->get('addMailingList') || $request->get('editMailingList'))) {
            $resource = $request->request->all();

            // Save mailing list
            $resource['email'] = true;
            $resource = $this->commonGroundService->saveResource($resource, (['component' => 'bs', 'type' => 'send_lists']));

            // add mailing list to wac application
            $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            $sendLists = [];
            if (isset($application['sendLists'])) {
                foreach ($application['sendLists'] as $sendList) {
                    if ($sendList != $resource['id']) {
                        array_push($sendLists, $sendList);
                    }
                }
            }
            array_push($sendLists, $resource['id']);
            $application['sendLists'] = $sendLists;
            $this->commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);

            return $this->redirect($this->generateUrl('app_dashboard_application', ['id' => $id]).'#'.$resource['id']);
        } // Delete mailing list
        elseif ($request->isMethod('POST') && $request->get('deleteMailingList')) {
            $sendList = $this->commonGroundService->getResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $request->get('mailingListID')]);

            // Remove mailing list from wac application
            $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            $sendLists = [];
            if (isset($application['sendLists'])) {
                foreach ($application['sendLists'] as $sendListItem) {
                    if ($sendListItem != $sendList['id']) {
                        array_push($sendLists, $sendListItem);
                    }
                }
            }
            $application['sendLists'] = $sendLists;
            $this->commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);

            // Delete mailing list
            $this->commonGroundService->deleteResource($sendList);

            return $this->redirect($this->generateUrl('app_dashboard_application', ['id' => $id]).'#mailingLists');
        } elseif ($request->isMethod('POST') && $request->get('privateKey')) {
            $application = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            if (isset($application['userGroups'])) {
                foreach ($application['userGroups'] as &$group) {
                    $group = '/groups/'.$group['id'];
                }
            }
            if (isset($application['proofs'])) {
                foreach ($application['proofs'] as &$proof) {
                    $proof = '/proofs/'.$proof['id'];
                }
            }

            $jwk = JWKFactory::createRSAKey(
                4096, // Size in bits of the key. We recommend at least 2048 bits.
                [
                    'alg' => 'RS512',
                    'use' => 'alg',
                ]
            );

            $application['publicKey'] = RSAKey::createFromJWK($jwk->toPublic())->toPEM();
            $application['privateKey'] = RSAKey::createFromJWK($jwk)->toPEM();

            $this->commonGroundService->updateResource($application);
        } elseif ($request->isMethod('POST') && $request->get('downloadPrivateKey')) {
            $filename = 'privateKey.pem';

            $key = $request->get('privateKeyValue');

            $response = new Response($key);
            // Create the disposition of the file
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            );

            // Set the content disposition
            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        }

        $variables['application'] = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
        $variables['wrcApplication'] = $this->commonGroundService->getResource($variables['application']['contact']);

        if (isset($variables['application']['sendLists'])) {
            $sendLists = [];
            foreach ($variables['application']['sendLists'] as $sendListId) {
                if ($this->commonGroundService->isResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendListId])) {
                    array_push($sendLists, $this->commonGroundService->getResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendListId]));
                }
            }
            if (count($sendLists) > 0) {
                $variables['sendLists'] = $sendLists;
            }
        }

        $variables['wrcOrganization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $variables['wrcApplication']['organization']['id']]);
        $groups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $variables['wrcOrganization']])['hydra:member'];
        if (count($groups) > 0) {
            $group = $groups[0];
            $variables['users'] = $group['users'];
        }

        return $variables;
    }

    /**
     * @Route("/conduction")
     * @Template
     */
    public function conductionAction(Request $request)
    {
        $variables = [];
        $variables = $this->provideCounterData($variables);
        $query = [];
        $date = new \DateTime('today');

        if ($request->isMethod('POST')) {
            switch ($request->get('type')) {
                case 'week':
                    $date->modify('Monday this week');
                    break;
                case 'month':
                    $date->modify('first day of this month');
                    break;
                case 'quarter':
                    $offset = (date('n') % 3) - 1;
                    $date->modify("first day of -$offset month midnight");
                    break;
                case 'year':
                    $date->modify('first day of january');
                    break;
            }
            $query['dateCreated[after]'] = $date->format('Y-m-d');
        }

        $variables['users'] = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], $query)['hydra:member'];
        $variables['organizations'] = $this->commonGroundService->getResourceList(['component' => 'wrc', 'type' => 'organizations'], $query)['hydra:member'];
        $variables['applications'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], $query)['hydra:member'];
        $variables['claims'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], $query)['hydra:member'];

        return $variables;
    }

    /**
     * @Route("/logs")
     * @Template
     */
    public function logsAction()
    {
        $variables = [];
        $variables = $this->provideCounterData($variables);
        $userUrl = $this->defaultService->getUserUrl($this->getUser()->getUsername());
        $variables['logs'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorization_logs'], ['authorization.userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];

        foreach ($variables['logs'] as &$log) {
            $application = $this->commonGroundService->isResource($log['authorization']['application']['contact']);
            if (isset($application['organization']['style']['css'])) {
                preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                $log['backgroundColor'] = $matches;
            }
        }

        return $variables;
    }

    /**
     * @Route("/logs/{id}")
     * @Template
     */
    public function logAction($id)
    {
        $variables = [];
        $variables = $this->provideCounterData($variables);
        $variables['resource'] = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'authorization_logs', 'id' => $id], ['order[dateCreated]' => 'desc']);

        $application = $this->commonGroundService->isResource($variables['resource']['authorization']['application']['contact']);
        if (isset($application['organization']['style']['css'])) {
            preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
            $variables['resource']['backgroundColor'] = $matches;
        }

        return $variables;
    }

    /**
     * @Route("/organizations")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function organizationsAction(Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        if ($request->isMethod('POST')) {
            $name = $request->get('name');
            $email = $request->get('email');
            $description = $request->get('description');

            $cc = [];
            $cc['name'] = $name;
            $cc['description'] = $description;
            $cc['emails'][0]['name'] = 'email for '.$name;
            $cc['emails'][0]['email'] = $email;
            $cc['adresses'][0]['name'] = 'address for '.$name;

            $cc = $this->commonGroundService->createResource($cc, ['component' => 'cc', 'type' => 'organizations']);

            $wrc = [];
            $wrc['rsin'] = ' ';
            $wrc['chamberOfComerce'] = ' ';
            $wrc['name'] = $name;
            $wrc['description'] = $description;
            $wrc['contact'] = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $cc['id']]);
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== 4) {
                $path = $_FILES['logo']['tmp_name'];
                $type = filetype($_FILES['logo']['tmp_name']);
                $data = file_get_contents($path);
                $wrc['style']['name'] = 'style for '.$name;
                $wrc['style']['description'] = 'style for '.$name;
                $wrc['style']['css'] = ' ';
                $wrc['style']['favicon']['name'] = 'logo for '.$name;
                $wrc['style']['favicon']['description'] = 'logo for '.$name;
                $wrc['style']['favicon']['base64'] = 'data:image/'.$type.';base64,'.base64_encode($data);
            }

            $wrc = $this->commonGroundService->createResource($wrc, ['component' => 'wrc', 'type' => 'organizations']);

            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $wrc['id']]);

            $validChars = '0123456789';
            $reference = substr(str_shuffle(str_repeat($validChars, ceil(3 / strlen($validChars)))), 1, 10);

            $account = [];
            $account['resource'] = $organizationUrl;
            $account['reference'] = $reference;
            $account['name'] = $wrc['name'];

            $account = $this->commonGroundService->createResource($account, ['component' => 'bare', 'type' => 'acounts']);

            $this->balanceService->addCredit(Money::EUR(1000), $organizationUrl, $wrc['name']);

            $userGroup = [];
            $userGroup['name'] = 'developers-'.$name;
            $userGroup['title'] = 'developers-'.$name;
            $userGroup['description'] = 'developers group for '.$name;
            $userGroup['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $wrc['id']]);

            $group = $this->commonGroundService->createResource($userGroup, ['component' => 'uc', 'type' => 'groups']);

            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $organizations = [];
                $user = $users[0];

                $userGroups = [];
                foreach ($user['userGroups'] as $userGroup) {
                    array_push($userGroups, '/groups/'.$userGroup['id']);
                }

                $user['userGroups'] = $userGroups;
                $user['userGroups'][] = '/groups/'.$group['id'];

                $this->commonGroundService->updateResource($user);
            }
        }

        if ($this->getUser()) {
            $application = $this->defaultService->getApplication();
            $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $organizations = [];
                $user = $users[0];
                foreach ($user['userGroups'] as $group) {
                    $organization = $this->commonGroundService->getResource($group['organization']);
                    if (!in_array($organization, $organizations) && $organization['id'] !== $application['organization']['id']) {
                        $organizations[] = $organization;
                    }
                }
                $variables['resources'] = $organizations;

                // Set the organization background-color for the icons shown with every organization
                foreach ($variables['resources'] as &$organization) {
                    if (isset($organization['style']['css'])) {
                        preg_match('/background-color: ([#A-Za-z0-9]+)/', $organization['style']['css'], $matches);
                        $organization['backgroundColor'] = $matches;
                    }
                }
            }
        }

        return $variables;
    }

    /**
     * @Route("/organizations/{id}")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function organizationAction($id, Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        $variables['organization'] = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);

        $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
        $account = $this->balanceService->getAcount($organizationUrl);
        if ($account !== false) {
            $account['balance'] = $this->balanceService->getBalance($organizationUrl);
            $variables['account'] = $account;
            $variables['payments'] = $this->commonGroundService->getResourceList(['component' => 'bare', 'type' => 'payments'], ['acount.id' => $account['id'], 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        if (key_exists('contact', $variables['organization']) and !empty($variables['organization']['contact'])) {
            $variables['cc'] = $this->commonGroundService->getResource($variables['organization']['contact']);
        }
        $organization = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
        $variables['applications'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['organization' => $organization])['hydra:member'];

        // Set the application/organization background-color for the icons shown with every application
        foreach ($variables['applications'] as &$application) {
            if (isset($application['style']['css'])) {
                preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['style']['css'], $matches);
                $application['backgroundColor'] = $matches;
            } elseif (isset($variables['organization']['style']['css'])) {
                preg_match('/background-color: ([#A-Za-z0-9]+)/', $variables['organization']['style']['css'], $matches);
                $application['backgroundColor'] = $matches;
            }
        }

        $groups = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $organization])['hydra:member'];
        if (count($groups) > 0) {
            $group = $groups[0];
            $variables['users'] = $group['users'];
        }

        if ($request->isMethod('POST') && $request->get('newDeveloper')) {
        } elseif ($request->isMethod('POST') && $request->get('newApplication')) {
            $name = $request->get('name');
            $application['name'] = $name;
            $application['description'] = $request->get('description');

            // Create a wRc application
            $wrcApplication['name'] = $name;
            $wrcApplication['description'] = $request->get('description');
            $wrcApplication['organization'] = '/organizations/'.$id;
            $wrcApplication['domain'] = $request->get('domain');

            $wrcApplication = $this->commonGroundService->createResource($wrcApplication, ['component' => 'wrc', 'type' => 'applications']);

            // Create a wAc application
            $user = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
            $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

            $application['organization'] = $organization;
            $application['authorizationUrl'] = $request->get('passthroughUrl');
            $application['contact'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'applications', 'id' => $wrcApplication['id']]);
            $application['gdprContact'] = $userUrl;
            $application['technicalContact'] = $userUrl;
            $application['privacyContact'] = $userUrl;
            $application['billingContact'] = $userUrl;

            $application['configuration']['fontColor'] = $request->get('fontColor');
            $application['configuration']['backgroundColor'] = $request->get('backgroundColor');
            if (isset($_FILES['applicationLogo']) && $_FILES['applicationLogo']['error'] !== 4) {
                $path = $_FILES['applicationLogo']['tmp_name'];
                $type = filetype($_FILES['applicationLogo']['tmp_name']);
                $data = file_get_contents($path);
                $application['configuration']['logo'] = 'data:image/'.$type.';base64,'.base64_encode($data);
            }

            $this->commonGroundService->createResource($application, ['component' => 'wac', 'type' => 'applications']);

            return $this->redirect($this->generateUrl('app_dashboard_organization', ['id' => $id]));
        } elseif ($request->isMethod('POST') && $request->get('updateInfo')) {
            $name = $request->get('name');
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== 4) {
                if (key_exists('style', $variables['organization']) and !empty($variables['organization']['style'])) {
                    if (key_exists('favicon', $variables['organization']['style']) and !empty($variables['organization']['style']['favicon'])) {
                        $icon = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'images', 'id' => $variables['organization']['style']['favicon']['id']]);
                    } else {
                        // create icon for the style ?
                    }
                } else {
                    // create style and icon ?
                }
                $path = $_FILES['logo']['tmp_name'];
                $type = filetype($_FILES['logo']['tmp_name']);
                $data = file_get_contents($path);
                $icon['name'] = 'logo for '.$name;
                $icon['description'] = 'logo for '.$name;
                $icon['base64'] = 'data:image/'.$type.';base64,'.base64_encode($data);
                $this->commonGroundService->saveResource($icon);
            }

            $organization = $variables['organization'];
            $organization['name'] = $name;
            $organization['description'] = $request->get('description');
            if (key_exists('style', $organization) and !empty($organization['style'])) {
                $organization['style'] = '/styles/'.$organization['style']['id'];
            }
            $this->commonGroundService->updateResource($organization);

            if (key_exists('cc', $variables)) {
                $cc = $variables['cc'];
                $cc['name'] = $name;
                $cc['emails'][0] = [];
                $cc['emails'][0]['name'] = 'email for '.$name;
                $cc['emails'][0]['email'] = $request->get('email');
                $address = [];
                $address['name'] = 'address for '.$name;
                $address['street'] = $request->get('street');
                $address['houseNumber'] = $request->get('houseNumber');
                $address['houseNumberSuffix'] = $request->get('houseNumberSuffix');
                $address['postalCode'] = $request->get('postalCode');
                $address['locality'] = $request->get('locality');
                $cc['adresses'][0] = $address;
                $this->commonGroundService->updateResource($cc);

                $variables['organization'] = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
                $variables['cc'] = $this->commonGroundService->getResource($variables['organization']['contact']);
            }
        }

        return $variables;
    }

    /**
     * @Route("/transactions/{organization}")
     * @Template
     */
    public function TransactionsAction(Request $request, $organization)
    {
        // On an index route we might want to filter based on user input
        $variables = [];
        $variables = $this->provideCounterData($variables);
        $organization = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization]);
        $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
        $variables['organization'] = $organization;

        if ($this->session->get('mollieCode')) {
            $mollieCode = $this->session->get('mollieCode');
            $this->session->remove('mollieCode');
            $result = $this->balanceService->processMolliePayment($mollieCode, $organizationUrl);

            if ($result['status'] == 'paid') {
                $variables['message'] = 'Payment processed successfully! <br> €'.$result['amount'].'.00 was added to your balance. <br>  Invoice with reference: '.$result['reference'].' is created.';
            } else {
                $variables['message'] = 'Something went wrong, the status of the payment is: '.$result['status'].' please try again.';
            }
        }

        $variables['account'] = $this->balanceService->getAcount($organizationUrl);

        if ($variables['account'] !== false) {
            $variables['account']['balance'] = $this->balanceService->getBalance($organizationUrl);
            $variables['payments'] = $this->commonGroundService->getResourceList(['component' => 'bare', 'type' => 'payments'], ['acount.id' => $variables['account']['id'], 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        if ($request->isMethod('POST')) {
            $amount = $request->get('amount') * 1.21;
            $amount = (number_format($amount, 2));

            $payment = $this->balanceService->createMolliePayment($amount, $request->get('redirectUrl'));
            $this->session->set('mollieCode', $payment['id']);

            return $this->redirect($payment['redirectUrl']);
        }

        return $variables;
    }

    /**
     * @Route("/invoices")
     * @Template
     */
    public function InvoicesAction()
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        if (!empty($this->getUser()->getOrganization())) {
            $organization = $this->commonGroundService->getResource($this->getUser()->getOrganization());
            $organizationUrl = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
            $variables['invoices'] = $this->commonGroundService->getResourceList(['component' => 'bc', 'type' => 'invoices'], ['customer' => $organizationUrl])['hydra:member'];
        }

        return $variables;
    }

    /**
     * @Route("/invoice/{id}")
     * @Template
     */
    public function InvoiceAction($id)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        $variables['invoice'] = $this->commonGroundService->getResource(['component' => 'bc', 'type' => 'invoices', 'id' => $id]);

        return $variables;
    }

    /**
     * @Route("/groups")
     * @Template
     */
    public function groupsAction(Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        $userUrl = $this->defaultService->getUserUrl($this->getUser()->getUsername());

        $variables['groups'] = $this->commonGroundService->getResourceList(['component' => 'wac', 'type' => 'memberships'], ['userUrl' => $userUrl])['hydra:member'];

        return $variables;
    }

    /**
     * @Route("/groups/{id}")
     * @Template
     */
    public function groupAction($id, Request $request)
    {
        $variables = [];

        $variables = $this->provideCounterData($variables);

        $variables['group'] = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'groups', 'id' => $id]);

        if ($request->query->get('newUser')) {
            if (!$this->getUser()) {
                return $this->redirect($this->generateUrl('app_default_login').'?backUrl='.$request->getUri());
            }

            $userUrl = $this->defaultService->getUserUrl($this->getUser()->getUsername());
            if (!in_array($userUrl, $variables['group']['users'])) {
                $variables['group']['application'] = '/applications/'.$variables['group']['application']['id'];
                $variables['group']['users'][] = $userUrl;
                $variables['group'] = $this->commonGroundService->updateResource($variables['group']);
                $this->defaultService->throwFlash('success', "{$this->getUser()->getUsername()} has been added to the group");
            }
        }

        if ($request->isMethod('POST') && $request->get('updateInfo')) {
            $resource = $variables['group'];
            $resource['application'] = '/applications/'.$resource['application']['id'];
            $resource['name'] = $request->get('name');
            $resource['organization'] = $request->get('organization');
            $resource['description'] = $request->get('description');
            $variables['group'] = $this->commonGroundService->updateResource($resource);
        } elseif ($request->isMethod('POST') && $request->get('inviteUser')) {
            $email = $request->get('email');
            $data['link'] = $this->generateUrl('app_dashboard_group', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL).'?newUser=true';
            $data['group'] = $variables['group'];

            $this->mailingService->sendMail('mails/groupInvite.html.twig', 'no-reply@id-vault.com', $email, 'group invite', $data);
            $this->defaultService->throwFlash('success', 'Invite sent');
        }

        return $variables;
    }
}
