<?php

// src/Controller/DefaultController.php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\ApplicationService;
//use App\Service\RequestService;
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
 * @Route("/oauth")
 */
class OauthController extends AbstractController
{

    /**
     * @Route("/authorize")
     * @Template
     */
    public function authorizeAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if ($request->isMethod('POST') && $request->get('grantAccess')) {

            $application = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $request->get('application')]);

            if ($request->get('grantAccess') == 'true'){

                $person = $commonGroundService->getResource($this->getUser()->getPerson());
                $person = $commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);
                $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['person' => $person])['hydra:member'];
                if (count($users) > 0) {
                    $user = $users[0];
                }
                $state = $request->get('state');
                $authorization = [];
                $authorization['application'] = '/applications/'.$application['id'];
                $authorization['scopes'] = $request->get('scopes');
                $authorization['goal'] = ' ';
                $authorization['userUrl'] = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

                $authorization = $commonGroundService->createResource($authorization, ['component' => 'wac', 'type' => 'authorizations']);

                return $this->redirect($application['authorizationUrl']."?code={$authorization['id']}&state={$state}");

            } else {
                return $this->redirect($application['authorizationUrl'].'?errorMessage=Authorization+denied+by+user');
            }

        }

        if (!$request->query->get('client_id')) {
            $this->addFlash('error', 'no client id provided');
        } else {
            try {
                $variables['application'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $request->query->get('client_id')]);
            } catch (\Throwable $e) {
                $this->addFlash('error', 'invalid client id');
            }
        }

        if (!$request->query->get('response_type') || $request->query->get('response_type') !== 'code'){
            return $this->redirect($variables['application']['authorizationUrl'].'?errorMessage=invalid+response+type');
        }

        if (!$request->query->get('scopes')){
            return $this->redirect($variables['application']['authorizationUrl'].'?errorMessage=no+scopes+provided');
        } else {
            $variables['scopes'] = explode(' ', $request->query->get('scopes'));
        }


        if ($request->query->get('state')){
            $variables['state'] = $request->query->get('state');
        }

        $session->set('backUrl', $request->getUri());

        $variables['wrcApplication'] = $commonGroundService->getResource($variables['application']['contact']);


        return $variables;
    }
}
