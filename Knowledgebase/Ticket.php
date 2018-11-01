<?php

namespace Webkul\UVDesk\SupportCenterBundle\Knowledgebase;

use Webkul\UVDesk\CoreBundle\Entity\Thread;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\CoreBundle\Entity\TicketRating;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Webkul\UVDesk\CoreBundle\Entity\Ticket as TicketEntity;
use Webkul\UVDesk\SupportCenterBundle\Form\Ticket as TicketForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Ticket extends Controller
{
    protected function isWebsiteActive()
    {
        $error = false;
        $currentKnowledgebase = $this->getWebsiteDetails();
        
        if(!$currentKnowledgebase)
            $this->noResultFound();
    }
    protected function getWebsiteDetails()
    {
        $knowledgebaseWebsite = $this->getDoctrine()->getManager()->getRepository('UVDeskCoreBundle:Website')->findOneByCode('knowledgebase');
        
        return $knowledgebaseWebsite;
    }

    /**
     * If customer is playing with url and no result is found then what will happen
     * @return
     */
    protected function noResultFound()
    {
        throw new NotFoundHttpException('Not found !');
    }

    public function ticketadd(Request $request)
    {
        $this->isWebsiteActive();
        
        $formErrors = $errors = array();
        $websiteConfiguration = $this->get('uvdesk.service')->getActiveConfiguration($this->getWebsiteDetails()->getId());

        if(!$websiteConfiguration || !$websiteConfiguration->getTicketCreateOption() || ($websiteConfiguration->getLoginRequiredToCreate() && !$this->getUser()))
            return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));

        $em = $this->getDoctrine()->getManager();
        $post = $request->request->all();

        if($request->getMethod() == "POST") {
            if($_POST) {
                $error = false;
                $message = '';
                $ticketType = $em->getRepository('UVDeskCoreBundle:TicketType')->find($request->request->get('type'));
                
                if($request->files->get('customFields') && !$this->get('file.service')->validateAttachmentsSize($request->files->get('customFields'))) {
                    $error = true;
                    $this->addFlash(
                            'warning',
                            $this->get('translator')->trans("Warning ! Files size can not exceed %size% MB", [
                                '%size%' => $this->container->getParameter('max_upload_size')
                            ])
                        );
                }

                $ticket = new TicketEntity();
                $loggedUser = $this->get('security.token_storage')->getToken()->getUser();
                
                if(!empty($loggedUser) && $loggedUser != 'anon.') {
                    
                    $form = $this->createForm(TicketForm::class, $ticket, [
                        'container' => $this->container,
                        'entity_manager' => $em,
                    ]);
                    $email = $loggedUser->getEmail();
                    try {
                        $name = $loggedUser->getFirstName() . ' ' . $loggedUser->getLastName();
                    } catch(\Exception $e) {
                        $name = explode(' ', strstr($email, '@', true));
                    }
                } else {
                    $form = $this->createForm(TicketForm::class, $ticket, [
                        'container' => $this->container,
                        'entity_manager' => $em,
                    ]);
                    $email = $request->request->get('from');
                    $name = explode(' ', $request->request->get('name'));
                }
                if($request->request->all())
                    $form->submit($request->request->all());
                
                // extract($this->get('customfield.service')->customFieldsValidation($request, 'customer'));
                // if(!empty($errorFlashMessage)) {
                //     $this->addFlash('warning', $errorFlashMessage);
                // }

                if ($form->isValid() && !count($formErrors) && !$error) {
                    $data = array(
                        'from' => $email, //email$request->getSession()->getFlashBag()->set('success', $this->get('translator')->trans('Success ! Ticket has been created successfully.'));
                        'subject' => $request->request->get('subject'),
                        'reply' => $request->request->get('reply'),
                        'firstName' => $name[0],
                        'lastName' => isset($name[1]) ? $name[1] : '',
                        'role' => 4,
                        'active' => true
                    );

                    $em = $this->getDoctrine()->getManager();
                    $data['type'] = $em->getRepository('UVDeskCoreBundle:TicketType')->find($request->request->get('type'));

                    if(!is_object($data['customer'] = $this->container->get('security.token_storage')->getToken()->getUser()) == "anon.") {
                        $customerEmail = $params['email'] = $request->request->get('from');
                        $customer = $em->getRepository('UVDeskCoreBundle:User')->findOneBy(array('email' => $customerEmail));
                        $params['flag'] = (!$customer) ? 1 : 0;$request->getSession()->getFlashBag()->set('success', $this->get('translator')->trans('Success ! Ticket has been created successfully.'));

                        $data['firstName'] = current($nameDetails = explode(' ', $request->request->get('name')));
                        $data['fullname'] = $request->request->get('name');
                        $data['lastName'] = ($data['firstName'] != end($nameDetails)) ? end($nameDetails) : " ";
                        $data['from'] = $customerEmail;
                        $data['role'] = 4;
                        $data['customer'] = $this->get('user.service')->getUserDetails($data);
                    } else {
                        $userDetail = $em->getRepository('UVDeskCoreBundle:User')->find($data['customer']->getId());
                        $data['email'] = $customerEmail = $data['customer']->getEmail();
                        $nameCollection = [$userDetail->getFirstName(), $userDetail->getLastName()];
                        $name = implode(' ', $nameCollection);
                        $data['fullname'] = $name;
                    }
                    $data['user'] = $data['customer'];
                    $data['subject'] = $request->request->get('subject');
                    $data['source'] = 'website';
                    $data['threadType'] = 'create';
                    $data['userType'] = 'customer';
                    $data['message'] = htmlentities($data['reply']);
                    $data['createdBy'] = $customerEmail;
                    $data['attachments'] = $request->files->get('attachments');

                    if(!empty($request->server->get("HTTP_CF_CONNECTING_IP") )) {
                        $data['ipAddress'] = $request->server->get("HTTP_CF_CONNECTING_IP");
                        if(!empty($request->server->get("HTTP_CF_IPCOUNTRY"))) {
                            $data['ipAddress'] .= '(' . $request->server->get("HTTP_CF_IPCOUNTRY") . ')';
                        }
                    }

                    $thread = $this->get('ticket.service')->createTicketBase($data);
                    if($thread) {
                        $ticket = $thread->getTicket();
                        if($request->request->get('customFields') || $request->files->get('customFields'))
                            $this->get('ticket.service')->addTicketCustomFields($ticket, $request->request->get('customFields'), $request->files->get('customFields'));

                    $request->getSession()->getFlashBag()->set('success', $this->get('translator')->trans('Success ! Ticket has been created successfully.'));
                    } else {
                        $request->getSession()->getFlashBag()->set('warning', $this->get('translator')->trans('Warning ! Can not create ticket, invalid details.'));
                    }
                    $request->getSession()->getFlashBag()->set('success', $this->get('translator')->trans('Success ! Ticket has been created successfully.'));
                    return $this->redirect($this->generateUrl('helpdesk_customer_create_ticket'));
                } else {
                    $errors = $this->getFormErrors($form);
                    $errors = array_merge($errors, $formErrors);
                }
            } else {
                $this->addFlash(
                    'warning',
                    $this->get('translator')->trans("Warning ! Post size can not exceed 25MB")
                );
            }

            if(isset($errors) && count($errors)) {
                $this->addFlash('warning', key($errors) . ': ' . reset($errors));
            }
        }

        $breadcrumbs = [
            [
                'label' => $this->get('translator')->trans('Support Center'),
                'url' => $this->generateUrl('helpdesk_knowledgebase')
            ],
            [
                'label' => $this->get('translator')->trans("Create Ticket Request"),
                'url' => '#'
            ],
        ];

        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticket.html.twig',
            array(
                'formErrors' => $formErrors,
                'errors' => json_encode($errors),
                'customFieldsValues' => $request->request->get('customFields'),
                'breadcrumbs' => $breadcrumbs,
                'post' => $post
            )
        );
    }

    public function ticketList(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $ticketRepo = $em->getRepository('UVDeskCoreBundle:Ticket');

        $currentUser = $this->get('security.token_storage')->getToken()->getUser();
        if(!$currentUser || $currentUser == "anon.") {
            //throw error
        }
        
        $tickets = $ticketRepo->getAllCustomerTickets($currentUser);
        
        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketList.html.twig', array(
            'ticketList' => $tickets,
        ));
    }

    public function saveReply(int $id, Request $request)
    {
        $this->isWebsiteActive();
        
        $data = $request->request->all();

        $ticket = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Ticket')->find($id);

        if($_POST) {
            if(str_replace(' ','',str_replace('&nbsp;','',trim(strip_tags($data['message'], '<img>')))) != "") {
                if(!$ticket)
                    $this->noResultFound();
                $data['ticket'] = $ticket;
                $data['user'] = $this->get('user.service')->getCurrentUser();

                $userDetail = $this->get('user.service')->getCustomerPartialDetailById($data['user']->getId());
                $data['fullname'] = $userDetail['name'];

                $data['userType'] = 'customer';
                $data['source']   = 'website';
                $data['createdBy']   = $userDetail['email'];
                $data['attachments'] = $request->files->get('attachments');

                $thread = $this->get('ticket.service')->createThread($ticket, $data);

                $em = $this->getDoctrine()->getManager();
                $status = $em->getRepository('UVDeskCoreBundle:TicketStatus')->find($data['status']);
                if($status) {
                    $flag = 0;
                    if($ticket->getStatus() != $status) {
                        $flag = 1;
                    }

                    $ticket->setStatus($status);
                    $em->persist($ticket);
                    $em->flush();
                }

                $this->addFlash('success', "Success ! Reply added successfully.");
            } else {
                $this->addFlash(
                    'warning',
                    $this->get('translator')->trans("Warning ! Reply field can not be blank.")
                );
            }
        } else {
            $this->addFlash(
                'warning',
                $this->get('translator')->trans("Warning ! Post size can not exceed 25MB")
            );
        }

        return $this->redirect($this->generateUrl('helpdesk_customer_ticket',array(
            'id' => $ticket->getId()
        )));
    }

    public function tickets(Request $request)
    {
        $this->isWebsiteActive();

        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketList.html.twig',
            array(
                'searchDisable' => true
            )
        );
    }

    /**
     * ticketListXhrAction "Filter and sort ticket collection on ajax request"
     * @param Object $request "HTTP Request object"
     * @return JSON "JSON response"
     */
    public function ticketListXhr(Request $request)
    {
        $this->isWebsiteActive();

        $json = array();
        if($request->isXmlHttpRequest()) {
            $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Ticket');
    
            $json = $repository->getAllCustomerTickets($request->query, $this->container);
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
    public function threadListXhr(Request $request)
    {
        $this->isWebsiteActive();

        $json = array();
        if($request->isXmlHttpRequest()) {
            $ticket = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Ticket')->find($request->attributes->get('id'));
            // $this->denyAccessUnlessGranted('FRONT_VIEW', $ticket);

            $repository = $this->getDoctrine()->getRepository('UVDeskCoreBundle:Thread');
            $json = $repository->getAllCustomerThreads($request->attributes->get('id'),$request->query, $this->container);
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function ticketView(int $id, Request $request)
    {
        $this->isWebsiteActive();

        $em = $this->getDoctrine()->getManager();

        $ticket = $em->getRepository('UVDeskCoreBundle:Ticket')->find($id);

        if(!$ticket)
            $this->noResultFound();
        
        $ticket->setIsCustomerViewed(1);
        $em->persist($ticket);
        $em->flush();
        
        $twigResponse = [
            'ticket' => $ticket,
            'searchDisable' => true,
        ];

        return $this->render('@UVDeskSupportCenter/Knowledgebase/ticketView.html.twig', $twigResponse);
    }
    // Ticket rating
    public function rateTicket(Request $request) {

        $this->isWebsiteActive();
        $json = array();
        $em = $this->getDoctrine()->getManager();
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $count = intval($data['rating']);
        
        if($count > 0 || $count < 6) {
            $ticket = $em->getRepository('UVDeskCoreBundle:Ticket')->find($id);
            $customer = $this->get('user.service')->getCurrentUser();
            $rating = $em->getRepository('UVDeskCoreBundle:TicketRating')->findOneBy(array('ticket' => $id,'customer'=>$customer->getId()));
            if($rating) {
                $rating->setcreatedAt(new \DateTime);
                $rating->setStars($count);
                $em->persist($rating);
                $em->flush();
            } else {
                $rating = new TicketRating();
                $rating->setStars($count);
                $rating->setCustomer($customer);
                $rating->setTicket($ticket);
                $em->persist($rating);
                $em->flush();
            }
            $json['alertClass'] = 'success';
            $json['alertMessage'] = $this->get('translator')->trans('Success ! Rating has been successfully added.');
        } else {
            $json['alertClass'] = 'danger';
            $json['alertMessage'] = $this->get('translator')->trans('Warning ! Invalid rating.');
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function downloadAttachmentZip(Request $request)
    {
        $threadId = $request->attributes->get('threadId');
        $attachmentRepository = $this->getDoctrine()->getManager()->getRepository('UVDeskCoreBundle:Attachment');

        $attachment = $attachmentRepository->findByThread($threadId);

        if (!$attachment) {
            $this->noResultFound();
        }

        $zipname = 'attachments/' .$threadId.'.zip';
        $zip = new \ZipArchive;

        $zip->open($zipname, \ZipArchive::CREATE);
        if(count($attachment)){
            foreach ($attachment as $attach) {
                $zip->addFile(substr($attach->getPath(), 1)); 
            }
        }
        $zip->close();

        $response = new Response();
        $response->setStatusCode(200);
        $response->headers->set('Content-type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $threadId . '.zip');
        $response->sendHeaders();
        $response->setContent(readfile($zipname));

        return $response;
    }
}
