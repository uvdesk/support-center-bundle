<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;

class Announcement extends AbstractController
{
    private $userService;
    private $entityManager;

    public function __construct(UserService $userService, EntityManagerInterface $entityManager)
    {
        $this->userService = $userService;
        $this->entityManager = $entityManager;
    }

    public function listAnnouncement(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/Announcement/listAnnouncement.html.twig');
    }

    public function listAnnouncementXHR(Request $request, ContainerInterface $container)
    {
        $json = array();
        $repository = $this->entityManager->getRepository(SupportEntities\Announcement::class);
        $json =  $repository->getAllAnnouncements($request->query, $container);
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function updateAnnouncement(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        if ($request->attributes->get('announcementId')) {
            $announcement = $this->entityManager->getRepository(SupportEntities\Announcement::class)
                ->findOneBy([
                    'id' => $request->attributes->get('announcementId')
                ]);
            $announcement->setCreatedAt(new \DateTime('now'));

            if (! $announcement)
                $this->noResultFound();
        } else {
            $announcement = new SupportEntities\Announcement();
            $announcement->setCreatedAt(new \DateTime('now'));
        }

        if ($request->getMethod() == "POST") {
            $request = $request->request->get('announcement_form');
            $group = $this->entityManager->getRepository(CoreEntities\SupportGroup::class)->find($request['group']);

            $announcement->setTitle($request['title']);
            $announcement->setPromoText($request['promotext']);
            $announcement->setPromotag($request['promotag']);
            $announcement->setTagColor($request['tagColor']);
            $announcement->setIsActive($request['status']);
            $announcement->setGroup($group);

            $this->entityManager->persist($announcement);
            $this->entityManager->flush();

            $this->addFlash('success', 'Success! Announcement data saved successfully.');

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_marketing_announcement'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/Announcement/announcementForm.html.twig', [
            'announcement' => $announcement,
            'errors'       => ''
        ]);
    }

    public function removeAnnouncementXHR(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $knowledgebaseAnnouncement = $this->entityManager->getRepository(SupportEntities\Announcement::class)
            ->findOneBy([
                'id' => $request->attributes->get('announcementId')
            ]);

        if ($knowledgebaseAnnouncement) {
            $this->entityManager->remove($knowledgebaseAnnouncement);
            $this->entityManager->flush();

            $json = [
                'alertClass'   => 'success',
                'alertMessage' => 'Announcement deleted successfully!',
            ];
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

    public function marketingAnnouncementCustomerListXHR(Request $request, ContainerInterface $container)
    {
        $json = array();
        $customer = $this->userService->getCurrentUser();
        $repository = $this->entityManager->getRepository(SupportEntities\Announcement::class);
        $json = $repository->getAllAnnouncementForCustomer($request->query, $container, $customer);
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
