<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\FileUploadService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UVDeskService;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;

Class MarketingModule extends AbstractController
{
    private $translator;
    private $userService;
    public $container;
    public $entityManager;
    public $fileUplaodService;
    public $uvdeskService;

    public function __construct(TranslatorInterface $translator, UserService $userService, ContainerInterface $container, EntityManagerInterface $entityManager, FileUploadService $fileUplaodService, UVDeskService $uvdeskService)
    {
        $this->translator = $translator;
        $this->userService = $userService;
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->fileUplaodService = $fileUplaodService;
        $this->uvdeskService = $uvdeskService;
    }

    public function listModules(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_MARKETING_MODULE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/MarketingModule/listModules.html.twig');
    }

    public function listModulesXHR(Request $request, ContainerInterface $container)
    {
        $json = array();
        $repository = $this->getDoctrine()->getRepository(SupportEntities\MarketingModule::class);
        $json =  $repository->getAllMarketingModules($request->query, $container);

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function updateModule(Request $request)
    {
        $prefix = 'marketing/module/';

        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_MARKETING_MODULE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ($request->attributes->get('id')) {
            $marketingModule = $this->entityManager->getRepository(SupportEntities\MarketingModule::class)->findOneBy([
                                    'id' => $request->attributes->get('id'),
                                ]);
        } else {
            $marketingModule = new SupportEntities\MarketingModule;
            $marketingModule->setCreatedAt(new \DateTime('now'));
        }

        if ($request->getMethod() == "POST") {
            $uploadImage = $request->files->get('marketingModule_image');
            $request = $request->request->get('marketingModule_form');

            $group = $this->entityManager->getRepository(CoreEntities\SupportGroup::class)->find($request['group']);

            $marketingModule->setTitle($request['title']);
            $marketingModule->setDescription($request['description']);
            $marketingModule->setIsActive($request['status']);
            $marketingModule->setGroup($group);
            $marketingModule->setBorderColor($this->hex2rgb($request['borderColor']));
            $marketingModule->setLinkURL($request['linkURL']);

            try {
                if ($uploadImage) {
                    $uploadedFileAttributes = $this->fileUplaodService->uploadFile($uploadImage, $prefix);

                    if ($uploadedFileAttributes) {
                        $marketingModule->setImage($this->uvdeskService->generateCompleteLocalResourcePathUri($uploadedFileAttributes['path']));
                    }
                }
            } catch (\Exception $e) {
                return $this->render('@UVDeskSupportCenter/Staff/MarketingModule/marketingModuleForm.html.twig', [
                    'marketingModule' => $marketingModule,
                    'errors'          => $e->getMessage()
                ]);
            }

            $this->entityManager->persist($marketingModule);
            $this->entityManager->flush();

            $this->addFlash('success', 'Success! Marketing Module data saved successfully.');

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_marketing_module'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/MarketingModule/marketingModuleForm.html.twig', [
                'marketingModule' => $marketingModule,
                'errors'          => ''
        ]);
    }

    public function hex2rgb($color)
    {
        // Check if the color is in RGB format
        if (preg_match('/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/', $color, $matches)) {
            // If it's already in RGB format, return the color as is
            return $color;
        } else {
            // Assume it's a hexadecimal color and convert it to RGB
            $hexColor = $color;
            $shorthand = (strlen($hexColor) == 4);
            list($r, $g, $b) = $shorthand ? sscanf($hexColor, "#%1s%1s%1s") : sscanf($hexColor, "#%2s%2s%2s");
            return 'rgb('.hexdec($shorthand ? "$r$r" : $r).','.
                        hexdec($shorthand ? "$g$g" : $g).','.
                        hexdec($shorthand ? "$b$b" : $b).')';
        }
    }

    public function removeModuleXHR(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_MARKETING_MODULE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $entityManager = $this->getDoctrine()->getManager();

        $marketingAnnouncement = $this->getDoctrine()->getRepository(SupportEntities\MarketingModule::class)
            ->findOneBy([
                'id' => $request->attributes->get('id')
            ]);

        if ($marketingAnnouncement) {
            $entityManager->remove($marketingAnnouncement);
            $entityManager->flush();

            $json = [
                'alertClass'   => 'success',
                'alertMessage' => 'Marketing Module deleted successfully!',
            ];
            $responseCode = 200;
        } else {
            $json = [
                'alertClass'   => 'warning',
                'alertMessage' => 'Marketing Module not found!',
            ];
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function marketingModuleCustomerListXHR(Request $request, ContainerInterface $container )
    {
        $json = array();
        $customer = $this->userService->getCurrentUser();
        $repository = $this->entityManager->getRepository(SupportEntities\MarketingModule::class);
        $json = $repository->getAllMarketingModulesForCustomer($request->query, $container, $customer);
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
