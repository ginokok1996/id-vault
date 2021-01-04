<?php

namespace App\Controller;

use App\Service\MailingService;
use App\Service\ScopeService;
use Conduction\BalanceBundle\Service\BalanceService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Money\Money;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
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
    /**
     * @var FlashBagInterface
     */
    private $flash;
    private $mailingService;
    private $session;
    private $request;

    public function __construct(FlashBagInterface $flash, MailingService $mailingService, Session $session, Request $request)
    {
        $this->flash = $flash;
        $this->mailingService = $mailingService;
        $this->session = $session;
        $this->request = $request;
    }

    public function provideCounterData(CommonGroundService $commonGroundService, $variables)
    {
        $user = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
        $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

        $person = $commonGroundService->getResource($this->getUser()->getPerson());
        $personUrl = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);

//        //tasks
//        $calendars = $commonGroundService->getResourceList(['component' => 'arc', 'type' => 'calendars'], ['resource' => $personUrl])['hydra:member'];
//
//        if (!count($calendars) > 0) {
//            $calendars = $commonGroundService->getResourceList(['component' => 'arc', 'type' => 'calendars'], ['resource' => $this->getUser()->getPerson()])['hydra:member'];
//        }
//
//        if (count($calendars) > 0) {
//            $calendar = $calendars[0];
//            if (count($calendar['todos']) > 0) {
//                $variables['taskCount'] = (string) count($calendar['todos']);
//            } else {
//                $variables['taskCount'] = '0';
//            }
//        }

        //alerts
        $alerts = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'alerts'], ['link' => $userUrl])['hydra:member'];
        $variables['alertCount'] = (string) count($alerts);

        return $variables;
    }

    /**
     * @Route("/")
     * @Template
     */
    public function indexAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
        $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
        $variables['authorizations'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];

        $query = [];
        $date = new \DateTime('today');
        $query['dateCreated[after]'] = $date->format('Y-m-d');
        $variables['logs'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorization_logs'], ['authorization.userUrl' => $userUrl])['hydra:member'];
        $variables['days']['monday'] = [];
        $variables['days']['tuesday'] = [];
        $variables['days']['wednesday'] = [];
        $variables['days']['thursday'] = [];
        $variables['days']['friday'] = [];
        $variables['days']['saturday'] = [];
        $variables['days']['sunday'] = [];

        if (count($variables['logs']) > 0) {
            foreach ($variables['logs'] as $log) {
                $date->modify('Monday this week');
                if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                    $variables['days']['monday'][] = $log;
                }

                $date->modify('Tuesday this week');
                if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                    $variables['days']['tuesday'][] = $log;
                }

                $date->modify('Wednesday this week');
                if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                    $variables['days']['wednesday'][] = $log;
                }

                $date->modify('Thursday this week');
                if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                    $variables['days']['thursday'][] = $log;
                }

                $date->modify('Friday this week');
                if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                    $variables['days']['friday'][] = $log;
                }

                $date->modify('Saturday this week');
                if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                    $variables['days']['saturday'][] = $log;
                }

                $date->modify('Sunday this week');
                if (strpos($log['dateCreated'], $date->format('Y-m-d')) !== false) {
                    $variables['days']['sunday'][] = $log;
                }
            }
        }

        foreach ($variables['authorizations'] as &$authorization) {
            if (isset($authorization['application']['singleSignOnUrl']) && in_array('single_sign_on', $authorization['scopes'])) {
                $application = $commonGroundService->isResource($authorization['application']['contact']);
                if ($application) {
                    if (isset($application['organization']['style']['css'])) {
                        preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                        $authorization['backgroundColor'] = $matches;
                    }
                }

                $authorization['singleSignOnUrl'] = $authorization['application']['singleSignOnUrl']."?code={$authorization['id']}";
            }
        }

        return $variables;
    }

    /**
     * @Route("/alerts")
     * @Template
     */
    public function alertsAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $user = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
        $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

        $variables['alerts'] = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'alerts'], ['link' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];

        return $variables;
    }

    /**
     * @Route("/tasks")
     * @Template
     */
    public function tasksAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $person = $commonGroundService->getResource($this->getUser()->getPerson());
        $personUrl = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
        $calendars = $commonGroundService->getResourceList(['component' => 'arc', 'type' => 'calendars'], ['resource' => $personUrl])['hydra:member'];

        if (!count($calendars) > 0) {
            $calendars = $commonGroundService->getResourceList(['component' => 'arc', 'type' => 'calendars'], ['resource' => $this->getUser()->getPerson()])['hydra:member'];
        }

        if (count($calendars) > 0) {
            $calendar = $calendars[0];
            $variables['tasks'] = $calendar['todos'];
        }

        return $variables;
    }

    /**
     * @Route("/claim-your-data/{type}")
     * @Template
     */
    public function claimYourDataAction($type = null, CommonGroundService $commonGroundService, ScopeService $scopeService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->request->query->get('authorization')) {
            $authorization = $commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $this->request->query->get('authorization')]);
            $scopes = $authorization['scopes'];

            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $user = $users[0];
            }
            $variables['deficiencies'] = $scopeService->checkScopes($scopes, $user);
        }

        if ($this->session->get('brp') && $type = 'brp') {
            $bsn = $this->session->get('brp');
            if ($this->session->get('backUrl')) {
                $backUrl = $this->session->get('backUrl');
            }
            $this->session->remove('brp');
            $variables['changedInfo'] = [];
            $ingeschrevenPersonen = $commonGroundService->getResourceList(['component' => 'brp', 'type' => 'ingeschrevenpersonen'], ['burgerservicenummer' => $bsn])['hydra:member'];
            $person = $commonGroundService->getResource($this->getUser()->getPerson());
            $person = $commonGroundService->getResource(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
            if (count($ingeschrevenPersonen) > 0) {
                $ingeschrevenPersoon = $ingeschrevenPersonen[0];
                $person['taxID'] = $ingeschrevenPersoon['burgerservicenummer'];
                $variables['changedInfo']['bsn'] = $ingeschrevenPersoon['burgerservicenummer'];

                if (isset($ingeschrevenPersoon['geboorte']['plaats']['omschrijving'])) {
                    $person['birthPlace'] = $ingeschrevenPersoon['geboorte']['plaats']['omschrijving'];
                    $variables['changedInfo']['birth_place'] = $ingeschrevenPersoon['geboorte']['plaats']['omschrijving'];
                }

                if (isset($ingeschrevenPersoon['geboorte']['datum']['datum'])) {
                    $person['birthday'] = $ingeschrevenPersoon['geboorte']['datum']['datum'];
                    $variables['changedInfo']['birthday'] = $ingeschrevenPersoon['geboorte']['datum']['datum'];
                }

                if (isset($ingeschrevenPersoon['verblijfplaats'])) {
                    $person['adresses'][0] = [];
                    $person['adresses'][0]['street'] = $ingeschrevenPersoon['verblijfplaats']['straatnaam'];
                    $person['adresses'][0]['houseNumber'] = (string) $ingeschrevenPersoon['verblijfplaats']['huisnummer'];
                    $person['adresses'][0]['houseNumberSuffix'] = (string) $ingeschrevenPersoon['verblijfplaats']['huisnummertoevoeging'];
                    $person['adresses'][0]['postalCode'] = $ingeschrevenPersoon['verblijfplaats']['postcode'];

                    $variables['changedInfo']['street'] = $ingeschrevenPersoon['verblijfplaats']['straatnaam'];
                    $variables['changedInfo']['house_number'] = (string) $ingeschrevenPersoon['verblijfplaats']['huisnummer'];
                    $variables['changedInfo']['house_number_suffix'] = (string) $ingeschrevenPersoon['verblijfplaats']['huisnummertoevoeging'];
                    $variables['changedInfo']['postal_code'] = $ingeschrevenPersoon['verblijfplaats']['postcode'];
                }

                $commonGroundService->saveResource($person, ['component' => 'cc', 'type' => 'people']);
            }
        }

        if ($this->session->get('duo') && $type = 'duo') {
            $this->session->remove('duo');
            $person = $commonGroundService->getResource($this->getUser()->getPerson());
            $person = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);

            $claim = [];
            $claim['person'] = $person;
            $claim['property'] = 'schema.person.educationalCredential';
            $claim['data']['credentialCategory'] = 'diploma';
            $claim['data']['name'] = 'Verpleegkundige';
            $claim['data']['description'] = 'De Mbo-Verpleegkundige werkt met mensen die door ziekte, ouderdom of een beperking specialistische hulp of verzorging nodig hebben. De begeleiding varieert per zorgvrager. Je kan te maken krijgen met situaties waarbij de (psychische) gezondheidstoestand van de zorgvrager snel wisselt. Het gaat dan om situaties waarbij intensieve behandeling, therapie of medicatie wordt toegepast. Je werkt zelfstandig en je bent medeverantwoordelijk voor het opstellen van zorgplannen.';
            $claim['data']['educationLevel'] = 'MBO 4';
            $claim['data']['recognizedBy'] = 'https://www.nvao.net/';

            $claim = $commonGroundService->saveResource($claim, ['component' => 'wac', 'type' => 'claims']);

            $variables['newClaim'] = $claim;

            if ($this->session->get('backUrl')) {
                $variables['backUrl'] = $this->session->get('backUrl');
                $variables['showModal'] = true;
                $this->session->remove('backUrl');
            }
        }

        if ($this->request->isMethod('POST') && $type == 'brp') {
            return $this->redirect($this->generateUrl('app_dashboard_general').'?brp='.$this->request->get('bsn'));
        } elseif ($this->request->isMethod('POST') && $type == 'duo') {
            return $this->redirect($this->generateUrl('app_dashboard_general').'?duo='.$this->request->get('bsn'));
        } elseif ($this->request->isMethod('POST') && $this->request->get('emailValidate')) {
            $data = [];
            $data['sender'] = 'no-reply@conduction.nl';
            $user = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];

            $data['resource'] = $this->generateUrl('app_dashboard_claimdata', ['type' => 'email', 'id' => $user['id']], UrlGeneratorInterface::ABSOLUTE_URL)."?email={$request->get('email')}";
            $this->mailingService->sendMail('mails/claim_your_data_email.html.twig', 'no-reply@conduction.nl', $request->get('email'), 'Claim your data', $data);

            return $this->redirectToRoute('app_dashboard_claimyourdata');
        } elseif (isset($backUrl)) {
            $this->session->remove('backUrl');

            return $this->redirect($backUrl);
        }

        return $variables;
    }

    /**
     * @Route("/general")
     * @Template
     */
    public function generalAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->request->query->get('brp')) {
            $this->session->set('brp', $this->request->query->get('brp'));

            return $this->redirect($this->generateUrl('app_dashboard_claimyourdata', ['type' => 'brp']));
        }

        if ($this->request->query->get('duo')) {
            $this->session->set('duo', $this->request->query->get('duo'));

            return $this->redirect($this->generateUrl('app_dashboard_claimyourdata', ['type' => 'duo']));
        }

        if ($this->getUser()) {
            $variables['person'] = $commonGroundService->getResource($this->getUser()->getPerson());
            $variables['person'] = $commonGroundService->getResource(['component' => 'cc', 'type' => 'people', 'id' => $variables['person']['id']]);
        }

        if ($this->request->isMethod('POST') && $this->request->get('updateInfo')) {
            $name = $this->request->get('name');
            $email = $this->request->get('email');

            // Update (or create) the cc/person of this user
            $person = $variables['person'];
            $person['name'] = $name;
            $person['emails'][0] = [];
            $person['emails'][0]['name'] = 'email for '.$name;
            $person['emails'][0]['email'] = $email;
            $person['telephones'][0] = [];
            $person['telephones'][0]['name'] = 'telephone for '.$name;
            $person['telephones'][0]['telephone'] = $this->request->get('telephone');
            $address = [];
            $address['name'] = 'address for '.$name;
            $address['street'] = $this->request->get('street');
            $address['houseNumber'] = $this->request->get('houseNumber');
            $address['houseNumberSuffix'] = $this->request->get('houseNumberSuffix');
            $address['postalCode'] = $this->request->get('postalCode');
            $address['locality'] = $this->request->get('locality');
            $person['adresses'][0] = $address;

            $person = $commonGroundService->saveResource($person, ['component' => 'cc', 'type' => 'people']);

            // If this user has no person the user.person should be set to this $person?
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $user = $users[0];

                if (!isset($user['person'])) {
                    $user['person'] = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
                    $commonGroundService->updateResource($user);
                }
            }

            return $this->redirect($this->generateUrl('app_dashboard_general'));
        } elseif ($this->request->isMethod('POST') && $this->request->get('twoFactorSwitchSubmit')) {
            // Add current user to userGroup developer.view if switch is on, else remove it instead.
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $user = $users[0];

                $userGroups = [];
                foreach ($user['userGroups'] as $userGroup) {
                    if ($userGroup['id'] != 'ff0a0468-3b92-4222-9bca-201df1ab0f42') {
                        array_push($userGroups, '/groups/'.$userGroup['id']);
                    }
                }

                $user['userGroups'] = $userGroups;
                if ($this->request->get('twoFactorSwitch')) {
                    $user['userGroups'][] = '/groups/ff0a0468-3b92-4222-9bca-201df1ab0f42';
                }
                $commonGroundService->updateResource($user);

                return $this->redirect($this->generateUrl('app_dashboard_general'));
            }
        } elseif ($this->request->isMethod('POST') && $this->request->get('becomeDeveloper')) {
            // Add current user to userGroup developer
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
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
                $commonGroundService->updateResource($user);

                $data = [];
                $data['sender'] = 'no-reply@conduction.nl';

                $this->mailingService->sendMail('mails/developer.html.twig', 'no-reply@conduction.nl', $this->request->get('email'), 'Welcome developer', $data);

                return $this->redirect($this->generateUrl('app_dashboard_general'));
            }
        }

        return $variables;
    }

    /**
     * @Route("/security")
     * @Template
     */
    public function securityAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        return $variables;
    }

    /**
     * @Route("/notifications")
     * @Template
     */
    public function notificationsAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        return $variables;
    }

    /**
     * @Route("/claimdata/{type}/{id}")
     * @Template
     */
    public function claimdataAction($id, $type, CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($type == 'email') {
            $user = $commonGroundService->getResource(['component' => 'uc', 'type' => 'users', 'id' => $id]);
            $person = $commonGroundService->getResource($user['person']);
            $personUrl = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
            $variables['value'] = $this->request->query->get('email');
            $variables['type'] = $type;

            $claims = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], ['property' => 'schema.person.email', 'person' => $personUrl])['hydra:member'];
            $variables['id'] = $id;

            if (count($claims) > 0) {
                $variables['claim'] = $claims[0];
            }

            // Add a new claim or edit one
            if ($this->request->isMethod('POST') && $type == 'email') {
                if ($this->request->get('claim')) {
                    $claim = $commonGroundService->getResource(['component' => 'wac', 'type' => 'claims', 'id' => $this->request->get('claim')]);
                    $claim['data']['email'] = $this->request->get('value');
                } else {
                    $claim = [];
                    $claim['property'] = 'schema.person.email';
                    $claim['person'] = $personUrl;
                    $claim['data']['email'] = $this->request->get('value');
                }

                $commonGroundService->saveResource($claim, (['component' => 'wac', 'type' => 'claims']));

                return $this->redirect($this->generateUrl('app_dashboard_claimyourdata'));
            }
        }

        return $variables;
    }

    /**
     * @Route("/claims")
     * @Template
     */
    public function claimsAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->getUser()) {
            $variables['claims'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], ['person' => $this->getUser()->getPerson(), 'order[dateCreated]' => 'desc'])['hydra:member'];

            // Set icon background colors and dossiers per claim
            foreach ($variables['claims'] as &$claim) {
                $claim['dossiers'] = [];

                // Set the organization background-color for the authorization icons shown with every claim
                if (isset($claim['authorizations'])) {
                    foreach ($claim['authorizations'] as &$authorization) {
                        if (isset($authorization['application']['contact'])) {
                            $application = $commonGroundService->isResource($authorization['application']['contact']);
                            if ($application) {
                                if (isset($application['organization']['style']['css'])) {
                                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                                    $authorization['iconBackgroundColor'] = $matches;
                                }
                            }
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
                        if (isset($proof['application']['contact'])) {
                            $application = $commonGroundService->isResource($proof['application']['contact']);
                            if ($application) {
                                if (isset($application['organization']['style']['css'])) {
                                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                                    $proof['iconBackgroundColor'] = $matches;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Add a new claim or edit one
        if ($this->request->isMethod('POST') && ($this->request->get('addClaim') || $this->request->get('editClaim'))) {
            $resource = $this->request->request->all();

            if ($this->getUser()) {
                $resource['person'] = $this->getUser()->getPerson();

                $resource = $commonGroundService->saveResource($resource, (['component' => 'wac', 'type' => 'claims']));

                return $this->redirect($this->generateUrl('app_dashboard_claim', ['id' => $resource['id']]));
            }
        } // Delete claim if there is no authorization connected to it
        elseif ($this->request->isMethod('POST') && $this->request->get('deleteClaim')) {
            $claim = $commonGroundService->getResource(['component' => 'wac', 'type' => 'claims', 'id' => $this->request->get('claimID')]);
            // Delete claim
            $commonGroundService->deleteResource($claim);

            return $this->redirect($this->generateUrl('app_dashboard_claims'));
        }

        return $variables;
    }

    /**
     * @Route("/claims/{id}")
     * @Template
     */
    public function claimAction($id, CommonGroundService $commonGroundService)
    {
        if (empty($this->getUser())) {
            $this->addFlash('error', 'This page requires you to be logged in');

            return $this->redirect($this->generateUrl('app_default_login'));
        }
        if (!$id) {
            $this->addFlash('error', 'No id provided');

            return $this->redirect($this->generateUrl('app_dashboard_claims'));
        }

        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'claims', 'id' => $id]);

        // Set the organization background-color for the icons shown with every authorization of this claim
        if (isset($variables['resource']['authorizations'])) {
            foreach ($variables['resource']['authorizations'] as &$authorization) {
                if (isset($authorization['application']['contact'])) {
                    $application = $commonGroundService->isResource($authorization['application']['contact']);
                    if ($application) {
                        if (isset($application['organization']['style']['css'])) {
                            preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                            $authorization['backgroundColor'] = $matches;
                        }
                    }
                }
            }
        }

        if ($variables['resource']['person'] != $this->getUser()->getPerson()) {
            $this->addFlash('error', 'You do not have access to this claim');

            return $this->redirect($this->generateUrl('app_dashboard_claims'));
        }

        return $variables;
    }

    /**
     * @Route("/authorizations")
     * @Template
     */
    public function authorizationsAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->getUser()) {
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
            $variables['authorizations'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];

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
                    $application = $commonGroundService->isResource($authorization['application']['contact']);
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
        if ($this->request->isMethod('POST') && ($this->request->get('endAuthorization') || $this->request->get('endClaimAuthorization'))) {
            $authorization = $commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $this->request->get('authorizationID')]);
            // Delete authorization
            $commonGroundService->deleteResource($authorization);

            // Redirect correctly
            if ($this->request->get('endClaimAuthorization')) {
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
    public function authorizationAction($id, CommonGroundService $commonGroundService)
    {
        if (empty($this->getUser())) {
            $this->addFlash('error', 'This page requires you to be logged in');

            return $this->redirect($this->generateUrl('app_default_login'));
        }
        if (!$id) {
            $this->addFlash('error', 'No id provided');

            return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
        }

        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $id]);

        // Set this resources as authorization for each authorizationLog and set icon background-color
        foreach ($variables['resource']['authorizationLogs'] as &$log) {
            // Set this resources as authorization for this Log
            $log['authorization'] = $variables['resource'];

            // Set the organization background-color for the icon shown with this log
            if (key_exists('contact', $log['authorization']['application']) && !empty($log['authorization']['application']['contact'])) {
                $application = $commonGroundService->isResource($log['authorization']['application']['contact']);
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
            $application = $commonGroundService->isResource($variables['resource']['application']['contact']);
            if ($application) {
                if (isset($application['organization']['style']['css'])) {
                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                    $variables['resource']['backgroundColor'] = $matches;
                }
            }
        }

        $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
        $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
        if ($variables['resource']['userUrl'] != $userUrl) {
            $this->addFlash('error', 'You do not have access to this authorization');

            return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
        }

        return $variables;
    }

    /**
     * @Route("/dossiers")
     * @Template
     */
    public function dossiersAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->getUser()) {
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
            $variables['dossiers'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'dossiers'], ['authorization.userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        // Delete dossier and redirect
        if ($this->request->isMethod('POST') && ($this->request->get('deleteDossier') || $this->request->get('deleteAuthorizationDossier') || $this->request->get('deleteClaimAuthorizationDossier'))) {
            $dossier = $commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id' => $this->request->get('dossierID')]);
            // Delete dossier
            $commonGroundService->deleteResource($dossier);

            // Redirect correctly
            if ($this->request->get('deleteClaimAuthorizationDossier')) {
                return $this->redirect($this->generateUrl('app_dashboard_claims'));
            } elseif ($this->request->get('deleteAuthorizationDossier')) {
                return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
            } else {
                return $this->redirect($this->generateUrl('app_dashboard_dossiers'));
            }
        } elseif ($this->request->isMethod('POST') && ($this->request->get('dossierObjection'))) {
            $dossier = $commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id' => $this->request->get('dossierID')]);

            // Create vrc request
//            $vrcRequest['status'] = 'submitted';
//            $vrcRequest['organization'] = 'url org';
//            $vrcRequest['requestType'] = 'url request type';
//            $vrcRequest['processType'] = 'url process type';
//            $vrcRequest['properties'] = [
//                'dossier'     => $commonGroundService->cleanUrl(['component' => 'wac', 'type' => 'dossiers', 'id' => $dossier['id']]),
//                'explanation' => $request->get('explanation'),
//            ];
//            $vrcRequest = $commonGroundService->createResource($vrcRequest, ['component' => 'vrc', 'type' => 'requests']);

//            $this->flash->add('success', 'Objection submitted for: '.$dossier['name']);
            $this->flash->add('error', 'No objection submitted for: '.$dossier['name']);

            return $this->redirect($this->generateUrl('app_dashboard_dossiers'));
        }

        return $variables;
    }

    /**
     * @Route("/dossiers/{id}")
     * @Template
     */
    public function dossierAction($id, CommonGroundService $commonGroundService)
    {
        if (empty($this->getUser())) {
            $this->addFlash('error', 'This page requires you to be logged in');

            return $this->redirect($this->generateUrl('app_default_login'));
        }
        if (!$id) {
            $this->addFlash('error', 'No id provided');

            return $this->redirect($this->generateUrl('app_dashboard_dossiers'));
        }

        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id' => $id]);

        // Set the organization background-color for the icon shown with the authorization of this dossier
        if (isset($variables['resource']['authorization']['application']['contact'])) {
            $application = $commonGroundService->isResource($variables['resource']['authorization']['application']['contact']);
            if ($application) {
                if (isset($application['organization']['style']['css'])) {
                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                    $variables['resource']['authorization']['backgroundColor'] = $matches;
                }
            }
        }

        $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
        $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
        if ($variables['resource']['authorization']['userUrl'] != $userUrl) {
            $this->addFlash('error', 'You do not have access to this dossier');

            return $this->redirect($this->generateUrl('app_dashboard_dossiers'));
        }

        return $variables;
    }

    /**
     * @Route("/applications")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function applicationsAction(CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $applications = [];

        if ($this->getUser()) {
            $application = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $params->get('app_id')]);
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $organizations = [];
                $user = $users[0];
                foreach ($user['userGroups'] as $group) {
                    $organization = $commonGroundService->getResource($group['organization']);
                    if (!in_array($organization, $organizations) && $organization['id'] !== $application['organization']['id']) {
                        $organizations[] = $organization;
                    }
                }

                foreach ($organizations as $organization) {
                    $cleanUrl = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
                    $newApplications = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['organization' => $cleanUrl])['hydra:member'];
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
                        $applicationContact = $commonGroundService->getResource($application['contact']);
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

        if ($this->request->isMethod('POST') && $this->request->get('newApplication')) {
            $name = $this->request->get('name');
            $application['name'] = $name;
            $application['description'] = $this->request->get('description');

            // Create a wRc application
            $wrcApplication['name'] = $name;
            $wrcApplication['description'] = $this->request->get('description');
            $wrcApplication['organization'] = '/organizations/'.$this->request->get('organization');
            $wrcApplication['domain'] = $this->request->get('domain');

//            if (isset($_FILES['applicationLogo']) && $_FILES['applicationLogo']['error'] !== 4) {
//                $path = $_FILES['applicationLogo']['tmp_name'];
//                $type = filetype($_FILES['applicationLogo']['tmp_name']);
//                $data = file_get_contents($path);
//                $wrcApplication['style']['name'] = 'style for '.$name;
//                $wrcApplication['style']['description'] = 'style for '.$name;
//                $wrcApplication['style']['css'] = ' ';
//                $wrcApplication['style']['favicon']['name'] = 'logo for '.$name;
//                $wrcApplication['style']['favicon']['description'] = 'logo for '.$name;
//                $wrcApplication['style']['favicon']['base64'] = 'data:image/'.$type.';base64,'.base64_encode($data);
//            }
            $wrcApplication = $commonGroundService->createResource($wrcApplication, ['component' => 'wrc', 'type' => 'applications']);

            // Create a wAc application
            $user = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
            $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

            $application['organization'] = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $this->request->get('organization')]);
            $application['authorizationUrl'] = $this->request->get('passthroughUrl');
            $application['contact'] = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'applications', 'id' => $wrcApplication['id']]);
            $application['gdprContact'] = $userUrl;
            $application['technicalContact'] = $userUrl;
            $application['privacyContact'] = $userUrl;
            $application['billingContact'] = $userUrl;
            $commonGroundService->createResource($application, ['component' => 'wac', 'type' => 'applications']);

            return $this->redirect($this->generateUrl('app_dashboard_applications'));
        }

        return $variables;
    }

    /**
     * @Route("/applications/{id}")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function applicationAction($id, CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->request->isMethod('POST') && $this->request->get('updateInfo')) {
            $application = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            $wrcApplication = $commonGroundService->getResource($application['contact']);

            //application
            $application['name'] = $this->request->get('name');
            $application['description'] = $this->request->get('description');
            $application['authorizationUrl'] = $this->request->get('authorizationUrl');
            $application['webhookUrl'] = $this->request->get('webhookUrl');
            $application['singleSignOnUrl'] = $this->request->get('singleSignOnUrl');
            $application['mailgunApiKey'] = $this->request->get('mailgunApiKey');
            $application['mailgunDomain'] = $this->request->get('mailgunDomain');
            $application['messageBirdApiKey'] = $this->request->get('messageBirdApiKey');

            $application['gdprContact'] = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $this->request->get('gdprContact')]);
            $application['technicalContact'] = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $this->request->get('technicalContact')]);
            $application['privacyContact'] = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $this->request->get('privacyContact')]);
            $application['billingContact'] = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $this->request->get('billingContact')]);

            $application = $commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);

            //wrc application
            $wrcApplication['name'] = $this->request->get('name');
            $wrcApplication['domain'] = $this->request->get('domain');
            $wrcApplication['organization'] = '/organizations/'.$wrcApplication['organization']['id'];

            if (isset($wrcApplication['style'])) {
                $wrcApplication['style'] = '/styles/'.$wrcApplication['style']['id'];
            }

            $wrcApplication = $commonGroundService->saveResource($wrcApplication, ['component' => 'wrc', 'type' => 'applications']);
        } elseif ($this->request->isMethod('POST') && $this->request->get('updateScopes')) {
            $application = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            $application['scopes'] = $this->request->get('scopes');

            $application = $commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);
        } // Add a new mailing list or edit one
        elseif ($this->request->isMethod('POST') && ($this->request->get('addMailingList') || $this->request->get('editMailingList'))) {
            $resource = $this->request->request->all();

            // Save mailing list
            $resource['email'] = true;
            $resource = $commonGroundService->saveResource($resource, (['component' => 'bs', 'type' => 'send_lists']));

            // add mailing list to wac application
            $application = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
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
            $commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);

            return $this->redirect($this->generateUrl('app_dashboard_application', ['id' => $id]).'#'.$resource['id']);
        } // Delete mailing list
        elseif ($this->request->isMethod('POST') && $this->request->get('deleteMailingList')) {
            $sendList = $commonGroundService->getResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $this->request->get('mailingListID')]);

            // Remove mailing list from wac application
            $application = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
            $sendLists = [];
            if (isset($application['sendLists'])) {
                foreach ($application['sendLists'] as $sendListItem) {
                    if ($sendListItem != $sendList['id']) {
                        array_push($sendLists, $sendListItem);
                    }
                }
            }
            $application['sendLists'] = $sendLists;
            $commonGroundService->saveResource($application, ['component' => 'wac', 'type' => 'applications']);

            // Delete mailing list
            $commonGroundService->deleteResource($sendList);

            return $this->redirect($this->generateUrl('app_dashboard_application', ['id' => $id]).'#mailingLists');
        }

        $variables['application'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $id]);
        $variables['wrcApplication'] = $commonGroundService->getResource($variables['application']['contact']);

        if (isset($variables['application']['sendLists'])) {
            $sendLists = [];
            foreach ($variables['application']['sendLists'] as $sendListId) {
                if ($commonGroundService->isResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendListId])) {
                    array_push($sendLists, $commonGroundService->getResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendListId]));
                }
            }
            if (count($sendLists) > 0) {
                $variables['sendLists'] = $sendLists;
            }
        }

        $variables['wrcOrganization'] = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $variables['wrcApplication']['organization']['id']]);
        $groups = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $variables['wrcOrganization']])['hydra:member'];
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
    public function conductionAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $query = [];
        $date = new \DateTime('today');
        if ($this->request->isMethod('POST')) {
            $type = $this->request->get('type');

            switch ($type) {
                case 'day':
                    $query['dateCreated[after]'] = $date->format('Y-m-d');
                    break;
                case 'week':
                    $date->modify('Monday this week');
                    $query['dateCreated[after]'] = $date->format('Y-m-d');
                    break;
                case 'month':
                    $date->modify('first day of this month');
                    $query['dateCreated[after]'] = $date->format('Y-m-d');
                    break;
                case 'quarter':
                    $offset = (date('n') % 3) - 1;
                    $date->modify("first day of -$offset month midnight");
                    $query['dateCreated[after]'] = $date->format('Y-m-d');
                    break;
                case 'year':
                    $date->modify('first day of january');
                    $query['dateCreated[after]'] = $date->format('Y-m-d');
                    break;
            }
        }

        $variables['users'] = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], $query)['hydra:member'];
        $variables['organizations'] = $commonGroundService->getResourceList(['component' => 'wrc', 'type' => 'organizations'], $query)['hydra:member'];
        $variables['applications'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], $query)['hydra:member'];
        $variables['claims'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], $query)['hydra:member'];

        return $variables;
    }

    /**
     * @Route("/logs")
     * @Template
     */
    public function logsAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->getUser()) {
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
            $variables['logs'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorization_logs'], ['authorization.userUrl' => $userUrl, 'order[dateCreated]' => 'desc'])['hydra:member'];

            // Set the organization background-color for the icons shown with every log
            foreach ($variables['logs'] as &$log) {
                if (isset($log['authorization']['application']['contact'])) {
                    $application = $commonGroundService->isResource($log['authorization']['application']['contact']);
                    if ($application) {
                        if (isset($application['organization']['style']['css'])) {
                            preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                            $log['backgroundColor'] = $matches;
                        }
                    }
                }
            }
        }

        return $variables;
    }

    /**
     * @Route("/logs/{id}")
     * @Template
     */
    public function logAction($id, CommonGroundService $commonGroundService)
    {
        if (empty($this->getUser())) {
            $this->addFlash('error', 'This page requires you to be logged in');

            return $this->redirect($this->generateUrl('app_default_login'));
        }
        if (!$id) {
            $this->addFlash('error', 'No id provided');

            return $this->redirect($this->generateUrl('app_dashboard_logs'));
        }

        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'authorization_logs', 'id' => $id], ['order[dateCreated]' => 'desc']);

        // Set the organization background-color for the icon shown with this log
        if (isset($variables['resource']['authorization']['application']['contact'])) {
            $application = $commonGroundService->isResource($variables['resource']['authorization']['application']['contact']);
            if ($application) {
                if (isset($application['organization']['style']['css'])) {
                    preg_match('/background-color: ([#A-Za-z0-9]+)/', $application['organization']['style']['css'], $matches);
                    $variables['resource']['backgroundColor'] = $matches;
                }
            }
        }

        $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
        $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
        if ($variables['resource']['authorization']['userUrl'] != $userUrl) {
            $this->addFlash('error', 'You do not have access to this log');

            return $this->redirect($this->generateUrl('app_dashboard_logs'));
        }

        return $variables;
    }

    /**
     * @Route("/organizations")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function organizationsAction(BalanceService $balanceService, CommonGroundService $commonGroundService, ParameterBagInterface $params)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        if ($this->request->isMethod('POST')) {
            $name = $this->request->get('name');
            $email = $this->request->get('email');
            $description = $this->request->get('description');

            $cc = [];
            $cc['name'] = $name;
            $cc['description'] = $description;
            $cc['emails'][0]['name'] = 'email for '.$name;
            $cc['emails'][0]['email'] = $email;
            $cc['adresses'][0]['name'] = 'address for '.$name;

            $cc = $commonGroundService->createResource($cc, ['component' => 'cc', 'type' => 'organizations']);

            $wrc = [];
            $wrc['rsin'] = ' ';
            $wrc['chamberOfComerce'] = ' ';
            $wrc['name'] = $name;
            $wrc['description'] = $description;
            $wrc['contact'] = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organizations', 'id' => $cc['id']]);
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

            $wrc = $commonGroundService->createResource($wrc, ['component' => 'wrc', 'type' => 'organizations']);

            $organizationUrl = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $wrc['id']]);

            $validChars = '0123456789';
            $reference = substr(str_shuffle(str_repeat($validChars, ceil(3 / strlen($validChars)))), 1, 10);

            $account = [];
            $account['resource'] = $organizationUrl;
            $account['reference'] = $reference;
            $account['name'] = $wrc['name'];

            $account = $commonGroundService->createResource($account, ['component' => 'bare', 'type' => 'acounts']);

            $balanceService->addCredit(Money::EUR(1000), $organizationUrl, $wrc['name']);

            $userGroup = [];
            $userGroup['name'] = 'developers-'.$name;
            $userGroup['title'] = 'developers-'.$name;
            $userGroup['description'] = 'developers group for '.$name;
            $userGroup['organization'] = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $wrc['id']]);

            $group = $commonGroundService->createResource($userGroup, ['component' => 'uc', 'type' => 'groups']);

            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $organizations = [];
                $user = $users[0];

                $userGroups = [];
                foreach ($user['userGroups'] as $userGroup) {
                    array_push($userGroups, '/groups/'.$userGroup['id']);
                }

                $user['userGroups'] = $userGroups;
                $user['userGroups'][] = '/groups/'.$group['id'];

                $commonGroundService->updateResource($user);
            }
        }

        if ($this->getUser()) {
            $application = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $params->get('app_id')]);
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $organizations = [];
                $user = $users[0];
                foreach ($user['userGroups'] as $group) {
                    $organization = $commonGroundService->getResource($group['organization']);
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
    public function organizationAction($id, CommonGroundService $commonGroundService, BalanceService $balanceService)
    {
        $variables = [];

        $variables = $this->provideCounterData($commonGroundService, $variables);

        $variables['organization'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);

        $organizationUrl = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
        $account = $balanceService->getAcount($organizationUrl);
        if ($account !== false) {
            $account['balance'] = $balanceService->getBalance($organizationUrl);
            $variables['account'] = $account;
            $variables['payments'] = $commonGroundService->getResourceList(['component' => 'bare', 'type' => 'payments'], ['acount.id' => $account['id'], 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        if (key_exists('contact', $variables['organization']) and !empty($variables['organization']['contact'])) {
            $variables['cc'] = $commonGroundService->getResource($variables['organization']['contact']);
        }
        $organization = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
        $variables['applications'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'applications'], ['organization' => $organization])['hydra:member'];

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

        $groups = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'], ['organization' => $organization])['hydra:member'];
        if (count($groups) > 0) {
            $group = $groups[0];
            $variables['users'] = $group['users'];
        }

        if ($this->request->isMethod('POST') && $this->request->get('newDeveloper')) {
        } elseif ($this->request->isMethod('POST') && $this->request->get('newApplication')) {
            $name = $this->request->get('name');
            $application['name'] = $name;
            $application['description'] = $this->request->get('description');

            // Create a wRc application
            $wrcApplication['name'] = $name;
            $wrcApplication['description'] = $this->request->get('description');
            $wrcApplication['organization'] = '/organizations/'.$id;
            $wrcApplication['domain'] = $this->request->get('domain');

//            if (isset($_FILES['applicationLogo']) && $_FILES['applicationLogo']['error'] !== 4) {
//                $path = $_FILES['applicationLogo']['tmp_name'];
//                $type = filetype($_FILES['applicationLogo']['tmp_name']);
//                $data = file_get_contents($path);
//                $wrcApplication['style']['name'] = 'style for '.$name;
//                $wrcApplication['style']['description'] = 'style for '.$name;
//                $wrcApplication['style']['css'] = ' ';
//                $wrcApplication['style']['favicon']['name'] = 'logo for '.$name;
//                $wrcApplication['style']['favicon']['description'] = 'logo for '.$name;
//                $wrcApplication['style']['favicon']['base64'] = 'data:image/'.$type.';base64,'.base64_encode($data);
//            }
            $wrcApplication = $commonGroundService->createResource($wrcApplication, ['component' => 'wrc', 'type' => 'applications']);

            // Create a wAc application
            $user = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
            $userUrl = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

            $application['organization'] = $organization;
            $application['authorizationUrl'] = $this->request->get('passthroughUrl');
            $application['contact'] = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'applications', 'id' => $wrcApplication['id']]);
            $application['gdprContact'] = $userUrl;
            $application['technicalContact'] = $userUrl;
            $application['privacyContact'] = $userUrl;
            $application['billingContact'] = $userUrl;
            $commonGroundService->createResource($application, ['component' => 'wac', 'type' => 'applications']);

            return $this->redirect($this->generateUrl('app_dashboard_organization', ['id' => $id]));
        } elseif ($this->request->isMethod('POST') && $this->request->get('updateInfo')) {
            $name = $this->request->get('name');
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== 4) {
                if (key_exists('style', $variables['organization']) and !empty($variables['organization']['style'])) {
                    if (key_exists('favicon', $variables['organization']['style']) and !empty($variables['organization']['style']['favicon'])) {
                        $icon = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'images', 'id' => $variables['organization']['style']['favicon']['id']]);
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
                $commonGroundService->saveResource($icon);
            }

            $organization = $variables['organization'];
            $organization['name'] = $name;
            $organization['description'] = $this->request->get('description');
            if (key_exists('style', $organization) and !empty($organization['style'])) {
                $organization['style'] = '/styles/'.$organization['style']['id'];
            }
            $commonGroundService->updateResource($organization);

            if (key_exists('cc', $variables)) {
                $cc = $variables['cc'];
                $cc['name'] = $name;
                $cc['emails'][0] = [];
                $cc['emails'][0]['name'] = 'email for '.$name;
                $cc['emails'][0]['email'] = $request->get('email');
                $address = [];
                $address['name'] = 'address for '.$name;
                $address['street'] = $this->request->get('street');
                $address['houseNumber'] = $this->request->get('houseNumber');
                $address['houseNumberSuffix'] = $this->request->get('houseNumberSuffix');
                $address['postalCode'] = $this->request->get('postalCode');
                $address['locality'] = $this->request->get('locality');
                $cc['adresses'][0] = $address;
                $commonGroundService->updateResource($cc);

                $variables['organization'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
                $variables['cc'] = $commonGroundService->getResource($variables['organization']['contact']);
            }
        }

        return $variables;
    }

    /**
     * @Route("/transactions/{organization}")
     * @Template
     */
    public function TransactionsAction(CommonGroundService $commonGroundService, BalanceService $balanceService, $organization)
    {
        // On an index route we might want to filter based on user input
        $variables = [];

        $organization = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization]);
        $organizationUrl = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
        $variables['organization'] = $organization;

        if ($this->session->get('mollieCode')) {
            $mollieCode = $this->session->get('mollieCode');
            $this->session->remove('mollieCode');
            $result = $balanceService->processMolliePayment($mollieCode, $organizationUrl);

            if ($result['status'] == 'paid') {
                $variables['message'] = 'Payment processed successfully! <br> €'.$result['amount'].'.00 was added to your balance. <br>  Invoice with reference: '.$result['reference'].' is created.';
            } else {
                $variables['message'] = 'Something went wrong, the status of the payment is: '.$result['status'].' please try again.';
            }
        }

        $account = $balanceService->getAcount($organizationUrl);

        if ($account !== false) {
            $account['balance'] = $balanceService->getBalance($organizationUrl);
            $variables['account'] = $account;
            $variables['payments'] = $commonGroundService->getResourceList(['component' => 'bare', 'type' => 'payments'], ['acount.id' => $account['id'], 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        if ($this->request->isMethod('POST')) {
            $amount = $this->request->get('amount') * 1.21;
            $amount = (number_format($amount, 2));

            $payment = $balanceService->createMolliePayment($amount, $this->request->get('redirectUrl'));
            $this->session->set('mollieCode', $payment['id']);

            return $this->redirect($payment['redirectUrl']);
        }

        return $variables;
    }

    /**
     * @Route("/invoices")
     * @Template
     */
    public function InvoicesAction(CommonGroundService $commonGroundService)
    {
        $variables = [];

        if (!empty($this->getUser()->getOrganization())) {
            $organization = $commonGroundService->getResource($this->getUser()->getOrganization());
            $organizationUrl = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
            $variables['invoices'] = $commonGroundService->getResourceList(['component' => 'bc', 'type' => 'invoices'], ['customer' => $organizationUrl])['hydra:member'];
        }

        return $variables;
    }

    /**
     * @Route("/invoice/{id}")
     * @Template
     */
    public function InvoiceAction(CommonGroundService $commonGroundService, $id)
    {
        $variables = [];

        $variables['invoice'] = $commonGroundService->getResource(['component' => 'bc', 'type' => 'invoices', 'id' => $id]);

        return $variables;
    }
}
