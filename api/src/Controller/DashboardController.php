<?php

// src/Controller/ProcessController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\ApplicationService;
//use App\Service\RequestService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/general")
     * @Template
     */
    public function generalAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];
        if ($this->getUser()) {
            $personUrl = $this->getUser()->getPerson();
            $variables['person'] = $commonGroundService->getResource($personUrl);
        }

        if ($request->isMethod('POST') && $request->get('updateInfo')) {
            $name = $request->get('name');
            $email = $request->get('email');

            // Update the cc/person of this user
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
            $commonGroundService->updateResource($person);

            $variables['person'] = $commonGroundService->getResource($variables['person']);
        } elseif ($request->isMethod('POST') && $request->get('twoFactorSwitchSubmit')) {
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
                if ($request->get('twoFactorSwitch')) {
                    $user['userGroups'][] = '/groups/ff0a0468-3b92-4222-9bca-201df1ab0f42';
                }
                $commonGroundService->updateResource($user);

                return $this->redirect($this->generateUrl('app_dashboard_general'));
            }
        } elseif ($request->isMethod('POST') && $request->get('becomeDeveloper')) {
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

                return $this->redirect($this->generateUrl('app_dashboard_general'));
            }
        }

        return $variables;
    }

    /**
     * @Route("/security")
     * @Template
     */
    public function securityAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/notifications")
     * @Template
     */
    public function notificationsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/claims")
     * @Template
     */
    public function claimsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if ($this->getUser()) {
            $variables['claims'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], ['person' => $this->getUser()->getPerson(), 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        // Add a new claim
        if ($request->isMethod('POST') && $request->get('addClaim')) {
            $resource = $request->request->all();

            if ($this->getUser()) {
                $resource['person'] = $this->getUser()->getPerson();

                $resource = $commonGroundService->saveResource($resource, (['component' => 'wac', 'type' => 'claims']));

                return $this->redirect($this->generateUrl('app_dashboard_claim', ['id'=>$resource['id']]));
            }
        }
        // Delete claim if there is no authorization connected to it
        elseif ($request->isMethod('POST') && $request->get('deleteClaim')) {
            $claim = $commonGroundService->getResource(['component' => 'wac', 'type' => 'claims', 'id' => $request->get('claimID')]);
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
    public function claimAction(Session $session, Request $request, $id = null, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
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
        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'claims', 'id'=>$id]);

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
    public function authorizationsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

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
                if(key_exists('contact', $authorization['application']) and !empty($authorization['application']['contact'])) {
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
        if ($request->isMethod('POST') && ($request->get('endAuthorization') || $request->get('endClaimAuthorization'))) {
            $authorization = $commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id' => $request->get('authorizationID')]);
            // Delete authorization
            $commonGroundService->deleteResource($authorization);

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
    public function authorizationAction(Session $session, Request $request, $id = null, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
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
        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'authorizations', 'id'=>$id]);

        if ($variables['resource'] != $this->getUser()->getPerson()) {
            $this->addFlash('error', 'You do not have access to this authorization');

            return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
        }

        return $variables;
    }

    /**
     * @Route("/dossiers")
     * @Template
     */
    public function dossiersAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if ($this->getUser()) {
            $variables['dossiers'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'dossiers'], ['authorization.person' => $this->getUser()->getPerson(), 'order[dateCreated]' => 'desc'])['hydra:member'];
        }

        // Delete dossier and redirect
        if ($request->isMethod('POST') && ($request->get('deleteDossier') || $request->get('deleteAuthorizationDossier') || $request->get('deleteClaimAuthorizationDossier'))) {
            $dossier = $commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id' => $request->get('dossierID')]);
            // Delete dossier
            $commonGroundService->deleteResource($dossier);

            // Redirect correctly
            if ($request->get('deleteClaimAuthorizationDossier')) {
                return $this->redirect($this->generateUrl('app_dashboard_claims'));
            } elseif ($request->get('deleteAuthorizationDossier')) {
                return $this->redirect($this->generateUrl('app_dashboard_authorizations'));
            } else {
                return $this->redirect($this->generateUrl('app_dashboard_dossiers'));
            }
        }

        return $variables;
    }

    /**
     * @Route("/dossiers/{id}")
     * @Template
     */
    public function dossierAction(Session $session, Request $request, $id = null, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
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
        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id'=>$id]);

        if ($variables['resource']['authorization']['person'] != $this->getUser()->getPerson()) {
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
    public function applicationsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/conduction")
     * @Template
     */
    public function conductionAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];
        $query = [];
        $date = new \DateTime('today');
        if ($request->isMethod('POST')) {
            $type = $request->get('type');

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
    public function logsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if ($this->getUser()) {
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            $user = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);
            $variables['logs'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorization_logs'], ['authorization.userUrl' => $user])['hydra:member'];

            // Set the organization background-color for the icons shown with every log
            foreach ($variables['logs'] as &$log) {
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
        }

        return $variables;
    }

    /**
     * @Route("/organizations")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function organizationsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

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

        return $variables;
    }

    /**
     * @Route("/organizations/{id}")
     * @Security("is_granted('ROLE_group.developer')")
     * @Template
     */
    public function organizationAction(Session $session, Request $request, $id, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];
        $variables['organization'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
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

        if ($request->isMethod('POST') && $request->get('newDeveloper')) {
            $name = $request->get('name');
            $email = $request->get('email');

            // Check if there is a developer user with the given email address.
            // Get all users in the developer group
            $group = $commonGroundService->getResource(['component' => 'uc', 'type' => 'groups', 'id' => 'c3c463b9-8d39-4cc0-b62c-826d8f5b7d8c']);
            $users = $group['users'];
            foreach ($users as $user) {
                if (key_exists('person', $user) && !empty($user['person'])) {
                    $person = $commonGroundService->getResource($user['person']);
                    if (key_exists('emails', $person) && !empty($person['emails']) && count($person['emails']) > 0) {
                        if ($person['emails'][0]['email'] == $email) {
                            $receiver = $user['person']; // Needs to be url!
                        }
                    }
                }
            }

            if (isset($receiver)) {
                // Create the email message
                $message = [];
                $message['service'] = '/services/1541d15b-7de3-4a1a-a437-80079e4a14e0';
                $message['status'] = 'queued';

                // lets use the organization contact as sender
                if (key_exists('cc', $variables)) {
                    $sender = $variables['cc'];
                    $message['sender'] = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'organization', 'id' => $variables['cc']]);
                } else {
                    $sender = $variables['organization'];
                }

                // if we don't have that we are going to self send te message
                $message['reciever'] = $receiver; // reciever = typo in BS
                if (!key_exists('sender', $message)) {
                    $message['sender'] = $receiver;
                }
                $message['data'] = ['sender'=>$sender, 'receiver'=>$commonGroundService->getResource($receiver)];
                $message['content'] = $commonGroundService->cleanUrl(['component'=>'wrc', 'type'=>'templates', 'id'=>'61162867-9811-451c-ac41-e38cb58698af']);

                // Send the email to this contact
                $commonGroundService->createResource($message, ['component'=>'bs', 'type'=>'messages']);
            }
        } elseif ($request->isMethod('POST') && $request->get('newApplication')) {
            $name = $request->get('name');
            $application['name'] = $name;
            $application['description'] = $request->get('description');

            // Create a wRc application
            $wrcApplication = $application;
            $wrcApplication['organization'] = '/organizations/'.$id;
            $wrcApplication['domain'] = $request->get('domain');

            // TODO: fix saving the (style and) logo
//            if (isset($_FILES['applicationLogo']) && $_FILES['applicationLogo']['error'] !== 4) {
//                $path = $_FILES['applicationLogo']['tmp_name'];
//                $type = filetype($_FILES['applicationLogo']['tmp_name']);
//                $data = file_get_contents($path);
//
//                // Create a wRc style (and favicon image)
//                $style['name'] = 'style for '.$name;
//                $style['description'] = 'style for '.$name;
//                $style['css'] = ' ';
//                $style['organization'] = '/organizations/'.$id;
//                $style['favicon']['name'] = 'logo for '.$name;
//                $style['favicon']['description'] = 'logo for '.$name;
//                $style['favicon']['base64'] = 'data:image/'.$type.';base64,'.base64_encode($data);
//                $style = $commonGroundService->createResource($style, ['component' => 'wrc', 'type' => 'styles']);
//
//                $wrcApplication['style'] = '/styles/'.$style['id'];
//            }
            $wrcApplication = $commonGroundService->createResource($wrcApplication, ['component' => 'wrc', 'type' => 'applications']);

            // Create a wAc application
            $application['organization'] = $organization;
            $application['authorizationUrl'] = $request->get('passthroughUrl');
            $application['contact'] = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'applications', 'id' => $wrcApplication['id']]);
            $commonGroundService->createResource($application, ['component' => 'wac', 'type' => 'applications']);

            return $this->redirect($this->generateUrl('app_dashboard_organization', ['id'=>$id]));
        } elseif ($request->isMethod('POST') && $request->get('updateInfo')) {
            $name = $request->get('name');
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
            $organization['description'] = $request->get('description');
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
                $address['street'] = $request->get('street');
                $address['houseNumber'] = $request->get('houseNumber');
                $address['houseNumberSuffix'] = $request->get('houseNumberSuffix');
                $address['postalCode'] = $request->get('postalCode');
                $address['locality'] = $request->get('locality');
                $cc['adresses'][0] = $address;
                $commonGroundService->updateResource($cc);

                $variables['organization'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
                $variables['cc'] = $commonGroundService->getResource($variables['organization']['contact']);
            }
        }

        return $variables;
    }
}
