<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Filesystem\Filesystem as Fileservice;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\SupportCenterBundle\Entity as SupportEntities;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\Services\FileUploadService;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;

class Folder extends AbstractController
{
    private $userService;
    private $translator;
    private $fileSystem;
    private $fileUploadService;
    private $em;

    public function __construct(UserService $userService, TranslatorInterface $translator, FileSystem $fileSystem, FileUploadService $fileUploadService, EntityManagerInterface $entityManager)
    {
        $this->userService = $userService;
        $this->translator = $translator;
        $this->fileSystem = $fileSystem;
        $this->fileUploadService = $fileUploadService;
        $this->em = $entityManager;
    }

    public function listFolders(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $totalKnowledgebaseFolders = count($this->em->getRepository(SupportEntities\Solutions::class)->findAll());
        $totalKnowledgebaseCategories = count($this->em->getRepository(SupportEntities\SolutionCategory::class)->findAll());
        $totalKnowledgebaseArticles = count($this->em->getRepository(SupportEntities\Article::class)->findAll());

        return $this->render('@UVDeskSupportCenter/Staff/Folders/listFolders.html.twig', [
            'articleCount'  => $totalKnowledgebaseArticles,
            'categoryCount' => $totalKnowledgebaseCategories,
            'solutionCount' => $totalKnowledgebaseFolders,
        ]);
    }

    public function createFolder(Request $request)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $folder = new SupportEntities\Solutions();
        $errors = [];

        if ($request->getMethod() == "POST") {
            $solutionImage = $request->files->get('solutionImage');

            if ($imageFile = $request->files->get('solutionImage')) {
                if (
                    $imageFile->getMimeType() == "image/svg+xml"
                    || $imageFile->getMimeType() == "image/svg"
                ) {
                    if (! $this->fileUploadService->svgFileCheck($imageFile)) {
                        $message = $this->translator->trans('Warning! Not a valid svg. (Recommended: PNG, JPG or GIF Format).');
                        $this->addFlash('warning', $message);

                        return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_create_folder'));
                    }
                }

                if (! preg_match('#^(image/)(?!(tif)|(svg) )#', $imageFile->getMimeType()) && !preg_match('#^(image/)(?!(tif)|(svg))#', $imageFile->getClientMimeType())) {

                    $message = $this->translator->trans('Warning! Provide valid image file. (Recommended: PNG, JPG or GIF Format).');
                    $this->addFlash('warning', $message);

                    return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_create_folder'));
                }

                if (strpos($imageFile->getClientOriginalName(), '.php') !== false) {
                    $message = $this->translator->trans('Warning! Provide valid image file. (Recommended: PNG, JPG or GIF Format).');
                    $this->addFlash('warning', $message);

                    return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_create_folder'));
                }
            }

            $data = $request->request->all();
            $folder->setName($data['name']);
            $folder->setDescription($data['description']);
            $folder->setvisibility($data['visibility']);

            if (isset($solutionImage)) {
                $assetDetails = $this->fileSystem->getUploadManager()->uploadFile($solutionImage, 'knowledgebase');
                $folder->setSolutionImage($assetDetails['path']);
            }

            $folder->setDateAdded(new \DateTime());
            $folder->setDateUpdated(new \DateTime());
            $folder->setSortOrder(1);

            $this->em->persist($folder);
            $this->em->flush();

            $message = $this->translator->trans('Success! Folder has been added successfully.');
            $this->addFlash('success', $message);

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_folders_collection'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/Folders/createFolder.html.twig', [
            'folder' => $folder,
            'errors' => json_encode($errors)
        ]);
    }

    public function updateFolder($folderId)
    {
        if (! $this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $knowledgebaseFolder = $this->em->getRepository(SupportEntities\Solutions::class)->findSolutionById(['id' => $folderId]);

        if (empty($knowledgebaseFolder)) {
            $this->noResultFound();
        }

        if ($request->getMethod() == "POST") {
            $formData = $request->request->all();
            $solutionImage = $request->files->get('solutionImage');

            $validMimeType = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $imageFile = $request->files->get('solutionImage');

            if (! empty($imageFile) && ! in_array($imageFile->getMimeType(), $validMimeType)) {
                $message = $this->translator->trans('Warning! Provide valid image file. (Recommended: PNG, JPG or GIF Format).');
                $this->addFlash('warning', $message);

                return $this->render('@UVDeskSupportCenter/Staff/Folders/updateFolder.html.twig', [
                    'folder' => $knowledgebaseFolder
                ]);
            }

            $formData = $request->request->all();
            if (isset($solutionImage)) {
                // Removing old image from physical path is new image uploaded
                $fileService = new Fileservice();
                if ($knowledgebaseFolder->getSolutionImage()) {
                    $fileService->remove($this->getParameter('kernel.project_dir') . "/public/" . $knowledgebaseFolder->getSolutionImage());
                }

                $assetDetails = $this->fileSystem->getUploadManager()->uploadFile($solutionImage, 'knowledgebase');
                $knowledgebaseFolder->setSolutionImage($assetDetails['path']);
            }

            $knowledgebaseFolder
                ->setName($formData['name'])
                ->setDescription($formData['description'])
                ->setvisibility($formData['visibility'])
                ->setDateUpdated(new \DateTime())
                ->setSortOrder(1);

            $this->em->persist($knowledgebaseFolder);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('Folder updated successfully.'));

            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_folders_collection'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/Folders/updateFolder.html.twig', [
            'folder' => $knowledgebaseFolder,
            'errors' => json_encode(!empty($errors) ? $errors : [])
        ]);
    }

    /**
     * If customer is playing with url and no result is found then what will happen
     * @return
     */
    protected function noResultFound()
    {
        throw new NotFoundHttpException('Not Found!');
    }
}
