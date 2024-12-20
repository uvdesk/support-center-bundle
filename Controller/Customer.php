<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Form\UserProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webkul\UVDesk\CoreFrameworkBundle\Utils\TokenGenerator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Filesystem as Fileservice;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\UVDesk\CoreFrameworkBundle\Services as CoreServices;
use Webkul\UVDesk\CoreFrameworkBundle\Providers\UserProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

Class Customer extends AbstractController
{
    private $translator;
    private $fileSystem;
    private $passwordEncoder;
    private $fileUploadService;
    private $uvdeskService;
    private $userProvider;

    public function __construct(TranslatorInterface $translator, UserPasswordEncoderInterface $passwordEncoder, FileSystem $fileSystem, CoreServices\FileUploadService $fileUploadService, CoreServices\EmailService $emailService, CoreServices\UVDeskService $uvdeskService, UserProvider $userProvider)
    {
        $this->translator = $translator;
        $this->fileSystem = $fileSystem;
        $this->passwordEncoder = $passwordEncoder;
        $this->fileUploadService = $fileUploadService;
        $this->emailService = $emailService;
        $this->uvdeskService = $uvdeskService;
        $this->userProvider = $userProvider;
    }

    protected function redirectUserToLogin()
    {
        $authChecker = $this->container->get('security.authorization_checker');

        if ($authChecker->isGranted('ROLE_CUSTOMER'))
            return true;
    }

    protected function isWebsiteActive()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $website = $entityManager->getRepository(CoreEntities\Website::class)->findOneByCode('knowledgebase');
  
        if (! empty($website)) {
            $knowledgebaseWebsite = $entityManager->getRepository(SupportEntities\KnowledgebaseWebsite::class)->findOneBy(['website' => $website->getId(), 'status' => 1]);
            
            if (! empty($knowledgebaseWebsite) && true == $knowledgebaseWebsite->getIsActive()) {
                return true;
            }
        }

        $this->noResultFound();
    }

    protected function noResultFound()
    {
        throw new NotFoundHttpException('Permission Denied !');
    }

    protected function isLoginDisabled()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $website = $entityManager->getRepository(CoreEntities\Website::class)->findOneByCode('knowledgebase');

        if (! empty($website)) {
            $configuration = $entityManager->getRepository(SupportEntities\KnowledgebaseWebsite::class)->findOneBy([
                'website' => $website->getId(),
                'isActive' => 1,
            ]);

            if (
                ! empty($configuration) 
                && $configuration->getDisableCustomerLogin()
            ) {
                return true;
            }
        }

        return false;
    }

    public function loginOtpVerify(Request $request) {
        $params = $request->request->all();
        $entityManager = $this->getDoctrine()->getManager();

        if (empty($params['_username'])) {
            return new JsonResponse([
                'success' => false,
                'message' => "No user details provided. Please try again later.",
            ], 403);
        }

        $user = $entityManager->getRepository(CoreEntities\User::class)->findOneByEmail($params['_username']);

        if (empty($user) || empty($params['otp'])) {
            return new JsonResponse([
                'success' => false,
                'message' => "No associated user account details were found. Please try again later.",
            ], 403);
        } else if ($user->getVerificationCode() != $params['otp']) {
            return new JsonResponse([
                'success' => false,
                'message' => "Invalid OTP provided. Please try again later.",
            ], 403);
        }

        $currentTimestamp = new \DateTime('now');
        $lastOtpGeneratedAtTimestamp = $user->getLastOtpGeneratedAt();

        $lastOtpGeneratedAtTimestamp->modify('+5 minutes');
        $interval = $lastOtpGeneratedAtTimestamp->diff($currentTimestamp);

        $isTimePeriodElapsed = (bool) $interval->invert ? false : true;

        if ($isTimePeriodElapsed == true) {
            return new JsonResponse([
                'success' => false,
                'message' => "The provided OTP has expired. Please try again later.",
            ], 403);
        }

        $user = $this->userProvider->refreshUser($user);

        try {
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->container->get('security.token_storage')->setToken($token);

            // Regenerate the session
            $request->getSession()->migrate();

            $this->addFlash('success', $this->translator->trans('Success ! Logged in successfully.'));

            return new JsonResponse([
                'success' => true,
                'message' => "Successfully logged in.",
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => "Failed to login " . $e->getMessage() ,
            ], 403);
        }
    }

    public function generateOtp(Request $request) {
        $params = $request->request->all();
        $entityManager = $this->getDoctrine()->getManager();
        $website = $entityManager->getRepository(CoreEntities\Website::class)->findOneByCode('helpdesk');
        $knowledgebase = $entityManager->getRepository(CoreEntities\Website::class)->findOneByCode('knowledgebase');

        $user = $entityManager->getRepository(CoreEntities\User::class)->retrieveHelpdeskCustomerInstances($params['_username']);

        if (empty($user)) {
            return new JsonResponse([
                'success' => false,
                'message' => "No associated user accounts were found with the email '{$params['_username']}'.",
            ], 403);
        } else if ($this->isLoginDisabled()) {
            return new JsonResponse([
                'success' => false,
                'message' => "Login has been disabled for this helpdesk.",
            ], 403);
        }

        $currentTimestamp = new \DateTime('now');
        $lastOtpGeneratedAtTimestamp = $user->getLastOtpGeneratedAt();

        if (! empty($lastOtpGeneratedAtTimestamp)) {
            $lastOtpGeneratedAtTimestamp->modify('+1 minute');
            $interval = $lastOtpGeneratedAtTimestamp->diff($currentTimestamp);

            $isTimePeriodElapsed = (bool) $interval->invert ? false : true;

            if ($isTimePeriodElapsed == false) {
                return new JsonResponse([
                    'success' => false,
                    'message' => "Please wait for upto 1 minute before requesting a new OTP.",
                ]);
            }
        }

        $user->setVerificationCode(TokenGenerator::generateToken(6, $range = '0123456789'))
            ->setLastOtpGeneratedAt(new \DateTime('now'))
        ;

        $entityManager->persist($user);
        $entityManager->flush();

        $name = ucwords(trim(implode(' ', [$user->getFirstName(), $user->getLastName()])));

        // Generate email content
        $subject = "Login OTP from ".$website->getName();
        $content = $this->renderView('@UVDeskSupportCenter/CustomerLogin/customer-login-otp-verification-email.html.twig', [
            'name'             => $name,
            'verificationCode' => $user->getVerificationCode(),
            'helpdeskName'     => $website->getName(),
            'helpdeskMail'     => $this->getParameter('uvdesk.support_email.id'),
            'helpdeskLogo'     => $knowledgebase->getLogo() ? $this->uvdeskService->generateCompleteLocalResourcePathUri($knowledgebase->getLogo()) : "",
        ]);

        $this->emailService->sendMail($subject, $content, $user->getEmail());

        return new JsonResponse([
            'success' => true,
            'message' => "Please check your email for a OTP verification code.",
        ]);
    }

    public function login(Request $request)
    {
        $this->isWebsiteActive();

        if ($this->redirectUserToLogin()) {
            return $this->redirect($this->generateUrl('helpdesk_customer_ticket_collection')); // Replace with Dashboard route
        }

        /** check disabled customer login **/
        if ($this->isLoginDisabled()) {
            $this->addFlash('warning', $this->translator->trans('Warning ! Customer Login disabled by admin.') );
            
            return $this->redirect($this->generateUrl('helpdesk_knowledgebase'));
        }

        $session = $request->getSession();

        $error = $session->get(Security::AUTHENTICATION_ERROR);
        $session->remove(Security::AUTHENTICATION_ERROR);

        if ($error) {
            $this->addFlash('warning', $this->translator->trans('Warning ! ' . $error->getMessage()) );
        }

        return $this->render('@UVDeskSupportCenter/CustomerLogin/customer-login.html.twig', [
            'searchDisable' => true,
            'last_username' => $session->get(Security::LAST_USERNAME),
            'error'         => $error,
            'breadcrumbs' => [
                [
                    'label' => $this->translator->trans('Support Center'),
                    'url'   => $this->generateUrl('helpdesk_knowledgebase')
                ], [
                    'label' => $this->translator->trans('Sign In'),
                    'url'   => '#'
                ]
            ]
        ]);
    }

    public function Account(Request $request)
    {
        $this->isWebsiteActive();
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $errors = [];

        if ($request->getMethod() == 'POST') {
            $data     = $request->request->all();
            $dataFiles = $request->files->get('user_form');
            $data = $data['user_form'];

            // Profile upload validation
            $validMimeType = ['image/jpeg', 'image/png', 'image/jpg'];
            if (isset($dataFiles['profileImage'])) {
                if (! in_array($dataFiles['profileImage']->getMimeType(), $validMimeType)) {
                    $this->addFlash('warning', $this->translator->trans('Error ! Profile image is not valid, please upload a valid format'));
                    
                    return $this->redirect($this->generateUrl('helpdesk_customer_account'));
                }
            }

            $checkUser = $em->getRepository(CoreEntities\User::class)->findOneBy(array('email'=>$data['email']));
            $errorFlag = 0;

            if ($checkUser) {
                if ($checkUser->getId() != $user->getId())
                    $errorFlag = 1;
            }

            if (! $errorFlag) {
                $password = $user->getPassword();

                $form = $this->createForm(UserProfile::class, $user);
                $form->handleRequest($request);
                $form->submit($data);

                if ($form->isValid()) {
                    if ($data != null && (!empty($data['password']['first']))) {
                        $encodedPassword = $this->passwordEncoder->encodePassword($user, $data['password']['first']);

                        if (! empty($encodedPassword) ) {
                            $user->setPassword($encodedPassword);
                        }
                    } else {
                        $user->setPassword($password);
                    }

                    $user->setFirstName($data['firstName']);
                    $user->setLastName($data['lastName']);
                    $user->setEmail($data['email']);
                    $user->setTimeZone($data['timezone']);
                    $user->setTimeFormat($data['timeformat']);
                    
                    $em->persist($user);
                    $em->flush();

                    $userInstance = $em->getRepository(CoreEntities\UserInstance::class)->findOneBy(array('user' => $user->getId()));

                    if (isset($dataFiles['profileImage'])) {
                        $previousImage = $userInstance->getProfileImagePath();
                        if ($previousImage != null) {
                            $image = str_replace("\\","/",$this->getParameter('kernel.project_dir').'/public'.$previousImage);
                            $check = $this->fileUploadService->fileRemoveFromFolder($image); 
                        }
                        $assetDetails = $this->fileSystem->getUploadManager()->uploadFile($dataFiles['profileImage'], 'profile');
                        $userInstance->setProfileImagePath($assetDetails['path']);
                    }

                    // Removed profile image from database and path
                    $fileService = new Fileservice;
                    if ($request->get('removeImage') == 'on') {
                        if ($userInstance->getProfileImagePath()) {
                            $fileService->remove($this->getParameter('kernel.project_dir').'/public'.$userInstance->getProfileImagePath());
                        }
                        $userInstance = $userInstance->setProfileImagePath(null);
                    }

                    $userInstance  = $userInstance->setContactNumber($data['contactNumber']);
                    $em->persist($userInstance);
                    $em->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Profile updated successfully.'));

                    return $this->redirect($this->generateUrl('helpdesk_customer_account'));
                } else {
                    $errors = $form->getErrors();
                    $errors = $this->getFormErrors($form);
                }
            } else {
                $this->addFlash('warning', $this->translator->trans('Error ! User with same email is already exist.'));

                return $this->redirect($this->generateUrl('helpdesk_customer_account'));
            }
        }

        return $this->render('@UVDeskSupportCenter/Knowledgebase/customerAccount.html.twig', [
            'searchDisable' => true,
            'user'          => $user,
        ]);
    }

    public function searchArticle(Request $request)
    {
        $this->isWebsiteActive();
        $searchQuery = $request->query->get('s');
        if (empty($searchQuery)) {
            return $this->redirect($this->generateUrl('helpdesk_customer_ticket_collection'));
        }

        $articleCollection = $this->getDoctrine()->getRepository(SupportEntities\Article::class)->getArticleBySearch($request);

        return $this->render('@UVDeskSupportCenter/Knowledgebase/search.html.twig', [
            'search'   => $searchQuery,
            'articles' => $articleCollection,
            'breadcrumbs' => [
                ['label' => $this->translator->trans('Support Center'), 'url' => $this->generateUrl('helpdesk_knowledgebase')],
                ['label' => $searchQuery, 'url' => '#'],
            ],
        ]);
    }
}
