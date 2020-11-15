<?php

// src/Controller/DefaultController.php

namespace App\Controller;

use App\Service\ScopeService;
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
    public function authorizeAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, ScopeService $scopeService, string $slug = 'home')
    {
        $variables = [];

        /*
         *  First we NEED to determine an application by public client_id (unsafe)
         */

        if (!$request->get('client_id')) {
            $this->addFlash('error', 'no client id provided');
        } else {
            try {
                $variables['application'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $request->get('client_id')]);
            } catch (\Throwable $e) {
                $this->addFlash('error', 'invalid client id');
            }
        }

        /*
         *  Lets transport our variables to twig
         */

        $clientId = $request->get('client_id');
        $variables['clientId'] = $clientId;

        $state = $request->get('state');
        $variables['state'] = $state;

        $scopes = $request->get('scopes');
        $variables['scopes'] = $scopes;

        /*
         *  Then we NEED to get a redirect url, for this we have several options
         */

        $redirectUrl = $request->get('redirect_uri', false);

        // Als localhost dan prima -> dit us wel unsafe want ondersteund ook subdomein of path localhost
        if ($redirectUrl && strpos($redirectUrl, 'localhost')) {
            // $redirectUrl is al oke dus we hoeven niks te doen
        } elseif ($redirectUrl && str_replace('http://', 'https://', $redirectUrl) != str_replace('http://', 'https://', $variables['application']['authorizationUrl'])) {
            // $redirectUrl
        } else {
            $redirectUrl = $variables['application']['authorizationUrl'];
        }

        $variables['redirectUrl'] = $redirectUrl;

        /*
         * Lastly lets handle the actual post request
         */

        if ($request->isMethod('POST') && $request->get('grantAccess')) {
            if (strpos($request->get('redirect_uri'), 'localhost')) {
                $redirectUrl = $request->get('redirect_uri');
            } elseif ($request->get('redirect_uri') == $variables['application']['authorizationUrl']) {
                $redirectUrl = $variables['application']['authorizationUrl'];
            }

            if ($request->get('grantAccess') == 'true') {
                $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
                if (count($users) > 0) {
                    $user = $users[0];
                }
                $state = $request->get('state');
                $authorization = [];
                $authorization['application'] = '/applications/'.$variables['application']['id'];
                $authorization['scopes'] = $request->get('scopes');
                $authorization['goal'] = ' ';
                $authorization['userUrl'] = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

                $authorization = $commonGroundService->createResource($authorization, ['component' => 'wac', 'type' => 'authorizations']);

                return $this->redirect($redirectUrl."?code={$authorization['id']}&state={$state}");
            } else {
                return $this->redirect($redirectUrl.'?errorMessage=Authorization+denied+by+user');
            }
        }

        if ($this->getUser()) {
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            $user = $commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $users[0]['id']]);

            $authorizations = $commonGroundService->getResourceList(['component' => 'wac', 'type' => 'authorizations'], ['userUrl' => $user, 'application' => '/applications/'.$variables['application']['id']])['hydra:member'];
            if (count($authorizations) > 0) {
                $authorization = $authorizations['0'];

                return $this->redirect($redirectUrl."?code={$authorization['id']}&state={$variables['state']}");
            }
        }

        if (!$request->query->get('response_type') || $request->query->get('response_type') !== 'code') {
            return $this->redirect($redirectUrl.'?errorMessage=invalid+response+type');
        }

        if (!$request->query->get('scopes')) {
            return $this->redirect($redirectUrl.'?errorMessage=no+scopes+provided');
        } else {
            $variables['scopes'] = explode(' ', $request->query->get('scopes'));
        }
        if ($this->getUser()) {
            $users = $commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
            if (count($users) > 0) {
                $user = $users[0];
            }
            $variables['deficiencies'] = $scopeService->checkScopes($variables['scopes'], $user);
            if ($variables['deficiencies']) {
                $session->set('backUrl', $request->getRequestUri());
//                var_dump($session->get('backUrl'));
            }
        }
        $session->set('backUrl', $request->getUri());

        $variables['wrcApplication'] = $commonGroundService->getResource($variables['application']['contact']);

        return $variables;
    }
}
