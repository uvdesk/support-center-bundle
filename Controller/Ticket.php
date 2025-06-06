<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Services as CoreServices;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;
use Webkul\UVDesk\SupportCenterBundle\Form\Ticket as TicketForm;
use Webkul\UVDesk\CoreFrameworkBundle\Workflow\Events as CoreWorkflowEvents;

class Ticket extends AbstractController
{
    private $userService;
    private $eventDispatcher;
    private $translator;
    private $uvdeskService;
    private $ticketService;
    private $recaptchaService;
    private $kernel;
    private $em;

    public function __construct(CoreServices\UserService $userService, CoreServices\UVDeskService $uvdeskService, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator, CoreServices\TicketService $ticketService, CoreServices\ReCaptchaService $recaptchaService, KernelInterface $kernel, EntityManagerInterface $entityManager)
    {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
        $this->uvdeskService = $uvdeskService;
        $this->ticketService = $ticketService;
        $this->recaptchaService = $recaptchaService;
        $this->kernel = $kernel;
        $this->em = $entityManager;
    }

    protected function isWebsiteActive()
    {
        $website = $this->em->getRepository(CoreEntities\Website::class)->findOneByCode('knowledgebase');

        if (! empty($website)) {
            $knowledgebaseWebsite = $this->em->getRepository(SupportEntities\KnowledgebaseWebsite::class)->findOneBy(['website' => $website->getId(), 'status' => 1]);

            if (
                ! empty($knowledgebaseWebsite)
                && true == $knowledgebaseWebsite->getIsActive()
            ) {
                return true;
            }
        }

        $this->noResultFound();
    }

    /**
     * If customer is playing with url and no result is found then what will happen
     * @return
     */
    protected function noResultFound()
    {
        throw new NotFoundHttpException('Not found !');
    }

    public function ticketadd(Request $request, ContainerInterface $container)
    {
        $this->isWebsiteActive();

        $formErrors = $errors = array();
        $website = $this->em->getRepository(CoreEntities\Website::class)->findOneByCode('knowledgebase');
        $websiteConfiguration = $this->uvdeskService->getActiveConfiguration($website->getId());

        if (
            ! $websiteConfiguration
            || ! $websiteConfiguration->getTicketCreateOption()
            || ($websiteConfiguration->getLoginRequiredToCreate()
                && ! $this->getUser())
        ) {
            return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
        }

        $post = $request->request->all();
        $recaptchaDetails = $this->recaptchaService->getRecaptchaDetails();

        if ($request->getMethod() == "POST") {
            if (
                $recaptchaDetails
                && $recaptchaDetails->getIsActive() == true
                && $this->recaptchaService->getReCaptchaResponse($request->request->get('g-recaptcha-response'))
            ) {
                $this->addFlash('warning', $this->translator->trans("Warning ! Please select correct CAPTCHA !"));
            } else {
                if ($_POST) {
                    $error = false;

                    try {
                        $customFieldsService = null;

                        if ($this->userService->isFileExists('apps/uvdesk/custom-fields')) {
                            $customFieldsService = $this->get('uvdesk_package_custom_fields.service');
                        } else if ($this->userService->isFileExists('apps/uvdesk/form-component')) {
                            $customFieldsService = $this->get('uvdesk_package_form_component.service');
                        }

                        if (! empty($customFieldsService)) {
                            if (
                                $request->files->get('customFields')
                                && ! $customFieldsService->validateAttachmentsSize($request->files->get('customFields'))
                            ) {
                                $error = true;

                                $this->addFlash(
                                    'warning',
                                    $this->translator->trans("Warning ! Files size can not exceed %size% MB", [
                                        '%size%' => $this->getParameter('max_upload_size')
                                    ])
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        // @TODO: Log execption message
                    }

                    $ticket = new CoreEntities\Ticket();
                    $loggedUser = $this->get('security.token_storage')->getToken()->getUser();

                    if (
                        ! empty($loggedUser)
                        && $loggedUser != 'anon.'
                    ) {
                        $form = $this->createForm(TicketForm::class, $ticket, [
                            'container'      => $container,
                            'entity_manager' => $this->em,
                        ]);
                        $email = $loggedUser->getEmail();

                        try {
                            $name = $loggedUser->getFirstName() . ' ' . $loggedUser->getLastName();
                        } catch (\Exception $e) {
                            $name = explode(' ', strstr($email, '@', true));
                        }
                    } else {
                        $form = $this->createForm(TicketForm::class, $ticket, [
                            'container'      => $container,
                            'entity_manager' => $this->em,
                        ]);

                        $email = $request->request->get('from');
                        $name = explode(' ', $request->request->get('name'));
                    }

                    $website = $this->em->getRepository(CoreEntities\Website::class)->findOneByCode('knowledgebase');

                    if (
                        ! empty($email)
                        && $this->ticketService->isEmailBlocked($email, $website)
                    ) {
                        $request->getSession()->getFlashBag()->set('warning', $this->translator->trans('Warning ! Cannot create ticket, given email is blocked by admin.'));

                        return $this->redirect($this->generateUrl('helpdesk_customer_create_ticket'));
                    }

                    if ($request->request->all())
                        $form->submit($request->request->all());

                    if ($form->isValid() && !count($formErrors) && !$error) {
                        $data = array(
                            'from'      => $email,
                            'subject'   => $request->request->get('subject'),
                            'reply'     => str_replace(['&lt;script&gt;', '&lt;/script&gt;'], '', htmlspecialchars($request->request->get('reply'))),
                            'firstName' => $name[0],
                            'lastName'  => isset($name[1]) ? $name[1] : '',
                            'role'      => 4,
                            'active'    => true
                        );

                        $data['type'] = $this->em->getRepository(CoreEntities\TicketType::class)->find($request->request->get('type'));

                        if (! is_object($data['customer'] = $this->container->get('security.token_storage')->getToken()->getUser()) == "anon.") {
                            $supportRole = $this->em->getRepository(CoreEntities\SupportRole::class)->findOneByCode("ROLE_CUSTOMER");

                            $customerEmail = $params['email'] = $request->request->get('from');
                            $customer = $this->em->getRepository(CoreEntities\User::class)->findOneBy(array('email' => $customerEmail));
                            $params['flag'] = (!$customer) ? 1 : 0;

                            $data['firstName'] = current($nameDetails = explode(' ', $request->request->get('name')));
                            $data['fullname'] = $request->request->get('name');
                            $data['lastName'] = ($data['firstName'] != end($nameDetails)) ? end($nameDetails) : " ";
                            $data['from'] = $customerEmail;
                            $data['role'] = 4;
                            $data['customer'] = $this->userService->createUserInstance($customerEmail, $data['fullname'], $supportRole, $extras = ["active" => true]);
                        } else {
                            $userDetail = $this->em->getRepository(CoreEntities\User::class)->find($data['customer']->getId());
                            $data['email'] = $customerEmail = $data['customer']->getEmail();
                            $nameCollection = [$userDetail->getFirstName(), $userDetail->getLastName()];
                            $name = implode(' ', $nameCollection);
                            $data['fullname'] = $name;
                        }

                        $data['user'] = $data['customer'];
                        $data['subject'] = $request->request->get('subject');
                        $data['source'] = 'website';
                        $data['threadType'] = 'create';
                        $data['message'] = $data['reply'];
                        $data['createdBy'] = 'customer';
                        $data['attachments'] = $request->files->get('attachments');

                        if (! empty($request->server->get("HTTP_CF_CONNECTING_IP"))) {
                            $data['ipAddress'] = $request->server->get("HTTP_CF_CONNECTING_IP");
                            if (!empty($request->server->get("HTTP_CF_IPCOUNTRY"))) {
                                $data['ipAddress'] .= '(' . $request->server->get("HTTP_CF_IPCOUNTRY") . ')';
                            }
                        }

                        $thread = $this->ticketService->createTicketBase($data);

                        if (! empty($thread)) {
                            $ticket = $thread->getTicket();
                            if (
                                $request->request->get('customFields')
                                || $request->files->get('customFields')
                            ) {
                                $this->ticketService->addTicketCustomFields($thread, $request->request->get('customFields'), $request->files->get('customFields'));
                            }
                            $this->addFlash('success', $this->translator->trans('Success ! Ticket has been created successfully.'));
                        } else {
                            $this->addFlash('warning', $this->translator->trans('Warning ! Can not create ticket, invalid details.'));
                        }

                        // Trigger ticket created event
                        $event = new CoreWorkflowEvents\Ticket\Create();
                        $event
                            ->setTicket($thread->getTicket());

                        $this->eventDispatcher->dispatch($event, 'uvdesk.automation.workflow.execute');

                        return null != $this->getUser() ? $this->redirect($this->generateUrl('helpdesk_customer_ticket_collection')) : $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
                    } else {
                        $errors = $this->getFormErrors($form);
                        $errors = array_merge($errors, $formErrors);
                    }
                } else {
                    $this->addFlash(
                        'warning',
                        $this->translator->trans("Warning ! Post size can not exceed 25MB")
                    );
                }

                if (
                    isset($errors)
                    && count($errors)
                ) {
                    $this->addFlash('warning', key($errors) . ': ' . reset($errors));
                }
            }
        }

        $breadcrumbs = [
            [
                'label' => $this->translator->trans('Support Center'),
                'url'   => $this->generateUrl('helpdesk_knowledgebase')
            ],
            [
                'label' => $this->translator->trans("Create Ticket Request"),
                'url'   => '#'
            ],
        ];

        return $this->render(
            '@UVDeskSupportCenter/Knowledgebase/ticket.html.twig',
            array(
                'formErrors'         => $formErrors,
                'errors'             => json_encode($errors),
                'customFieldsValues' => $request->request->get('customFields'),
                'breadcrumbs'        => $breadcrumbs,
                'post'               => $post
            )
        );
    }

    public function saveReply(int $id, Request $request)
    {
        $this->isWebsiteActive();
        $data = $request->request->all();
        $ticket = $this->em->getRepository(CoreEntities\Ticket::class)->find($id);
        $user = $this->userService->getSessionUser();

        // process only if access for the resource.
        if (empty($ticket) || ((!empty($user)) && $user->getId() != $ticket->getCustomer()->getId())) {
            if (! $this->isCollaborator($ticket, $user)) {
                throw new \Exception('Access Denied', 403);
            }
        }

        if ($_POST) {
            if (str_replace(' ', '', str_replace('&nbsp;', '', trim(strip_tags($data['message'], '<img>')))) != "") {
                if (!$ticket)
                    $this->noResultFound();
                $data['ticket'] = $ticket;
                $data['user'] = $this->userService->getCurrentUser();

                // Checking if reply is from collaborator end
                $isTicketCollaborator = $ticket->getCollaborators() ? $ticket->getCollaborators()->toArray() : [];
                $isCollaborator = false;
                foreach ($isTicketCollaborator as $value) {
                    if ($value->getId() == $data['user']->getId()) {
                        $isCollaborator = true;
                    }
                }

                // @TODO: Refactor -> Why are we filtering only these two characters?
                $data['message'] = str_replace(['&lt;script&gt;', '&lt;/script&gt;'], '', htmlspecialchars($data['message']));

                $userDetail = $this->userService->getCustomerPartialDetailById($data['user']->getId());
                $data['fullname'] = $userDetail['name'];
                $data['source'] = 'website';
                $data['createdBy'] = $isCollaborator ? 'collaborator' : 'customer';
                $data['attachments'] = $request->files->get('attachments');
                $thread = $this->ticketService->createThread($ticket, $data);

                $status = $this->em->getRepository(CoreEntities\TicketStatus::class)->findOneByCode($data['status']);

                if ($status) {
                    $ticket
                        ->setStatus($status);

                    $this->em->persist($ticket);
                }

                $ticket->setCustomerRepliedAt(new \DateTime('now'));
                $this->em->persist($ticket);

                $this->em->flush();

                if ($thread->getcreatedBy() == 'customer') {
                    $event = new CoreWorkflowEvents\Ticket\CustomerReply();
                    $event
                        ->setTicket($ticket)
                        ->setThread($thread)
                    ;
                } else {
                    $event = new CoreWorkflowEvents\Ticket\CollaboratorReply();
                    $event
                        ->setTicket($ticket)
                        ->setThread($thread)
                    ;
                }

                $this->eventDispatcher->dispatch($event, 'uvdesk.automation.workflow.execute');
                $this->eventDispatcher->dispatch($event, 'uvdesk.automation.report_app.workflow.execute');

                $this->addFlash('success', $this->translator->trans('Success ! Reply added successfully.'));
            } else {
                $this->addFlash('warning', $this->translator->trans('Warning ! Reply field can not be blank.'));
            }
        } else {
            $this->addFlash('warning', $this->translator->trans('Warning ! Post size can not exceed 25MB'));
        }

        return $this->redirect($this->generateUrl('helpdesk_customer_ticket', array(
            'id' => $ticket->getId()
        )));
    }

    public function tickets(Request $request)
    {
        $this->isWebsiteActive();
        // List Announcement if any
        $announcements =  $this->em->getRepository(SupportEntities\Announcement::class)->findBy(['isActive' => 1]);

        $groupAnnouncement = [];
        foreach ($announcements as $announcement) {
            $announcementGroupId = $announcement->getGroup();
            $isTicketExist =  $this->em->getRepository(CoreEntities\Ticket::class)->findBy(['supportGroup' => $announcementGroupId, 'customer' => $this->userService->getCurrentUser()]);

            if (! empty($isTicketExist)) {
                $groupAnnouncement[] = $announcement;
            }
        }

        return $this->render(
            '@UVDeskSupportCenter/Knowledgebase/ticketList.html.twig',
            array(
                'searchDisable'     => true,
                'groupAnnouncement' => $groupAnnouncement
            )
        );
    }

    /**
     * ticketListXhrAction "Filter and sort ticket collection on ajax request"
     * @param Object $request "HTTP Request object"
     * @return JSON "JSON response"
     */
    public function ticketListXhr(Request $request, ContainerInterface $container)
    {
        $this->isWebsiteActive();
        $json = array();

        if ($request->isXmlHttpRequest()) {
            $repository = $this->em->getRepository(CoreEntities\Ticket::class);
            $json = $repository->getAllCustomerTickets($request->query, $container);
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * threadListXhrAction "Filter and sort user collection on ajx request"
     * @param Object $request "HTTP Request object"
     * @return JSON "JSON response"
     */
    public function threadListXhr(Request $request, ContainerInterface $container)
    {
        $this->isWebsiteActive();
        $json = array();

        if ($request->isXmlHttpRequest()) {
            $repository = $this->em->getRepository(CoreEntities\Thread::class);
            $json = $repository->getAllCustomerThreads($request->attributes->get('id'), $request->query, $container);
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function ticketView($id, Request $request)
    {
        $this->isWebsiteActive();

        $user = $this->userService->getSessionUser();
        $ticket = $this->em->getRepository(CoreEntities\Ticket::class)->findOneBy(['id' => $id]);
        $isConfirmColl = false;

        if ($ticket->getIsTrashed()) {
            return $this->redirect($this->generateUrl('helpdesk_customer_ticket_collection'));
        }

        if ($ticket == null && empty($ticket)) {
            throw new NotFoundHttpException('Page Not Found!');
        }

        if (
            ! empty($ticket)
            && ((!empty($user))
                && $user->getId() != $ticket->getCustomer()->getId())
        ) {
            if ($this->isCollaborator($ticket, $user)) {
                $isConfirmColl = true;
            }

            if ($isConfirmColl != true) {
                throw new \Exception('Access Denied', 403);
            }
        }

        if (
            ! empty($user)
            && $user->getId() == $ticket->getCustomer()->getId()
        ) {
            $ticket->setIsCustomerViewed(1);

            $this->em->persist($ticket);
            $this->em->flush();
        }

        $checkTicket = $this->em->getRepository(CoreEntities\Ticket::class)->isTicketCollaborator($ticket, $user->getEmail());

        $twigResponse = [
            'ticket'                => $ticket,
            'searchDisable'         => true,
            'initialThread'         => $this->ticketService->getTicketInitialThreadDetails($ticket),
            'localizedCreateAtTime' => $this->userService->getLocalizedFormattedTime($ticket->getCreatedAt(), $user),
            'isCollaborator'        => $checkTicket,
        ];

        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketView.html.twig', $twigResponse);
    }

    // Check if user is collaborator for the ticket
    public function isCollaborator($ticket, $user)
    {
        $isCollaborator = false;
        if (! empty($ticket->getCollaborators()->toArray())) {
            foreach ($ticket->getCollaborators()->toArray() as $collaborator) {
                if ($collaborator->getId() == $user->getId()) {
                    $isCollaborator = true;
                }
            }
        }

        return $isCollaborator;
    }

    // Ticket rating
    public function rateTicket(Request $request)
    {
        $this->isWebsiteActive();
        $json = array();
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $count = intval($data['rating']);

        if ($count > 0 || $count < 6) {
            $customer = $this->userService->getCurrentUser();
            $ticket = $this->em->getRepository(CoreEntities\Ticket::class)->findOneBy([
                'id'       => $id,
                'customer' => $customer
            ]);

            if (empty($ticket)) {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->translator->trans('Warning ! Invalid rating.');

                $response = new Response(json_encode($json));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }

            $rating = $this->em->getRepository(CoreEntities\TicketRating::class)->findOneBy(array('ticket' => $id, 'customer' => $customer->getId()));

            if ($rating) {
                $rating->setcreatedAt(new \DateTime);
                $rating->setStars($count);
                $this->em->persist($rating);
                $this->em->flush();
            } else {
                $rating = new CoreEntities\TicketRating();
                $rating->setStars($count);
                $rating->setCustomer($customer);
                $rating->setTicket($ticket);
                $this->em->persist($rating);
                $this->em->flush();
            }

            $json['alertClass'] = 'success';
            $json['alertMessage'] = $this->translator->trans('Success ! Rating has been successfully added.');
        } else {
            $json['alertClass'] = 'danger';
            $json['alertMessage'] = $this->translator->trans('Warning ! Invalid rating.');
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function downloadAttachmentZip(Request $request)
    {
        $threadId = $request->attributes->get('threadId');
        $attachmentRepository = $this->em->getRepository(CoreEntities\Attachment::class);
        $threadRepository = $this->em->getRepository(CoreEntities\Thread::class);

        // Get thread and attachments
        $thread = $threadRepository->findOneById($threadId);
        $attachments = $attachmentRepository->findByThread($threadId);

        if (!$attachments) {
            $this->noResultFound();
        }

        // Access control
        $ticket = $thread->getTicket();
        $user = $this->userService->getSessionUser();

        if (
            empty($ticket)
            || (!empty($user) && $user->getId() != $ticket->getCustomer()->getId())
        ) {
            if (!$this->isCollaborator($ticket, $user)) {
                throw new \Exception('Access Denied', 403);
            }
        }

        // Create ZIP file with proper path
        $zipName = sys_get_temp_dir() . '/attachments_' . $threadId . '.zip';

        // Make sure directory exists
        $zipDir = dirname($zipName);

        if (! file_exists($zipDir)) {
            mkdir($zipDir, 0777, true);
        }

        // Create new ZIP archive
        $zip = new \ZipArchive();

        if ($zip->open($zipName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("Cannot create zip file");
        }

        // Add files to ZIP
        foreach ($attachments as $attachment) {
            $filePath = substr($attachment->getPath(), 1); // Remove leading slash

            if (file_exists($filePath)) {
                $zip->addFile($filePath, basename($filePath));
            }
        }

        $zip->close();

        // Check if file was created successfully
        if (!file_exists($zipName)) {
            throw new \Exception("ZIP file could not be created");
        }

        // Stream the file to prevent memory issues
        $response = new BinaryFileResponse($zipName);
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="attachments_' . $threadId . '.zip"');
        $response->deleteFileAfterSend(true); // Clean up after sending

        return $response;
    }

    public function downloadAttachment(Request $request)
    {
        $attachmentId = $request->attributes->get('attachmentId');
        $attachment = $this->em->getRepository(CoreEntities\Attachment::class)->findOneById($attachmentId);

        if (empty($attachment)) {
            $this->noResultFound();
        }

        $thread = $attachment->getThread();

        if (! empty($thread)) {
            $ticket = $thread->getTicket();
            $user = $this->userService->getSessionUser();

            // process only if access for the resource.
            if (
                empty($ticket)
                || ((! empty($user)) && $user->getId() != $ticket->getCustomer()->getId())
            ) {
                if (! $this->isCollaborator($ticket, $user)) {
                    throw new \Exception('Access Denied', 403);
                }
            }
        }

        $path = $this->kernel->getProjectDir() . "/public/" . $attachment->getPath();

        return new StreamedResponse(function () use ($path) {
            readfile($path);
        }, 200, [
            'Content-Type'        => $attachment->getContentType(),
            'Content-Disposition' => 'attachment; filename="' . $attachment->getName() . '"',
            'Content-Length'      => $attachment->getSize(),
        ]);
    }

    public function ticketCollaboratorXhr(Request $request)
    {
        $json = array();
        $content = json_decode($request->getContent(), true);
        $ticket = $this->em->getRepository(CoreEntities\Ticket::class)->find($content['ticketId']);
        $user = $this->userService->getSessionUser();

        // process only if access for the resource.
        if (empty($ticket) || ((!empty($user)) && $user->getId() != $ticket->getCustomer()->getId())) {
            if (! $this->isCollaborator($ticket, $user)) {
                throw new \Exception('Access Denied', 403);
            }
        }

        if ($request->getMethod() == "POST") {
            if ($content['email'] == $ticket->getCustomer()->getEmail()) {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->translator->trans('Error ! Can not add customer as a collaborator.');
            } else {
                $data = array(
                    'from'      => $content['email'],
                    'firstName' => ($firstName = ucfirst(current(explode('@', $content['email'])))),
                    'lastName'  => ' ',
                    'role'      => 4,
                );

                $supportRole = $this->em->getRepository(CoreEntities\SupportRole::class)->findOneByCode('ROLE_CUSTOMER');
                $collaborator = $this->userService->createUserInstance($data['from'], $data['firstName'], $supportRole, $extras = ["active" => true]);

                $checkTicket = $this->em->getRepository(CoreEntities\Ticket::class)->isTicketCollaborator($ticket, $content['email']);

                if (! $checkTicket) {
                    $ticket->addCollaborator($collaborator);
                    $this->em->persist($ticket);
                    $this->em->flush();

                    $ticket->lastCollaborator = $collaborator;

                    $collaborator = $this->em->getRepository(CoreEntities\User::class)->find($collaborator->getId());

                    $event = new CoreWorkflowEvents\Ticket\Collaborator();
                    $event
                        ->setTicket($ticket);

                    $this->eventDispatcher->dispatch($event, 'uvdesk.automation.workflow.execute');

                    $json['collaborator'] =  $this->userService->getCustomerPartialDetailById($collaborator->getId());
                    $json['alertClass'] = 'success';
                    $json['alertMessage'] = $this->translator->trans('Success ! Collaborator added successfully.');
                } else {
                    $json['alertClass'] = 'danger';
                    $json['alertMessage'] = $this->translator->trans('Error ! Collaborator is already added.');
                }
            }
        } elseif ($request->getMethod() == "DELETE") {
            $collaborator = $this->em->getRepository(CoreEntities\User::class)->findOneBy(array('id' => $request->attributes->get('id')));

            if ($collaborator) {
                $ticket->removeCollaborator($collaborator);
                $this->em->persist($ticket);
                $this->em->flush();

                $json['alertClass'] = 'success';
                $json['alertMessage'] = $this->translator->trans('Success ! Collaborator removed successfully.');
            } else {
                $json['alertClass'] = 'danger';
                $json['alertMessage'] = $this->translator->trans('Error ! Invalid Collaborator.');
            }
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function ticketIntermediateAccess(Request $request)
    {
        $user = $this->userService->getSessionUser();
        $urid = $request->query->get('urid');
        $currentDateTime = new \DateTime('now', new \DateTimeZone('UTC'));

        $resource = $this->em->getRepository(CoreEntities\PublicResourceAccessLink::class)->findOneBy([
            'uniqueResourceAccessId' => $urid,
        ]);
        $user = $resource->getUser();

        $website = $this->em->getRepository(CoreEntities\Website::class)->findOneByCode('knowledgebase');
        $websiteConfiguration = $this->em->getRepository(SupportEntities\KnowledgebaseWebsite::class)->findOneBy(['website' => $website]);

        if (empty($resource)) {
            $this->addFlash('warning', $this->translator->trans("Invalid link."));

            return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
        }

        if (! empty($user)) {
            if ($resource->getExpiresAt() < $currentDateTime || $websiteConfiguration->getPublicResourceAccessAttemptLimit() <= $resource->getTotalViews() || $resource->getIsExpired()) {
                $resource->setIsExpired(true);

                $this->em->persist($resource);
                $this->em->flush();

                $this->addFlash('warning', $this->translator->trans('Warning! Link expired or access limit reached.'));

                return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
            }

            if ($resource->getResourceType() == CoreEntities\Ticket::class) {
                $ticket = $this->em->getRepository(CoreEntities\Ticket::class)->findOneBy([
                    'id' => (int) $resource->getResourceId(),
                ]);

                $session = $request->getSession();
                $viewedLinks = $session->get('accessed_public_links', []);

                $ticketId = $ticket->getId();

                if (! in_array($ticketId, $viewedLinks)) {
                    $resource->setTotalViews($resource->getTotalViews() + 1);
                    $this->em->persist($resource);
                    $this->em->flush();

                    $viewedLinks[] = $ticketId;
                    $session->set('accessed_public_links', $viewedLinks);
                }

                $token = new UsernamePasswordToken($user, null, 'customer', $user->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                $request->getSession()->migrate();

                return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketViewPublic.html.twig', [
                    'ticket'                => $ticket,
                    'searchDisable'         => true,
                    'initialThread'         => $this->ticketService->getTicketInitialThreadDetails($ticket),
                    'localizedCreateAtTime' => $this->userService->getLocalizedFormattedTime($ticket->getCreatedAt(), $user),
                    'isCollaborator'        => $this->em->getRepository(CoreEntities\Ticket::class)->isTicketCollaborator($ticket, $user->getEmail()),
                ]);
            }
        }

        $this->addFlash('warning', $this->translator->trans("Please login to continue."));

        return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
    }
}
