<?php

namespace App\Service;

use App\Entity\CreateClient;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Config\Definition\Exception\Exception;

class ClientService
{
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    /**
     * This function creates an wrc organization object
     *
     * @param string $name name of the organization
     * @return array the created organization object
     */
    public function createOrganization(string $name)
    {
        $validChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $random = substr(str_shuffle(str_repeat($validChars, ceil(3 / strlen($validChars)))), 1, 6);
        $organization = [];
        $organization['name'] = $name;
        $organization['rsin'] = $random;
        $organization['chamberOfComerce'] = $random;
        return $this->commonGroundService->createResource($organization,['component' => 'wrc', 'type' => 'organizations']);
    }

    /**
     * This function creates an wrc application object
     *
     * @param string $name name of the application
     * @param string $uri domain of the application
     * @param array $organization wrc organization object linked to the application
     * @return array the created application object
     */
    public function createWrcApplication(string $name, string $uri, array $organization)
    {
        $application = [];
        $application['name'] = $name;
        $application['description'] = $name;
        $application['domain'] = $uri;
        $application['organization'] = '/organizations/'.$organization['id'];
        return $this->commonGroundService->createResource($application, ['component' => 'wrc', 'type' => 'applications']);
    }

    /**
     * This function creates an wac application object
     *
     * @param string $name name of the application
     * @param string $uri authorizationUrl of the application
     * @param array $organization organization linked to the application
     * @param array $wrcApplication contact of the application
     * @return array the created application object
     */
    public function createWacApplication(string $name, string $uri, array $organization, array $wrcApplication)
    {
        $application = [];
        $application['name'] = $name;
        $application['contact'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'applications', 'id' => $wrcApplication['id']]);
        $application['organization'] = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization['id']]);
        $application['authorizationUrl'] = $uri;
        return $this->commonGroundService->createResource($application, ['component' => 'wac', 'type' => 'applications']);
    }
}
