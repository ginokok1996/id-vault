<?php

namespace App\Controller;

use App\Service\OauthService;
use App\Service\ScopeService;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    private $commonGroundService;
    private $scopeService;
    private $oauthService;

    public function __construct(CommonGroundService $commonGroundService, ScopeService $scopeService, OauthService $oauthService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->scopeService = $scopeService;
        $this->oauthService = $oauthService;
    }

    /**
     * @Route("/authorize")
     * @Template
     */
    public function authorizeAction(Session $session, Request $request)
    {
        $variables = [];

        /*
         *  First we NEED to determine an application by public client_id (unsafe)
         */
        try {
            $variables['application'] = $this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $request->get('client_id')]);
        } catch (\Throwable $e) {
            $this->addFlash('error', 'invalid client id');
        }

        $variables['clientId'] = $request->get('client_id');
        $variables['state'] = $request->get('state');

        //get the redirect Url
        $redirectUrl = $this->oauthService->createRedirectUrl($request->get('redirect_uri'), $variables['application']);
        $variables['redirectUrl'] = $redirectUrl;

        if (!$request->query->get('scopes') && !$request->get('scopes')) {
            return $this->redirect($redirectUrl.'?errorMessage=no+scopes+provided');
        } else {
            if ($request->query->get('scopes')) {
                $variables['scopes'] = explode(' ', $request->query->get('scopes'));
            } else {
                $variables['scopes'] = $request->get('scopes');
            }
        }

        $session->set('backUrl', $request->getRequestUri());

        $variables['wrcApplication'] = $this->commonGroundService->getResource($variables['application']['contact']);

        if ($this->getUser()) {
            $user = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'][0];
            $userUrl = $this->commonGroundService->cleanUrl(['component' => 'uc', 'type' => 'users', 'id' => $user['id']]);

            $variables['deficiencies'] = $this->scopeService->checkScopes($variables['scopes'], $user);

            $result = $this->oauthService->compareExistingScopes($userUrl, $variables['application'], $variables['scopes']);

            if ($result !== false) {
                if ($result['authorizationNeeded']) {
                    $variables['authorization'] = $result['id'];
                    $variables['scopes'] = $result['newScopes'];
                } else {
                    return $this->redirect($redirectUrl."?code={$result['id']}&state={$variables['state']}");
                }
            }
        }

        /*
         * Lastly lets handle the actual post request
         */

        if ($request->isMethod('POST') && $request->get('grantAccess')) {
            $redirectUrl = $request->get('redirect_uri');

            if ($request->get('grantAccess') == 'true' && $request->get('authorization')) {
                $authorization = $this->oauthService->updateAuthorization($request->get('authorization'), $request->get('scopes'));
            } elseif ($request->get('grantAccess') == 'true') {
                $authorization = $this->oauthService->createAuthorization($variables['application'], $user, $request->get('scopes'));
            } else {
                return $this->redirect($redirectUrl.'?errorMessage=Authorization+denied+by+user');
            }

            if ($request->get('needScopes')) {
                $session->set('backUrl', $redirectUrl."?code={$authorization['id']}&state={$variables['state']}");

                return $this->redirect($this->generateUrl('app_dashboard_claimyourdata').'?authorization='.$authorization['id']);
            }

            return $this->redirect($redirectUrl."?code={$authorization['id']}&state={$variables['state']}");
        }

        if (!$request->query->get('response_type') || $request->query->get('response_type') !== 'code') {
            return $this->redirect($redirectUrl.'?errorMessage=invalid+response+type');
        }

        return $variables;
    }
}
