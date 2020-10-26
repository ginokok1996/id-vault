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

        // Set current user to userGroup developer
        if ($request->isMethod('POST')) {
            $person = $this->getUser()->getPerson();
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['person' => $person])['hydra:member'];
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
        $variables['claims'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'claims'], ['person' => $this->getUser()->getPerson(), 'order[dateCreated]' => 'desc'])['hydra:member'];

        if ($request->isMethod('POST')) {
            $resource = $request->request->all();

            $resource['person'] = $this->getUser()->getPerson();

            $commonGroundService->saveResource($resource, (['component' => 'wac', 'type' => 'claims']));

            return $this->redirect($this->generateUrl('app_dashboard_claims'));
        }

        return $variables;
    }

    /**
     * @Route("/contracts")
     * @Template
     */
    public function contractsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];
        $variables['contracts'] = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'contracts'], ['person' => $this->getUser()->getPerson(), 'order[dateCreated]' => 'desc'])['hydra:member'];

        return $variables;
    }

    /**
     * @Route("/applications")
     * @Template
     */
    public function applicationsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/organizations")
     * @Template
     */
    public function organizationsAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if ($request->isMethod('POST')) {
            $name = $request->get('name');
            $email = $request->get('email');
            $description = $request->get('description');

            $cc = [];
            $cc['name'] = $name;
            $cc['description'] = $description;
            $cc['emails'][0]['email'] = $email;

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

        if ($this->getUser()) {
            $application = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $params->get('app_id') ]);
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
            }
        }

        return $variables;
    }

    /**
     * @Route("/organizations/{id}")
     * @Template
     */
    public function organizationAction(Session $session, Request $request, $id, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];
        $variables['organization'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
        $variables['cc'] = $commonGroundService->getResource($variables['organization']['contact']);
        $organization = $commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
        $variables['applications'] = $commonGroundService->getResourceList(['component' => 'wrc', 'type' => 'applications'], ['organization' => '/organizations/'.$variables['organization']['id']])['hydra:member'];

        $groups = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'groups'],['title' => 'developers'.$variables['organization']['name'], 'organization' => $organization])['hydra:member'];
        if (count($groups) > 0){
            $group = $groups[0];
            $variables['users'] = $group['users'];
        }

        if ($request->isMethod('POST') && $request->get('newDeveloper')) {

        } elseif ($request->isMethod('POST') && $request->get('updateInfo')) {
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== 4) {
                $icon = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'images', 'id' => $variables['organization']['style']['favicon']['id']]);
                $path = $_FILES['logo']['tmp_name'];
                $type = filetype($_FILES['logo']['tmp_name']);
                $data = file_get_contents($path);
                $icon['base64'] = 'data:image/'.$type.';base64,'.base64_encode($data);
                $commonGroundService->updateResource($icon);

            }

            $organization = $variables['organization'];
            $organization['name'] = $request->get('name');
            $organization['style'] = '/styles/'.$organization['style']['id'];
            $commonGroundService->updateResource($organization);

            $cc = $variables['cc'];
            $cc['name'] = $request->get('name');
            $cc['emails'][0] = [];
            $cc['emails'][0]['email'] = $request->get('email');
            $commonGroundService->updateResource($cc);

            $variables['organization'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'organizations', 'id' => $id]);
            $variables['cc'] = $commonGroundService->getResource($variables['organization']['contact']);
        }

        return $variables;
    }

    /**
     * @Route("/documentation")
     * @Template
     */
    public function documentationAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }
}
