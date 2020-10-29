<?php

// src/Controller/WacController.php

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
 * The WacController test handles any calls that are about the wac component.
 *
 * Class WacController
 *
 * @Route("/wac")
 */
class WacController extends AbstractController
{
    /**
     * @Route("/claims/{id}")
     * @Template
     */
    public function claimAction(Session $session, Request $request, $id = null, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        if (empty($this->getUser())) {
            $this->addFlash('error', 'This page requires you to be logged in');

            return $this->redirect('/login');
        }
        if (!$id) {
            $this->addFlash('error', 'No id provided');

            return $this->redirect('/dashboard/claims');
        }

        $variables = [];
        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'claims', 'id'=>$id]);

        if ($variables['resource']['person'] != $this->getUser()->getPerson()) {
            $this->addFlash('error', 'You do not have access to this claim');

            return $this->redirect('/dashboard/claims');
        }

        return $variables;
    }

    /**
     * @Route("/contracts/{id}")
     * @Template
     */
    public function contractAction(Session $session, Request $request, $id = null, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
    {
        if (empty($this->getUser())) {
            $this->addFlash('error', 'This page requires you to be logged in');

            return $this->redirect('/login');
        }
        if (!$id) {
            $this->addFlash('error', 'No id provided');

            return $this->redirect('/dashboard/contracts');
        }

        $variables = [];
        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'contracts', 'id'=>$id]);

        if ($variables['resource'] != $this->getUser()->getPerson()) {
            $this->addFlash('error', 'You do not have access to this contract');

            return $this->redirect('/dashboard/contracts');
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

            return $this->redirect('/login');
        }
        if (!$id) {
            $this->addFlash('error', 'No id provided');

            return $this->redirect('/dashboard/dossiers');
        }

        $variables = [];
        $variables['resource'] = $commonGroundService->getResource(['component' => 'wac', 'type' => 'dossiers', 'id'=>$id]);

        if ($variables['resource']['authorization']['person'] != $this->getUser()->getPerson()) {
            $this->addFlash('error', 'You do not have access to this dossier');

            return $this->redirect('/dashboard/dossiers');
        }

        return $variables;
    }

//    /**
//     * @Route("/oauth/{id}")
//     * @Template
//     */
//    public function oauthAction(Session $session, Request $request, $id = null, CommonGroundService $commonGroundService, ApplicationService $applicationService, ParameterBagInterface $params, string $slug = 'home')
//    {
//        $variables = [];
//
//        if (!$id) {
//            $this->addFlash('error', 'no application id provided');
//        }
//
//        try {
//            $variables['application'] = $commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $id]);
//        } catch (\Throwable $e) {
//            $this->addFlash('error', 'invalid application id');
//        }
//
//        $session->set('backUrl', $request->getUri());
//
//        return $variables;
//    }
}
