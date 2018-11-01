<?php

namespace Webkul\UVDesk\SupportCenterBundle\Workstation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\SupportCenterBundle\Entity\Website;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Branding extends Controller
{  
    public function theme(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $errors = [];
        $entityManager = $this->getDoctrine()->getManager();
        $settingType = $request->attributes->get('type');
        $userService = $this->container->get('user.service');
        $website = $entityManager->getRepository('UVDeskCoreBundle:Website')->findOneBy(['code'=>"knowledgebase"]);
        $configuration = $entityManager->getRepository('UVDeskSupportCenterBundle:KnowledgebaseWebsite')->findOneBy(['website' => $website->getId(),'isActive' => 1]);
        
        if ($request->getMethod() == 'POST') {
            $isValid = 0;
            $params = $request->request->all();
            $parmsFile = ($request->files->get('website'));

            switch($settingType) {
                case "general": 
                    $website->setName($params['website']['name']);
                    $status = array_key_exists("status",$params['website']) ? 1 : 0;
                    
                    if (isset($parmsFile['logo'])) {
                        $fileName  = $this->container->get('uvdesk.service')->getFileUploadManager()->upload($parmsFile['logo']);
                        $website->setLogo($fileName);
                    }

                    $configuration->setStatus($status);
                    $configuration->setBrandColor($params['website']['brandColor']);
                    
                    $entityManager->persist($website);
                    $entityManager->persist($configuration);
                    $entityManager->flush();
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

                    $ticketCreateOption = array_key_exists('ticketCreateOption',$params['website']) ? $params['website']['ticketCreateOption'] : 0;
                    $loginRequiredToCreate = array_key_exists('loginRequiredToCreate',$params['website']) ? $params['website']['loginRequiredToCreate'] : 0;
                    $configuration->setTicketCreateOption($ticketCreateOption);                
                    $configuration->setLoginRequiredToCreate($loginRequiredToCreate);
                    $configuration->setUpdatedAt(new \DateTime());
                    $entityManager->persist($configuration);
                    $entityManager->flush();
                    break;
                case "seo":
                    $configuration->setMetaDescription($params['metaDescription']);  
                    $configuration->setMetaKeywords($params['metaKeywords']);  
                    $configuration->setUpdatedAt(new \DateTime());
                    $entityManager->persist($configuration);
                    $entityManager->flush();
                    break;
                case "links":
                    $footerLinks=[];
                    $headerLinks=[];
                    $headerLinks = $params['headerLinks'];                    
                    if(count($headerLinks)>0){
                        foreach ($headerLinks as $key => $link) {
                            if($link['label'] == '' || $link['url'] == '' || !filter_var($link['url'], FILTER_VALIDATE_URL)) {
                                
                                unset($headerLinks[$key]);
                            }
                        }
                    } 
                    $footerLinks = $params['footerLinks'];
                    if(count($footerLinks)>0){
                        foreach ($footerLinks as $key => $link) {
                            if($link['label'] == '' || $link['url'] == '' || !filter_var($link['url'], FILTER_VALIDATE_URL)) {
                                unset($footerLinks[$key]);
                            }
                        }
                    }

                    $configuration->setHeaderLinks($headerLinks);
                    $configuration->setFooterLinks($footerLinks);
                    $entityManager->persist($configuration);
                    $entityManager->flush();
                    break;
                case "broadcasting":
                    $params['broadcasting']['isActive'] = array_key_exists('isActive', $params['broadcasting']) ? true  : false;
                    $configuration->setBroadcastMessage(json_encode($params['broadcasting']));
                    $configuration->setUpdatedAt(new \DateTime());
                    
                    $entityManager->persist($configuration);
                    $entityManager->flush();
                    break;
                case 'advanced':
                    $configuration->setCustomCSS($request->request->get('customCSS'));
                    $configuration->setScript($request->request->get('script'));
                    $entityManager->persist($configuration);
                    $entityManager->flush();
                    break;
                default:
                    break;
            }
        }
    
        return $this->render('@UVDeskSupportCenter/Staff/branding.html.twig', [
            'websitedata' => $website,
            'type' => $settingType,
            'configuration' => $configuration,
            'broadcast' => json_decode($configuration->getBroadcastMessage()),
        ]);
    }

    public function spam(Request $request)
    {
        if (!$this->get('user.service')->checkPermission('ROLE_ADMIN')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $website = $entityManager->getRepository('UVDeskCoreBundle:Website')->findOneBy(['code'=>"knowledgebase"]);
        if(!$website) {
            // return not found
        }
        $configuration = $entityManager->getRepository('UVDeskSupportCenterBundle:KnowledgebaseWebsite')->findOneBy(['website' => $website->getId(), 'isActive' => 1]);
        $params = $request->request->all();

        
        if ($request->getMethod() == 'POST') {
            $configuration->setWhiteList($request->request->get('whiteList'));
            $configuration->setBlackList($request->request->get('blackList'));
            $entityManager->persist($configuration);
            $entityManager->flush();

            $this->addFlash('success', 'Spam setting saved successfully.');

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_spam'));
        }
        
        return $this->render('@UVDeskSupportCenter/Staff/spam.html.twig', [
            'whitelist'=>$configuration->getWhiteList(),
            'blacklist'=>$configuration->getBlackList(),
        ]);
    }

}