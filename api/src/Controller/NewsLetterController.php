<?php

namespace App\Controller;

use function GuzzleHttp\Promise\all;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NewsLetterController.
 *
 * @Route("/newsletter")
 */
class NewsLetterController extends AbstractController
{
    /**
     * This function shows all available processes.
     *
     * @Route("/")
     * @Template
     */
    public function indexAction()
    {
    }
}
