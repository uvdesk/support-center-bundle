<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntites;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntites;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;

Class MarketingModule extends AbstractController
{
    private $translator;
    private $userService;
    public $container;
    public $entityManager;

    public function __construct(TranslatorInterface $translator, UserService $userService, ContainerInterface $container, EntityManagerInterface $entityManager)
    {
        $this->translator = $translator;
        $this->userService = $userService;
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

    public function listModules(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_MARKETING_MODULE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/MarketingModule/listModules.html.twig');
    }

    public function listModulesXHR(Request $request, ContainerInterface $container)
    {
        $json = array();
        $repository = $this->getDoctrine()->getRepository(SupportEntites\MarketingModule::class);
        $json =  $repository->getAllAnnouncements($request->query, $container);
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function updateModule(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_MARKETING_MODULE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ($request->attributes->get('id')) {
            $marketingModule = $this->findOneBy([
                                    'id' => $request->attributes->get('id'),
                                ]);

            if ($marketingModule) {
                $marketingModule->setCreatedAt(new \DateTime('now'));
            }
        } else {
            $marketingModule = new SupportEntites\MarketingModule;
            $marketingModule->setCreatedAt(new \DateTime('now'));
        }

        if ($request->getMethod() == "POST") {
            $previousImage = $marketingModule->getImage();
            $image = $request->files->get('marketingModule_image');
            $request = $request->request->get('marketingModule_form');

            $group = $this->entityManager->getRepository(CoreEntites\SupportGroup::class)->find($request['group']);

            $marketingModule->setTitle($request['title']);
            $marketingModule->setDescription($request['description']);
            $marketingModule->setIsActive($request['status']);
            $marketingModule->setGroup($group);
            $marketingModule->setBorderColor($this->hex2rgb($request['borderColor']));
            $marketingModule->setLinkURL($request['linkURL']);
            $marketingModule->setImage($previousImage);

            $this->entityManager->persist($marketingModule);
            $this->entityManager->flush();

            if ($image) {
                $prefix = 'marketing/module' . $marketingModule->getId();
                $uploadManager = $this->container->get('uvdesk.core.file_system.service')->getUploadManager();

                $uploadedFileAttributes = $uploadManager->uploadFile($attachment, $prefix);

                $marketingModule->upload($image, $marketingModule->getId(), $this->container);

                $this->entityManager->persist($marketingModule);
                $this->entityManager->flush();
            }
        }

        return $this->render('@UVDeskSupportCenter/Staff/MarketingModule/marketingModuleForm.html.twig', [
                'marketingModule' => $marketingModule,
                'errors'       => ''
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
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_MARKETING_MODULE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $knowledgebaseAnnouncementId = $request->attributes->get('announcementId');

        $knowledgebaseAnnouncement = $this->getDoctrine()->getRepository(SupportEntites\Announcement::class)
            ->findOneBy([
                'id' => $request->attributes->get('announcementId')
            ]);

        if ($knowledgebaseAnnouncement) {
            $entityManager->remove($knowledgebaseAnnouncement);
            $entityManager->flush();

            $json = [
                'alertClass'   => 'success',
                'alertMessage' => 'Announcement deleted successfully!',
            ];
            $responseCode = 200;
        } else {
            $json = [
                'alertClass'   => 'warning',
                'alertMessage' => 'Announcement not found!',
            ];
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
