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
     * @Route("/")
     * @Template
     */
    public function indexAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/authorize")
     * @Template
     */
    public function oauthAction(Session $session, Request $request, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        $variables = [];

        if (!$request->query->get('client_id')) {
            $this->addFlash('error', 'no client id provided');
        } else {
            try {
                $variables['application'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $request->query->get('client_id')]);
            } catch (\Throwable $e) {
                $this->addFlash('error', 'invalid client id');
            }
        }

        if (!$request->query->get('response_type') || $request->query->get('response_type') !== 'code'){
            $this->addFlash('error', 'invalid response type');
        }

        if (!$request->query->get('scopes')){
            $this->addFlash('error', 'no scopes provided');
        } else {
            $variables['scopes'] = explode(' ', $request->query->get('scopes'));
        }


        if (!$request->query->get('state')){
            $variables['state'] = $request->query->get('state');
        }

        $session->set('backUrl', $request->getUri());

        if ($request->isMethod('POST') && $request->get('grantAccess')) {

            if ($request->get('grantAccess') == 'true'){
                //@todo create token & send back to authorization url defined in application
            } else {
                //@todo send message back that access was denied.
            }

        }


        return $variables;
    }
}
