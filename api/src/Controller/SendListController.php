<?php

namespace App\Controller;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The SendListController ...
 *
 * Class SendListController
 *
 * @Route("/sendlist")
 */
class SendListController extends AbstractController
{
    private $commonGroundService;


    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
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

        if (!$request->get('client_id')) {
            $this->addFlash('error', 'no client id provided');
        } else {
            try {
                $variables['application'] = $$this->commonGroundService->getResource(['component' => 'wac', 'type' => 'applications', 'id' => $request->get('client_id')]);
            } catch (\Throwable $e) {
                $this->addFlash('error', 'invalid client id');
            }
        }

        /*
         *  Lets transport our variables to twig
         */

        $clientId = $request->get('client_id');
        $variables['clientId'] = $clientId;

        $sendListIds = $request->get('send_lists');
        $variables['sendListIds'] = $sendListIds;

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
            // Set redirectUrl
            if (strpos($request->get('redirect_uri'), 'localhost')) {
                $redirectUrl = $request->get('redirect_uri');
            } elseif ($request->get('redirect_uri') == $variables['application']['authorizationUrl']) {
                $redirectUrl = $variables['application']['authorizationUrl'];
            }

            if ($request->get('grantAccess') == 'true') {
                // Get user
                $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $this->getUser()->getUsername()])['hydra:member'];
                if (count($users) > 0) {
                    $user = $users[0];
                    if (isset($user['person'])) {
                        // Get the SendLists from the form
                        $sendListIds = $request->get('sendLists');
                        $sendLists = [];
                        foreach ($sendListIds as $sendListId) {
                            array_push($sendLists, $this->commonGroundService->getResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendListId]));
                        }

                        // Check if this user already has a subscriber object in BS
                        $subscribers = $this->commonGroundService->getResourceList(['component' => 'bs', 'type' => 'subscribers'], ['person' => $user['person']])['hydra:member'];
                        $subscriber['sendLists'] = [];
                        if (count($subscribers) > 0) {
                            // Set subscriber to the existing subscriber to update later
                            $subscriber = $subscribers[0];

                            // Get sendLists of this subscriber except the ones we are already adding later
                            $subscriberSendLists = [];
                            foreach ($subscriber['sendLists'] as $subscriberSendList) {
                                if (!in_array($subscriberSendList['id'], $sendListIds)) {
                                    array_push($subscriberSendLists, '/send_lists/'.$subscriberSendList['id']);
                                }
                            }

                            // Set subscriber sendLists
                            $subscriber['sendLists'] = $subscriberSendLists;
                        } else {
                            // Set person to create a new subscriber
                            $subscriber['person'] = $user['person'];
                        }

                        // Add sendLists to the subscriber
                        foreach ($sendLists as $sendList) {
                            array_push($subscriber['sendLists'], '/send_lists/'.$sendList['id']);
                        }

                        // Update or create a subscriber in BS
                        $this->commonGroundService->saveResource($subscriber, ['component' => 'bs', 'type' => 'subscribers']);
                    } else {
                        return $this->redirect($redirectUrl.'&errorMessage=User+has+no+contact');
                    }
                }

                return $this->redirect($redirectUrl);
            } else {
                return $this->redirect($redirectUrl.'&errorMessage=Authorization+denied+by+user');
            }
        }

        if (!isset($variables['sendListIds'])) {
            return $this->redirect($redirectUrl.'&errorMessage=no+send_lists+provided');
        } else {
            $variables['sendListIds'] = explode(' ', $request->query->get('send_lists'));

            $sendLists = [];
            foreach ($variables['sendListIds'] as $sendListId) {
                // TD: check if this user isn't already a subscriber in this sendList and if so, dont put this sendList in $variables to be shown on screen or used in the post
                // TD: redirect or do something if the user is already a subscriber in all of these sendLists!
                // (The post already ensures that a user is not put in a mailing list if he is already subscribed to it)
                array_push($sendLists, $this->commonGroundService->getResource(['component' => 'bs', 'type' => 'send_lists', 'id' => $sendListId]));
            }
            $variables['sendLists'] = $sendLists;
        }

        $session->set('backUrl', $request->getUri());

        $variables['wrcApplication'] = $this->commonGroundService->getResource($variables['application']['contact']);

        return $variables;
    }
}
