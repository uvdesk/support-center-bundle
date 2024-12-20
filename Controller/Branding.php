<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Entity as CoreEntities;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UVDeskService;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileservice;

class Branding extends AbstractController
{
    private $userService;
    private $translator;
    private $fileSystem;
    private $uvdeskService;

    public function __construct(UserService $userService, TranslatorInterface $translator, FileSystem $fileSystem, UVDeskService $uvdeskService)
    {
        $this->userService = $userService;
        $this->translator = $translator;
        $this->fileSystem = $fileSystem;
        $this->uvdeskService = $uvdeskService;
    }

    public function theme(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $errors = [];
        $entityManager = $this->getDoctrine()->getManager();
        $settingType = $request->attributes->get('type');
        $userService = $this->userService;
        $website = $entityManager->getRepository(CoreEntities\Website::class)->findOneBy(['code'=>"knowledgebase"]);
        $configuration = $entityManager->getRepository(SupportEntities\KnowledgebaseWebsite::class)->findOneBy(['website' => $website->getId(),'isActive' => 1]);
        $currentLocales = $this->uvdeskService->getDefaultLangauge();

        if ($request->getMethod() == 'POST') {
            $isValid = 0;
            $params = $request->request->all();
            $paramsFile = ($request->files->get('website'));
            $selectedLocale = isset($params['defaultLocale']) ? $params['defaultLocale'] : null;

            switch($settingType) {
                case 'business-hours':
                    $website->setBusinessHourStatus(isset($params['status']) && $params['status']=='on' ? 1 : 0);
                    $website->setBusinessHour(serialize($params['businessHours']));
                    $entityManager->persist($website);
                    $entityManager->flush();

                    break;
                case "general":
                    $website->setName($params['website']['name']);
                    $status = array_key_exists("status",$params['website']) ? 1 : 0;
                    $logo = $website->getLogo();

                    if ($logo != null && isset($paramsFile['logo'])) {
                        $fileService = new SymfonyFileservice;
                        $fileService->remove($this->getParameter('kernel.project_dir').'/public'.$logo);
                    }

                    if (isset($paramsFile['logo'])) {
                        $assetDetails = $this->fileSystem->getUploadManager()->uploadFile($paramsFile['logo'], 'website');
                        $website->setLogo($assetDetails['path']);
                    }

                    $configuration->setStatus($status);
                    $configuration->setBrandColor($params['website']['brandColor']);

                    $entityManager->persist($website);
                    $entityManager->persist($configuration);
                    $entityManager->flush();

                    if (! empty($selectedLocale)) {
                        if (false == $this->uvdeskService->updatesLocales($selectedLocale)) {
                            $this->addFlash('danger', $this->translator->trans('Warning! Locales could not be updated successfully.'));
                        } else {
                            $currentLocales = $selectedLocale;
                        }
                    }

                    $this->addFlash('success', $this->translator->trans('Success ! Branding details saved successfully.'));

                    break;
                case "knowledgebase":
                    $configuration->setPageBackgroundColor($params['website']['pageBackgroundColor']);
                    $configuration->setHeaderBackgroundColor($params['website']['headerBackgroundColor']);

                    $configuration->setLinkColor($params['website']['linkColor']);
                    $configuration->setLinkHoverColor($params['website']['linkHoverColor']);
                    $configuration->setArticleTextColor($params['website']['articleTextColor']);
                    $configuration->setSiteDescription($params['website']['siteDescription']);
                    $configuration->setBannerBackgroundColor($params['website']['bannerBackgroundColor']);
                    $configuration->setHomepageContent($params['website']['homepageContent']);

                    $removeCustomerLoginButton = array_key_exists("removeCustomerLoginButton",$params['website']) ? $params['website']['removeCustomerLoginButton'] : 0;
                    $removeBrandingContent = array_key_exists("removeBrandingContent",$params['website']) ? $params['website']['removeBrandingContent'] : 0;
                    $disableCustomerLogin = array_key_exists("disableCustomerLogin",$params['website']) ? $params['website']['disableCustomerLogin'] : 0;

                    $configuration->setRemoveCustomerLoginButton($removeCustomerLoginButton);
                    $configuration->setRemoveBrandingContent($removeBrandingContent);
                    $configuration->setDisableCustomerLogin($disableCustomerLogin);

                    $ticketCreateOption = array_key_exists('ticketCreateOption', $params['website']) ? 1 : 0;
                    $loginRequiredToCreate = array_key_exists('loginRequiredToCreate', $params['website']) ? 1 : 0;
                    $configuration->setTicketCreateOption($ticketCreateOption);
                    $configuration->setLoginRequiredToCreate($loginRequiredToCreate);
                    $configuration->setUpdatedAt(new \DateTime());
                    $entityManager->persist($configuration);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Branding details saved successfully.'));
                    break;
                case "seo":
                    $configuration->setMetaDescription($params['metaDescription']);
                    $configuration->setMetaKeywords($params['metaKeywords']);
                    $configuration->setUpdatedAt(new \DateTime());
                    $entityManager->persist($configuration);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Branding details saved successfully.'));
                    break;
                case "links":
                    $footerLinks=[];
                    $headerLinks=[];
                    $headerLinks = isset($params['headerLinks'])? $params['headerLinks']: '';
                    $footerLinks = isset($params['footerLinks']) ? $params['footerLinks']: 0;

                    if (! empty($headerLinks)) {
                        foreach ($headerLinks as $key => $link) {
                            if (
                                $link['label'] == '' 
                                || $link['url'] == '' 
                                || !filter_var($link['url'], FILTER_VALIDATE_URL)
                            ) {
                                unset($headerLinks[$key]);
                            }
                        }
                    }

                    if (! empty($footerLinks)) {
                        foreach ($footerLinks as $key => $link) {
                            if (
                                $link['label'] == '' 
                                || $link['url'] == '' 
                                || !filter_var($link['url'], FILTER_VALIDATE_URL)
                            ) {
                                unset($footerLinks[$key]);
                            }
                        }
                    }

                    $configuration->setHeaderLinks($headerLinks);
                    $configuration->setFooterLinks($footerLinks);
                    $entityManager->persist($configuration);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Branding details saved successfully.'));
                    break;
                case "broadcasting":
                    $params['broadcasting']['isActive'] = array_key_exists('isActive', $params['broadcasting']) ? true  : false;
                    $configuration->setBroadcastMessage(json_encode($params['broadcasting']));
                    $configuration->setUpdatedAt(new \DateTime());

                    $entityManager->persist($configuration);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Branding details saved successfully.'));
                    break;
                case 'advanced':
                    $configuration->setCustomCSS($request->request->get('customCSS'));
                    $configuration->setScript($request->request->get('script'));
                    $entityManager->persist($configuration);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Branding details saved successfully.'));
                    break;
                case 'time':
                    $configuration->getWebsite()->setTimezone($params['form']['timezone']);
                    $configuration->getWebsite()->setTimeformat($params['form']['timeFormat']);

                    $entityManager->persist($configuration);
                    $entityManager->flush();

                    $this->addFlash('success', $this->translator->trans('Success ! Time details saved successfully.'));
                    break;
                default:
                    break;
            }
        }

        return $this->render('@UVDeskSupportCenter/Staff/branding.html.twig', [
            'websiteData'   => $website,
            'type'          => $settingType,
            'configuration' => $configuration,
            'broadcast'     => json_decode($configuration->getBroadcastMessage()),
            'locales'       => $currentLocales,
            'days'          => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
            'time_interval' => [
                "00:00" => "12:00 AM",
                "1:00"  => "1:00 AM",
                "2:00"  => "2:00 AM",
                "3:00"  => "3:00 AM",
                "4:00"  => "4:00 AM",
                "5:00"  => "5:00 AM",
                "6:00"  => "6:00 AM",
                "7:00"  => "7:00 AM",
                "8:00"  => "8:00 AM",
                "9:00"  => "9:00 AM",
                "10:00" => "10:00 AM",
                "11:00" => "11:00 AM",
                "12:00" => "12:00 AM",
                "13:00" => "1:00 PM",
                "14:00" => "2:00 PM",
                "15:00" => "3:00 PM",
                "16:00" => "4:00 PM",
                "17:00" => "5:00 PM",
                "18:00" => "6:00 PM",
                "19:00" => "7:00 PM",
                "20:00" => "8:00 PM",
                "21:00" => "9:00 PM",
                "22:00" => "10:00 PM",
                "23:00" => "11:00 PM",
            ],
            'business_hours' => unserialize($website->getBusinessHour()),
        ]);
    }

    public function spam(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $params = $request->request->all();
        $entityManager = $this->getDoctrine()->getManager();

        $website = $entityManager->getRepository(CoreEntities\Website::class)->findOneBy(['code'=>"knowledgebase"]);
        
        if (! $website) {
            throw new \Exception("No knowledgebase website details were found.");
        }
        
        $configuration = $entityManager->getRepository(SupportEntities\KnowledgebaseWebsite::class)->findOneBy(['website' => $website->getId(), 'isActive' => 1]);
        
        $blacklist = ! empty($params['blackList']) ? explode(',', $params['blackList']) : [];
        $whitelist = ! empty($params['whiteList']) ? explode(',', $params['whiteList']) : [];

        $blacklist = array_values(array_filter(array_map(function ($email) {
            return trim($email);
        }, $blacklist)));

        $whitelist = array_values(array_filter(array_map(function ($email) {
            return trim($email);
        }, $whitelist)));

        $whitelist = implode(',', $whitelist);
        $blacklist = implode(',', $blacklist);

        if ($request->getMethod() == 'POST') {
            $configuration
                ->setWhiteList($whitelist)
                ->setBlackList($blacklist)
            ;

            $entityManager->persist($configuration);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('Spam setting saved successfully.'));

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_spam'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/spam.html.twig', [
            'whitelist' => $configuration->getWhiteList(),
            'blacklist' => $configuration->getBlackList(),
        ]);
    }

    public function LocalesUpdateXhr(Request $request)
    {
        $params = $request->request->all();
        $defaultLocale = isset($params['defaultLocale']) ? $params['defaultLocale'] : null;

        if (! empty($defaultLocale)) {
            $localesStatus = $this->uvdeskService->updatesLocales($defaultLocale);
            $localesStatus == true ? '' : $this->addFlash('danger', $this->translator->trans('Warning ! Locales not updates successfully.'));
        }

        $json['alertClass'] = 'success';
        $json['alertMessage'] = $this->translator->trans('Success ! Updated.');

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}