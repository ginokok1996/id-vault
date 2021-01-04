<?php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The Procces test handles any calls that have not been picked up by another test, and wel try to handle the slug based against the wrc.
 *
 * Class ProcessController
 *
 * @Route("/docs")
 */
class DocumentationController extends AbstractController
{
    /**
     * @Route("/")
     * @Template
     */
    public function indexAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/glossary")
     * @Template
     */
    public function glossaryAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/plugins")
     * @Template
     */
    public function pluginsAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/scopes")
     * @Template
     */
    public function scopesAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/api")
     * @Template
     */
    public function apiAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/tutorial")
     * @Template
     */
    public function tutorialAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/earn-money")
     * @Template
     */
    public function earnMoneyAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/terms")
     * @Template
     */
    public function termsAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/privacy")
     * @Template
     */
    public function privacyAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/beta")
     * @Template
     */
    public function betaAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/architecture")
     * @Template
     */
    public function architectureAction()
    {
        $variables = [];

        return $variables;
    }

    /**
     * @Route("/support")
     * @Template
     */
    public function supportAction()
    {
        $variables = [];

        return $variables;
    }
}
